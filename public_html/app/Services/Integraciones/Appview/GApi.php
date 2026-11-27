<?php

namespace App\Services\Integraciones\Appview;

use App\Support\GApi as GApiSwitch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * API externa de la app (g-api.net) que leen appview/photo_gallery.php y visit_history.php con
 * file_get_contents (GET, 3 s): b.g-api.net si admin_master.push_api_host = 1, si no b-develop.g-api.net.
 * Sin fila en admin_master no se llama. Pasa por el interruptor común App\Support\GApi (NUCLEO.md §9):
 * apagado, no sale ninguna petición y la vista ve una respuesta vacía (como si la API no contestara). La respuesta va decodificada como objetos (json_decode del legacy).
 */
class GApi
{
    public static function get(string $path, string $query): mixed
    {
        $master = DB::table('admin_master')->where('cd', 1)->first(['push_api_host']);
        if (! $master) {
            return null;
        }
        $domain = self::looseEqualsOne($master->push_api_host) ? 'b.g-api.net' : 'b-develop.g-api.net';

        try {
            // send() y no get(): con query [] Http borraría la query que ya va en la URL
            $url = 'http://'.$domain.$path.'?'.$query;
            $body = GApiSwitch::send($url, fn () => Http::timeout(3)->get($url))?->body() ?? '';
        } catch (Throwable) {
            $body = '';
        }

        return json_decode($body);
    }

    /** `(is_string($v) && !is_numeric($v) ? (float) $v : $v) == 1` del legacy */
    public static function looseEqualsOne(mixed $value): bool
    {
        return (is_string($value) && ! is_numeric($value) ? (float) $value : $value) == 1;
    }

    /** ¿Trae `status` = "ok" y una lista `data` no vacía? (condición de las vistas) */
    public static function hasData(mixed $data): bool
    {
        return is_object($data) && isset($data->status) && $data->status == 'ok' && is_array($data->data ?? null) && count($data->data) > 0;
    }
}
