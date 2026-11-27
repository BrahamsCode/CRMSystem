<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Interruptor común de las llamadas a la API externa de la app y del soporte (g-api.net, producción o desarrollo
 * según admin_master.push_api_host). Fuera de producción está apagado (`config('crm.gapi.enabled')`,
 * CRM_GAPI_ENABLED): no sale ninguna petición y solo se registra en el log lo que se habría enviado, para que
 * :8081, las BD de desarrollo y las pruebas no avisen al servidor real (NUCLEO.md §9).
 *
 * Uso:
 *   GApi::get("http://{$host}/api/mpa/setStoreTel", ['domain' => …]);          // null si está apagado
 *   GApi::send('pushMessage', fn () => Http::timeout(3)->get($url, $query));  // cualquier petición
 *   if (GApi::enabled()) { … }
 * Las pruebas que comprueben la llamada con Http::fake() activan el interruptor: config(['crm.gapi.enabled' => true]).
 */
final class GApi
{
    public static function enabled(): bool
    {
        return (bool) config('crm.gapi.enabled');
    }

    /** GET con timeout; si está apagado, registra y devuelve null */
    public static function get(string $url, array $query = [], int $timeout = 3): ?Response
    {
        return self::send($url, fn () => Http::timeout($timeout)->get($url, $query), $query);
    }

    /** POST de formulario con timeout; si está apagado, registra y devuelve null */
    public static function postForm(string $url, array $data = [], int $timeout = 3): ?Response
    {
        return self::send($url, fn () => Http::asForm()->timeout($timeout)->post($url, $data), $data);
    }

    /**
     * Ejecuta la petición solo con el interruptor encendido.
     *
     * @template T
     *
     * @param  callable(): T  $request
     * @return T|null
     */
    public static function send(string $url, callable $request, array $payload = []): mixed
    {
        if (! self::enabled()) {
            Log::info('g-api apagado (CRM_GAPI_ENABLED): no se envía', ['url' => $url, 'payload' => self::mask($payload)]);

            return null;
        }

        return $request();
    }

    /** Las claves no van al log */
    private static function mask(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && preg_match('/pass|token|secret/i', $key)) {
                $payload[$key] = '***';
            }
        }

        return $payload;
    }
}
