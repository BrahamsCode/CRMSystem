<?php

namespace App\Services\Promotions;

use App\Jobs\Promotions\SendMailmagJob;
use App\Models\Promotions\MailmagMaster;
use App\Models\Promotions\SendSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registro de un envío (inc/mail_send_frm.php): fila de mailmag_master con el `振分配信` generado
 * por el servidor (MailAudience) y, según 配信時間, envío inmediato en segundo plano (mailsoushin.php)
 * o programación en send_schedule (cSendSchedule::addSchedule).
 */
class MailRegistrar
{
    public const FOLLOW_TYPES = ['follow', 'followdeco', 'yoyaku', 'posyoyaku', 'remdeco', 'posremdeco'];

    public const AUTO_TYPES = ['auto', 'autodeco'];

    public function __construct(private readonly MailAudience $audience) {}

    /** type_flg de mailmag_master para cada mail_type de pantalla */
    public static function typeFlg(string $mailType): string
    {
        return match ($mailType) {
            'mail' => '',
            'mycoupon' => 'coupon',
            default => $mailType,
        };
    }

    /**
     * @param  array<string, mixed>  $in  campos del formulario confirmado (title, naiyou, send_flg, nen…fun,
     *                                    tourokusya, bikou, reigai_kaiin_cds, get_coupon_cd, hakkou_cd, p_*, mailmagcd, pic_link, step*)
     */
    public function register(string $mailType, array $in, string $uniqid, string $shopId): MailmagMaster
    {
        $now = Carbon::now(LegacyTime::ZONE);
        $excluded = MailAudience::cds($in['reigai_kaiin_cds'] ?? null);
        $counts = $this->audience->counts($uniqid, $excluded);
        $description = $this->audience->description($uniqid);
        $immediate = false;

        $row = [
            '店id' => $shopId,
            '投稿日時' => $now->format('Y/m/d H:i:s'),
            '投稿日' => $now->format('Y/m/d'),
            '投稿時間' => $now->format('H:i:s'),
            'タイトル' => (string) ($in['title'] ?? ''),
            'pc内容' => (string) ($in['naiyou'] ?? ''),
            'pc配信数' => $counts['pc'],
            'docomo配信数' => $counts['docomo'],
            'au配信数' => $counts['au'],
            'vodafone配信数' => $counts['softbank'],
            'その他配信数' => $counts['other'],
            '配信総数' => $counts['total'],
            '登録者' => (string) ($in['tourokusya'] ?? ''),
            '備考' => (string) ($in['bikou'] ?? ''),
            '受付メールアドレス' => 'なし',
            'type_flg' => self::typeFlg($mailType),
            'search_display' => $this->audience->display($uniqid),
            '振分配信' => $this->audience->whereSql($uniqid, $excluded),
            '振分条件' => (string) ($in['reigai_kaiin_cds'] ?? ''),
        ];

        $hour = sprintf('%02d', (int) ($in['step2_jikan'] ?? 0));
        $minute = sprintf('%02d', (int) ($in['step2_fun'] ?? 0));
        if (in_array($mailType, self::FOLLOW_TYPES, true)) {
            $row += ['配信日時' => "$hour:$minute", '配信時間' => "$hour:$minute",
                'send_parameter' => ($in['shop_cd'] ?? '').'<>'.($in['step1'] ?? '').'<>'.($in['step2_1'] ?? '')];
        } elseif (in_array($mailType, self::AUTO_TYPES, true)) {
            $step2 = ($in['step1'] ?? '') === '1' ? ($in['step2_1'] ?? '').'<>'.($in['step2_2'] ?? '') : ($in['step2_3'] ?? '');
            $row += ['配信日時' => "$hour:$minute", '配信時間' => "$hour:$minute", 'send_parameter' => ($in['step1'] ?? '').'<>'.$step2];
        } elseif (($in['send_flg'] ?? '') === '1') {
            $immediate = true;
            $row += ['配信日時' => $now->format('Y/m/d H:i:s'), '配信日' => $now->format('Y/m/d'), '配信時間' => $now->format('H:i:s')];
        } else {
            $day = sprintf('%04d/%02d/%02d', (int) ($in['nen'] ?? 0), (int) ($in['tuki'] ?? 0), (int) ($in['hi'] ?? 0));
            $time = sprintf('%02d:%02d:00', (int) ($in['jikan'] ?? 0), (int) ($in['fun'] ?? 0));
            $row += ['配信日時' => "$day $time", '配信日' => $day, '配信時間' => $time];
        }

        $row += $this->imageColumns($in);

        if ($mailType !== 'anketo' && (string) ($in['get_coupon_cd'] ?? '') !== '') {
            $row['type_data_cd'] = (int) $in['get_coupon_cd'];
        } elseif ((string) ($in['hakkou_cd'] ?? '') !== '') {
            $row['type_data_cd'] = (int) $in['hakkou_cd'];
        }

        $row['配信状況'] = $immediate ? MailmagMaster::STATUS_SENT : MailmagMaster::STATUS_PENDING;
        if ((string) ($in['pic_link'] ?? '') !== '') {
            $row['画像タイトル'] = (string) $in['pic_link'];
        }

        $mail = DB::transaction(fn () => MailmagMaster::create($row));

        if ($immediate) {
            SendMailmagJob::dispatch($mail->cd)->afterResponse();
        } else {
            $this->schedule($mail);
        }

        return $mail;
    }

    /** cSendSchedule::addSchedule: una fila por envío con el 配信日時 (no para los anulados con disp_flg) */
    public function schedule(MailmagMaster $mail): void
    {
        if ((string) $mail->disp_flg !== '') {
            return;
        }
        DB::transaction(function () use ($mail) {
            SendSchedule::query()->where('mag_cd', $mail->cd)->delete();
            SendSchedule::create([
                '店id' => (string) $mail->getAttribute('店id'),
                '配信時間' => (string) $mail->getAttribute('配信日時'),
                'flg' => '1',
                'mag_cd' => $mail->cd,
            ]);
        });
    }

    private function imageColumns(array $in): array
    {
        if ((string) ($in['p_name_b'] ?? '') !== '') {
            return [
                '画像有無' => '1',
                '画像名大' => (string) $in['p_name_b'],
                '画像名小' => (string) ($in['p_name_s'] ?? ''),
                '画像拡張子' => (string) ($in['p_kakucyo'] ?? ''),
                '画像タイプ' => (string) ($in['p_type'] ?? ''),
                '画像サイズ大' => (string) ($in['p_size_b'] ?? ''),
                '画像サイズ小' => (string) ($in['p_size_s'] ?? ''),
                '画像表示有無' => '1',
            ];
        }

        // Reenvío de un envío pasado: reutiliza su imagen
        if ((string) ($in['mailmagcd'] ?? '') !== '') {
            $orig = MailmagMaster::query()->find((int) $in['mailmagcd']);
            if ($orig && (string) $orig->getAttribute('画像有無') === '1') {
                return collect(['画像有無', '画像名大', '画像名小', '画像拡張子', '画像タイプ', '画像サイズ大', '画像サイズ小', '画像表示有無'])
                    ->mapWithKeys(fn ($c) => [$c => $orig->getAttribute($c)])->all();
            }
        }

        return ['画像有無' => '0', '画像表示有無' => '0'];
    }
}
