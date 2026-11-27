<?php

namespace App\Services\Promotions;

use App\Support\LegacyText;

/**
 * form2Db() de inc/db.php: lo aplican a todo `$_REQUEST` los registros de mail_mag
 * (mail_template_register, mail_template_category_register, test_mail_address_register):
 * 半角カナ → 全角 (mb_convert_kana 'KV') y recorte de espacios 半角/全角. Sin entidades HTML:
 * la columna queda en texto plano y se imprime con {{ }} (salvo el HTML de las plantillas デコメール).
 */
class Form2Db
{
    public static function clean(mixed $value): string
    {
        return is_array($value) || $value === null ? '' : LegacyText::trim(mb_convert_kana((string) $value, 'KV'));
    }
}
