<?php

namespace App\Http\Middleware\Integraciones;

use App\Services\Integraciones\ToolApi\ToolApiOutput;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * system/tool/api/checkKey.php: el parámetro `key` (GET o POST) debe ser igual a CRM_API_KEY
 * (`hash_equals`). Sin clave configurada, sin `key` o distinta: respuesta vacía con 200, como el `exit` del legacy.
 */
class CheckCrmApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.crm.api_key');
        $key = $request->input('key');

        // El legacy pasaba form2Db() a $_REQUEST antes de comparar
        if ($expected === '' || ! is_string($key) || ! hash_equals($expected, ToolApiOutput::form2Db($key))) {
            return ToolApiOutput::empty();
        }

        return $next($request);
    }
}
