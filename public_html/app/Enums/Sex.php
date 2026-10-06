<?php

namespace App\Enums;

/** ISO 5218 */
enum Sex: int
{
    case Male = 1;
    case Female = 2;

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Hombre',
            self::Female => 'Mujer',
        };
    }
}
