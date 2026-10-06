<?php

namespace App\Enums;

enum RankType: int
{
    case Amount = 1;
    case Visits = 2;

    public function label(): string
    {
        return match ($this) {
            self::Amount => 'Por importe de compra',
            self::Visits => 'Por número de visitas',
        };
    }
}
