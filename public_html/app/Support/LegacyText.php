<?php

namespace App\Support;

/**
 * Tratamiento de texto del legacy (inc/kihon.php). Cada `*_register.php` del legacy limpia con una de dos
 * funciones y la BD compartida (app, API, reproductores) depende de ello: la pantalla migrada usa la misma.
 *
 * - `form()`  = `get_form()`: recorta espacios 半角/全角, quita `"`, cambia `'` → `’` y `\` → `￥`. La BD queda en texto
 *   plano (sin entidades). Es la de la mayoría de altas/ediciones (tb_shop, tb_staff…).
 * - `input()` = `get_form2()`: recorta y aplica `htmlspecialchars(…, ENT_COMPAT | ENT_HTML401)`. La BD queda con
 *   `&amp;`, `&lt;`, `&quot;`… Lo usan otros registros y todas las pantallas de confirmación para mostrar.
 * Las pantallas del legacy imprimen las columnas tal cual; las migradas imprimen con `{{ }}` lo guardado con
 * `form()` y con `{!! !!}` lo guardado con `input()` (para no escapar dos veces).
 */
class LegacyText
{
    /** get_form(): recorte de espacios 半角/全角 y sustitución de comillas y barra invertida */
    public static function form(mixed $value): string
    {
        if (is_array($value) || $value === null) {
            return '';
        }

        return str_replace(['"', '\'', '\\'], ['', '’', '￥'], self::trim((string) $value));
    }

    /** get_form2(): recorte de espacios 半角/全角 + htmlspecialchars (comillas dobles, no simples) */
    public static function input(mixed $value): string
    {
        if (is_array($value) || $value === null) {
            return '';
        }

        return htmlspecialchars(self::trim((string) $value), ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }

    /** Solo el recorte de espacios 半角/全角 de get_form2() (para búsquedas) */
    public static function trim(string $value): string
    {
        return (string) preg_replace('/^[ 　]*(.*?)[ 　]*$/us', '$1', $value);
    }

    /**
     * form2Db() (inc/db.php), que el legacy aplica a todo `$_REQUEST`: 半角カナ → 全角 y recorte de espacios.
     * Sin la bandera `s`, como el legacy: un texto con saltos de línea queda sin recortar.
     */
    public static function db(mixed $value): string
    {
        if (is_array($value) || $value === null) {
            return '';
        }
        $value = mb_convert_kana((string) $value, 'KV');

        return (string) (preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', $value) ?? $value);
    }

    /** Aplica input() a cada clave indicada de un array (las que falten quedan como '') */
    public static function inputs(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = self::input($data[$key] ?? '');
        }

        return $out;
    }
}
