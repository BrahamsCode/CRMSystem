<?php

namespace App\Services\Promotions;

use App\Models\Promotions\PushFollowMaster;
use App\Models\Promotions\PushMaster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registro de un push (legacy category3/push/ajaxSavePush.php + Push::followSaveOrUpdate):
 *   - sin type_flg: fila en push_master y aviso inmediato a g-api (`PushTrigger`, Push::exec) con type = push;
 *   - programado (scheduled_flg = 1 o type_flg = scheduled), フォロー o 自動: fila en push_follow_master con su
 *     next_send_date; la convierte en push_master el comando promocion:send-follow-push (sendFollowPush.php);
 *   - prueba a un socio (testMemberCd): push normal con `WHERE cd = <socio>` y search_display テスト配信.
 * Los bloques (texto o imagen) se guardan como `serialize(array)` en `message` y los enlaces de las imágenes
 * en `pic_link` (índice del bloque => URL), como los lee g-api.
 */
class PushRegistrar
{
    /** int4 de push_master.point_expiration_date (el legacy ponía PHP_INT_MAX y el INSERT fallaba) */
    private const MAX_EPOCH = 2147483647;

    /** Mensaje del legacy si el INSERT/UPDATE falla */
    public const FAILED = '登録に失敗しました。';

    public function __construct(
        private readonly PushTrigger $trigger,
        private readonly PushSchedule $schedule,
        private readonly PushImageStore $images,
        private readonly MailAudience $audience,
    ) {}

    /**
     * @param  array  $in  la petición ya validada (PushSaveRequest)
     * @param  int  $shopCd  tb_shop_cd del push: -1 para master, si no la tienda del personal
     * @return int cd de push_master o de push_follow_master
     */
    public function save(array $in, int $shopCd): int
    {
        $now = Carbon::now(LegacyTime::ZONE);
        $test = isset($in['testMemberCd']) && (string) $in['testMemberCd'] !== '';
        $postType = (int) ($in['post_type'] ?? 0);
        $requestType = (string) ($in['type_flg'] ?? '');

        [$contents, $registered] = $this->blocks((array) ($in['contents'] ?? []), (array) ($in['registeredFlg'] ?? []));
        $picLinks = array_filter((array) ($in['pic_link'] ?? []), fn ($v) => is_string($v) && $v !== '');

        // Caducidad de los puntos (epoch): fecha elegida a las 23:59:59, o «配信から N 日後» en la prueba de フォロー/自動
        $expirationDate = 0;
        if ($postType === 2 && ! in_array($requestType, ['auto', 'follow'], true)) {
            $expirationDate = (int) PushSchedule::parseDate($in['point_expiration_date'].' 23:59:59');
        }

        if ($test) {
            if ((string) ($in['point_expiration_days'] ?? '') !== '') {
                $expirationDate = $now->copy()->setTime(23, 59, 59)->getTimestamp() + (int) $in['point_expiration_days'] * 86400;
                $in['point_expiration_days'] = '';
            }
            $params = ['cd' => null, 'type_flg' => '', 'wherestr' => 'WHERE cd = '.(int) $in['testMemberCd'], 'search_display' => 'テスト配信', 'send_parameter' => ''];
        } else {
            $uniqid = (string) ($in['search_uniqid'] ?? '');
            $scheduled = ($in['scheduled_flg'] ?? '') === '1' || $requestType === 'scheduled';
            $params = [
                'cd' => (string) ($in['cd'] ?? '') !== '' ? (int) $in['cd'] : null,
                'type_flg' => $scheduled ? 'scheduled' : $requestType,
                'wherestr' => $this->audience->has($uniqid) ? $this->audience->whereSql($uniqid) : '',
                'search_display' => $this->audience->has($uniqid) ? $this->audience->display($uniqid) : '',
                'send_parameter' => $scheduled
                    ? 'send_date='.($in['send_date'] ?? '').',point_expiration_date='.($in['point_expiration_date'] ?? '')
                    : (string) ($in['sendParameter'] ?? ''),
            ];
        }

        $postPoint = $postType === 2 ? (int) ($in['post_point'] ?? 0) : 0;
        $row = $params + [
            'tb_shop_cd' => $shopCd,
            'title' => (string) $in['title'],
            'message' => serialize($contents),
            'pic_link' => serialize($picLinks),
            'post_type' => (string) $postType,
            'post_point' => $postPoint > 0 ? $postPoint : null,
            'point_expiration_days' => $postPoint > 0 ? (int) ($in['point_expiration_days'] ?? 0) : null,
            'point_expiration_date' => $postPoint > 0 ? min($expirationDate, self::MAX_EPOCH) : null,
            'send_time' => ($in['send_hour'] ?? '').':'.($in['send_min'] ?? ''),
        ];

        return DB::transaction(function () use ($row, $contents, $registered, $test, $in, $requestType) {
            $cd = $row['type_flg'] !== '' ? $this->saveFollow($row) : $this->savePush($row);
            $this->storeImages($contents, $registered, PushImageStore::folder($row['type_flg']), $cd, $test, $requestType, (int) ($in['cd'] ?? 0));

            if ($row['type_flg'] === '') {
                DB::afterCommit(fn () => $this->trigger->trigger('push', $cd));
            }

            return $cd;
        });
    }

