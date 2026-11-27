<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use Illuminate\Support\Facades\Http;

/**
 * Push.php::exec del legacy: GET /api/mpa/pushMessage?domain=&type=&cd= a b.g-api.net
 * (b-develop.g-api.net si admin_master.push_api_host <> '1'), solo con la app activa
 * (application_use_flg = 1). Mismo contrato; si el servidor no responde se sigue sin error, como el legacy.
 */
class GApiPushTrigger implements PushTrigger
{
    public function __construct(private readonly SystemDomain $domain) {}

    public function trigger(string $type, int $masterCd): void
    {
        $admin = AdminMaster::current();
        if ((int) $admin->application_use_flg !== 1) {
            return;
        }

        $host = (string) $admin->push_api_host === '1' ? 'b.g-api.net' : 'b-develop.g-api.net';

        try {
            Http::timeout(30)->get("http://{$host}/api/mpa/pushMessage", [
                'domain' => $this->domain->domain(),
                'type' => $type,
                'cd' => $masterCd,
            ]);
        } catch (\Throwable) {
            // Igual que el legacy: el fallo del servidor de push no detiene el envío
        }
    }
}
