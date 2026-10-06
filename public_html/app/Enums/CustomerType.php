<?php

namespace App\Enums;

enum CustomerType: int
{
    case Person = 1;
    case Company = 2;

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Persona',
            self::Company => 'Empresa',
        };
    }
}
