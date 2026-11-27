<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use App\Support\GApi;
use Illuminate\Support\Facades\Http;

/**
 * Push.php::exec del legacy: GET /api/mpa/pushMessage?domain=&type=&cd= a b.g-api.net
 * (b-develop.g-api.net si admin_master.push_api_host <> '1'), solo con la app activa
 * (application_use_flg = 1). Mismo contrato; si el servidor no responde se sigue sin error, como el legacy.
 * Pasa por el interruptor común GApi (CRM_GAPI_ENABLED, NUCLEO.md §9): apagado fuera de producción, solo se anota en el log.
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
        $url = "http://{$host}/api/mpa/pushMessage";
        $query = ['domain' => $this->domain->domain(), 'type' => $type, 'cd' => $masterCd];

        try {
            GApi::send($url, fn () => Http::timeout(30)->get($url, $query), $query);
        } catch (\Throwable) {
            // Igual que el legacy: el fallo del servidor de push no detiene el envío
        }
    }
}
