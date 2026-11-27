<?php

namespace App\Services\Mypage;

use App\Models\AdminMaster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push a la app al guardar una オススメ con push=1 (system/class/Push.php::exec):
 * GET http://<host>/api/mpa/pushMessage?domain=<dominio>&type=recommend&cd=<cd>
 * con host b.g-api.net si admin_master.push_api_host = '1', si no b-develop.g-api.net.
 * Solo si admin_master.application_use_flg = 1. El legacy escribía la petición en un socket y
 * no esperaba respuesta; aquí es síncrona con un timeout corto y un fallo no corta el guardado.
 */
class RecommendationPush
{
    public const PRODUCTION_HOST = 'b.g-api.net';

    public const DEVELOP_HOST = 'b-develop.g-api.net';

    public const TIMEOUT_SECONDS = 5;

    public function exec(string $domain, string $type, int $masterCd): bool
    {
        $admin = AdminMaster::current();
        if ((int) $admin->application_use_flg !== 1) {
            return false;
        }

        $host = (string) $admin->push_api_host === '1' ? self::PRODUCTION_HOST : self::DEVELOP_HOST;

        try {
            Http::timeout(self::TIMEOUT_SECONDS)->get("http://{$host}/api/mpa/pushMessage", [
                'domain' => $domain,
                'type' => $type,
                'cd' => $masterCd,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Push de マイページ no enviado', ['type' => $type, 'cd' => $masterCd, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
