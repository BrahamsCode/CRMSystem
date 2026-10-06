<?php

namespace App\Services\Promotions\Push;

use Illuminate\Support\Facades\Log;

/** Deja cada notificación en el log: sirve en desarrollo y hasta que exista la app */
class LogPushSender implements PushSender
{
    public function send(array $tokens, string $title, string $body, ?string $url = null): bool
    {
        Log::info('Push', ['tokens' => count($tokens), 'title' => $title, 'body' => mb_substr($body, 0, 200), 'url' => $url]);

        return $tokens !== [];
    }
}