    /** Bloques no vacíos, reindexados, con su bandera «imagen ya registrada» */
    private function blocks(array $contents, array $registeredFlags): array
    {
        $values = $registered = [];
        foreach ($contents as $key => $value) {
            if (! is_string($value) || $value === '' || $value === '0') {
                continue;
            }
            $values[] = $value;
            $registered[] = (string) ($registeredFlags[$key] ?? '');
        }

        return [$values, $registered];
    }

    private function savePush(array $row): int
    {
        $push = PushMaster::create([
            'tb_shop_cd' => $row['tb_shop_cd'],
            'title' => $row['title'],
            'message' => $row['message'],
            'pic_link' => $row['pic_link'],
            'img_flg' => '1',
            'version' => 2,
            'wherestr' => $row['wherestr'],
            'search_display' => $row['search_display'],
            'pos_search' => '',
            'pos_tb_shop_cd' => 0,
            'disp_flg' => '1',
            'post_type' => $row['post_type'],
            'post_point' => $row['post_point'],
            'point_expiration_date' => $row['point_expiration_date'],
        ]);

        return (int) $push->cd;
    }

    /** Alta o modificación de push_follow_master (programado, フォロー, 自動) */
    private function saveFollow(array $row): int
    {
        $data = [
            'title' => $row['title'],
            'message' => $row['message'],
            'pic_link' => $row['pic_link'],
            'post_point' => $row['post_point'],
            'point_expiration_days' => $row['post_point'] !== null ? $row['point_expiration_days'] : null,
            'send_time' => $row['send_time'],
            'next_send_date' => $this->schedule->nextSendDate($row['type_flg'], $row['send_time'], $row['send_parameter']),
        ];

        if ($row['cd'] !== null) {
            $follow = PushFollowMaster::query()->whereKey($row['cd'])->whereIn('disp_flg', ['1', PushFollowMaster::PAUSED_FLAG])->firstOrFail();
            // El legacy no actualizaba send_parameter: al cambiar la fecha de un programado la lista seguía con la vieja
            if ($row['type_flg'] === 'scheduled') {
                $data['send_parameter'] = $row['send_parameter'];
            }
            $follow->update($data);

            return (int) $follow->cd;
        }

        $follow = PushFollowMaster::create($data + [
            'tb_shop_cd' => $row['tb_shop_cd'],
            'wherestr' => $row['wherestr'],
            'search_display' => $row['search_display'],
            'pos_search' => '',
            'pos_tb_shop_cd' => 0,
            'disp_flg' => '1',
            'type_flg' => $row['type_flg'],
            'send_parameter' => $row['send_parameter'],
        ]);

        return (int) $follow->cd;
    }

    /** Imágenes nuevas: de la subida temporal a hp/uploads/c3/<carpeta>/<cd>/; registradas: se copian solo en la prueba */
    private function storeImages(array $contents, array $registered, string $folder, int $cd, bool $test, string $requestType, int $originalCd): void
    {
        foreach ($contents as $i => $value) {
            if (! preg_match(PushImageStore::IMAGE_PATTERN, $value)) {
                continue;
            }
            if ($registered[$i] === '1') {
                if ($test && $originalCd > 0) {
                    $this->images->copyRegistered($value, PushImageStore::folder($requestType), $originalCd, $folder, $cd);
                }

                continue;
            }
            $this->images->promote($value, $folder, $cd);
        }
    }
}
