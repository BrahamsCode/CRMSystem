<?php

namespace App\Enums;

/** Dónde se muestra un campo personalizado */
enum DisplayScope: int
{
    case Both = 1;
    case AdminOnly = 2;
    case MobileOnly = 3;

    public function label(): string
    {
        return match ($this) {
            self::Both => 'Admin y móvil',
            self::AdminOnly => 'Solo admin',
            self::MobileOnly => 'Solo móvil',
        };
    }
}
