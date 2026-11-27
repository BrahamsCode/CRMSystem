<?php

namespace App\Services\Promotions;

/**
 * Zona horaria del legacy (crm2-php8: php.ini date.timezone = Asia/Tokyo). Las horas de las reglas,
 * del sorteo y de los envíos se interpretan siempre en esta zona, aunque app.timezone sea otra.
 */
final class LegacyTime
{
    public const ZONE = 'Asia/Tokyo';
}
