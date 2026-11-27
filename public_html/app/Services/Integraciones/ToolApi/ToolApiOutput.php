<?php

namespace App\Services\Integraciones\ToolApi;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Salida de `system/tool/api/**` y de CTI: el legacy arma el "JSON" concatenando texto, sin escapar los
 * valores (comillas o saltos de línea en los datos lo rompen), con espacios propios y Content-Type por
 * defecto de PHP (text/html). Aquí se reproduce igual, byte a byte (fixtures en tests/Fixtures/integraciones/tool).
 *
 * Las filas se leen como las daba pg_fetch_*: cada columna como texto de PostgreSQL (`col::text`), NULL → "".
 */
class ToolApiOutput
{
    public const CONTENT_TYPE = 'text/html; charset=UTF-8';

    /** form2Db() de inc/db.php: kana de ancho medio a completo y recorte de espacios 半角/全角 (el escape SQL lo hacen los bindings) */
    public static function form2Db(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return (string) preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', mb_convert_kana((string) $value, 'KV'));
    }

    /** h() de inc/h.php (ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, sin doble codificación) */
    public static function h(mixed $value): string
    {
        return is_array($value) ? '' : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    /** '"k":"v"' por columna, sin escapar; $encode = rawurlencode de cada valor */
    public static function pairs(array $row, bool $encode = false): array
    {
        $pairs = [];
        foreach ($row as $key => $value) {
            $pairs[] = '"'.$key.'":"'.($encode ? rawurlencode((string) $value) : (string) $value).'"';
        }

        return $pairs;
    }

    /** '{ "k":"v","k2":"v2" }' */
    public static function object(array $row, bool $encode = false): string
    {
        return '{ '.implode(',', self::pairs($row, $encode)).' }';
    }

    /**
     * Sobre común: { "status":"…", "max":"…", "result":[ … ] } (o con llaves), y su variante JSONP
     * `<h(callback)>({ … })` cuando data_type=jsonp.
     */
    public static function envelope(Request $request, string $status, int|string $max, string $data, bool $list = true): Response
    {
        [$open, $close] = $list ? ['[', ']'] : ['{', '}'];

        return self::jsonp($request, '{ "status":"'.$status.'", "max":"'.$max.'", "result":'.$open.' '.$data.' '.$close.' }');
    }

    /** $body tal cual, o `<h(callback)>($body)` con data_type=jsonp */
    public static function jsonp(Request $request, string $body): Response
    {
        if ($request->input('data_type') === 'jsonp') {
            $body = self::h($request->input('callback')).'('.$body.')';
        }

        return self::text($body);
    }

    public static function text(string $body, int $status = 200, array $headers = []): Response
    {
        return new Response($body, $status, $headers + ['Content-Type' => self::CONTENT_TYPE]);
    }

    /** Respuesta vacía con la que el legacy terminaba (exit) */
    public static function empty(): Response
    {
        return self::text('');
    }

    /**
     * Lista de columnas `alias.col::text AS col` de una tabla, en el orden de `alias.*` (attnum),
     * para leer las filas como pg_fetch_array.
     */
    public static function textColumns(string $table, string $alias): string
    {
        static $cache = [];
        $columns = $cache[$table] ??= array_column(Schema::getColumns($table), 'name');

        return implode(', ', array_map(fn ($c) => "{$alias}.\"{$c}\"::text AS \"{$c}\"", $columns));
    }

    /**
     * SELECT y filas como arrays de texto (NULL → ''); null si la consulta falla (el legacy seguía con
     * el resultado false: NO_DATA).
     *
     * @return list<array<string, string>>|null
     */
    public static function rows(string $sql, array $bindings = []): ?array
    {
        try {
            // En un savepoint: si la consulta falla, la transacción que la envuelve sigue usable
            $rows = DB::transaction(fn () => DB::select($sql, $bindings));
        } catch (\Illuminate\Database\QueryException) {
            return null;
        }

        return array_map(fn ($row) => array_map(fn ($v) => $v === null ? '' : (string) $v, (array) $row), $rows);
    }
}
