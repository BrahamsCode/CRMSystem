<?php

namespace App\Services\Promotions\Push;

/**
 * Envío de notificaciones push. La implementación real (FCM / APNs) depende de
 * la app de Mi página; mientras tanto se usa LogPushSender.
 */
interface PushSender
{
    /**
     * @param  list<string>  $tokens  tokens de customer_devices
     * @return bool true si el proveedor aceptó el envío
     */
    public function send(array $tokens, string $title, string $body, ?string $url = null): bool;
}
