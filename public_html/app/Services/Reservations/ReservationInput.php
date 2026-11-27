<?php

namespace App\Services\Reservations;

use App\Support\LegacyText;

/**
 * Limpieza de entrada de las pantallas de reservas que en el legacy no usan get_form()/get_form2(),
 * sino `array_walk_recursive($_REQUEST, 'form2Db')` (inc/db.php): setsubi_register, yoyaku_koumoku_category_register,
 * free_yoyaku_register, yoyaku_form_config y yoyaku_comment_config.
 *
 * form2Db(): 半角カナ → 全角 (mb_convert_kana 'KV') y recorte de espacios 半角/全角 (el pg_escape_string lo hace PDO).
 * La BD queda en texto plano, sin entidades: se imprime con `{{ }}`.
 */
final class ReservationInput
{
    public static function form2Db(mixed $value): string
    {
        if (is_array($value) || $value === null) {
            return '';
        }

        return LegacyText::trim(mb_convert_kana((string) $value, 'KV'));
    }
}
