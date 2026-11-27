<?php

namespace App\Services\Promotions;

use Illuminate\Support\Facades\Log;

/**
 * PushTrigger de desarrollo (config promocion.push_trigger = log): anota el aviso que se habría enviado a g-api
 * en vez de llamar al servidor de push real.
 */
class LogPushTrigger implements PushTrigger
{
    public function trigger(string $type, int $masterCd): void
    {
        Log::info('PushTrigger (log): no se avisa a g-api', ['type' => $type, 'cd' => $masterCd]);
    }
}
