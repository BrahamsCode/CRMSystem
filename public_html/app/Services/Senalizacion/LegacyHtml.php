<?php

namespace App\Services\Senalizacion;

/** Escape de salida del legacy (inc/h.php): htmlspecialchars con ENT_QUOTES y sin doble codificación */
class LegacyHtml
{
    public static function h(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }
}
