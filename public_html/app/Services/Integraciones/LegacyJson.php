<?php

namespace App\Services\Integraciones;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Response;

/**
 * JSON idéntico al del legacy para los contratos externos (api/, mpa_*).
 *
 * El legacy hace `echo json_encode($x)` sin flags:
 * - el japonés sale como secuencias \uXXXX y "/" como "\/";
 * - si la codificación falla (UTF-8 inválido), json_encode devuelve false y
 *   `echo false` no imprime nada: el cuerpo queda vacío;
 * - las filas vienen de pg_fetch_assoc, que entrega todo como texto: los
 *   números salen entre comillas ("cd":"12") y los booleanos de PostgreSQL
 *   como "t"/"f". pgText() reproduce eso a partir de valores de Eloquent.
 *
 * Por eso los controladores de integraciones no usan response()->json(),
 * que aplica otras flags y conserva los tipos.
 */
final class LegacyJson
{
    /** Flags de json_encode del legacy: ninguna */
    public const FLAGS = 0;

    public const CONTENT_TYPE = 'application/json; charset=UTF-8';

    /** json_encode del legacy; '' si falla, como `echo false` */
    public static function encode(mixed $value): string
    {
        $json = json_encode($value, self::FLAGS);

        return $json === false ? '' : $json;
    }

    /**
     * Convierte valores de PHP/Eloquent a lo que habría devuelto pg_fetch_assoc:
     * escalares a texto, null se mantiene, bool a 't'/'f', fechas a 'Y-m-d H:i:s'.
     * Recorre arrays y objetos Arrayable conservando las claves y su orden.
     */
    public static function pgText(mixed $value): mixed
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 't' : 'f',
            is_int($value), is_float($value) => (string) $value,
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof Arrayable => self::pgText($value->toArray()),
            is_array($value) => array_map(self::pgText(...), $value),
            default => $value,
        };
    }

    /**
     * Respuesta HTTP 200 (el legacy responde 200 también en los errores).
     * $jsonHeader = false reproduce las ramas del legacy que hacen echo antes de
     * header(): PHP manda entonces su Content-Type por defecto.
     */
    public static function response(mixed $value, bool $jsonHeader = true): Response
    {
        return new Response(self::encode($value), 200, [
            'Content-Type' => $jsonHeader ? self::CONTENT_TYPE : 'text/html; charset=UTF-8',
        ]);
    }
}
