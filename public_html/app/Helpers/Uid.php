<?php

namespace App\Helpers;

/**
 * Identificador corto para exponer registros en las vistas de los usuarios
 * finales, en lugar del id autoincremental.
 *
 * Ver Doc/database/ESTANDARES_BD.md §5.
 */
class Uid
{
    public static function getUid(): string
    {
        $n = time() . random_int(100000000, 999999999);

        return base_convert($n, 10, 36);
    }
}
