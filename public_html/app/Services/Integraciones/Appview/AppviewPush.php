<?php

namespace App\Services\Integraciones\Appview;

/**
 * Push de member_regist.php (お友達紹介ポイント y ご登録ありがとうございます) con el CustomerPushService de S3
 * (push_master + push_notifications + aviso a g-api). En el legacy el exec de appview/pushMessage.php fallaba (el
 * archivo no existe) y el push no salía; aquí sale (anotado para Japón).
 *
 * Mientras S0 no integre CustomerPushService (l13/promocion 3e5f081) no se envía nada.
 */
class AppviewPush
{
    private const SERVICE = 'App\\Services\\Promotions\\CustomerPushService';

    /**
     * @param  array{cd: int, registration_id: ?string}|null  $notification
     */
    public function send(string $customerId, int $shopCd, string $title, string $message, array $extraData, ?int $postPoint, ?int $pointExpirationDate, ?array $notification = null): ?int
    {
        if (! class_exists(self::SERVICE)) {
            return null;
        }

        return app(self::SERVICE)->send($customerId, $shopCd, $title, [$message], $extraData, $postPoint, $pointExpirationDate, $notification);
    }
}
