<?php

namespace App\Enums;

/** El estado «eliminado» del legacy lo cubre deleted_at, no este campo. */
enum CustomerStatus: int
{
    case Withdrawn = 0;
    case Registered = 1;

    public function label(): string
    {
        return match ($this) {
            self::Withdrawn => 'Dado de baja',
            self::Registered => 'Registrado',
        };
    }
}
