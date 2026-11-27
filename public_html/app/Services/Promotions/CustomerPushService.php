<?php

namespace App\Services\Promotions;

use App\Models\Promotions\PushMaster;
use Illuminate\Support\Facades\DB;

/**
 * Push a un cliente concreto, como appview/member_regist.php (paquete crm: お友達紹介ポイント, ご登録ありがとうございます…):
 * una fila en push_master cuyo `wherestr` apunta a ese cliente, opcionalmente su fila en push_notifications,
 * y el aviso a g-api (`PushTrigger`, Push.php::exec) con type = 'push'. El servidor de push lee la fila por su cd.
 * Lo usan S5 (appview member_regist / customerUpdate) y los comandos de puntos de S3.
 */
class CustomerPushService
{
    public function __construct(private readonly PushTrigger $trigger) {}

    /**
     * @param  string  $customerId  tb_customer.id (el login de la app) del destinatario
     * @param  string|array  $message  texto o lista de párrafos; se guarda serializado como array (lo espera g-api)
     * @param  array  $extraData  p. ej. ['system' => 'mpa', 'file' => 'signUp', 'action' => 'newRegist'] (JSON)
     * @param  int|null  $postPoint  puntos que anuncia el push (post_type 2); null = solo mensaje (post_type 1)
     * @param  int|null  $pointExpirationDate  epoch de caducidad de esos puntos
     * @param  array{cd: int, registration_id: string}|null  $notification  fila de push_notifications (si la app ya dio su registration_id)
     * @return int cd del push_master
     */
    public function send(string $customerId, int $shopCd, string $title, string|array $message, array $extraData = [],
        ?int $postPoint = null, ?int $pointExpirationDate = null, ?array $notification = null): int
    {
        return DB::transaction(function () use ($customerId, $shopCd, $title, $message, $extraData, $postPoint, $pointExpirationDate, $notification) {
            $now = time();
            $push = PushMaster::create([
                'tb_shop_cd' => $shopCd,
                'title' => $title,
                'message' => serialize(array_values((array) $message)),
                'img_flg' => '',
                'wherestr' => self::whereFor($customerId),
                'search_display' => '',
                'disp_flg' => '1',
                'touroku_date' => $now,
                'update_date' => $now,
                'version' => 2,
                'post_type' => $postPoint !== null ? PushMaster::POST_WITH_POINTS : PushMaster::POST_MESSAGE,
                'point_expiration_date' => $pointExpirationDate,
                'post_point' => $postPoint,
                'extra_data' => $extraData === [] ? null : json_encode($extraData, JSON_UNESCAPED_UNICODE),
            ]);

            if ($notification !== null) {
                DB::table('push_notifications')->insert([
                    // El legacy (paquete crm) insertaba cd = 1 fijo; push_notifications no tiene hatuban
                    'cd' => 1,
                    'type' => 'push',
                    'type_master_cd' => $push->cd,
                    'tb_customer_cd' => (int) $notification['cd'],
                    'registration_id' => (string) $notification['registration_id'],
                    'touroku_date' => $now,
                ]);
            }

            DB::afterCommit(fn () => $this->trigger->trigger('push', (int) $push->cd));

            return (int) $push->cd;
        });
    }

    /** wherestr del destinatario (sobre tb_customer, como member_regist.php) con el id escapado */
    public static function whereFor(string $customerId): string
    {
        return "WHERE id = '".str_replace("'", "''", $customerId)."' AND disp_flg = '1' AND touroku_kubun = '2'";
    }
}
