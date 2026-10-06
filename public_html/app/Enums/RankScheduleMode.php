<?php

namespace App\Enums;

/** Cuándo se ejecuta la asignación automática de rangos */
enum RankScheduleMode: int
{
    case ByDate = 1;
    case ByWeekday = 2;

    public function label(): string
    {
        return match ($this) {
            self::ByDate => 'Meses y días concretos',
            self::ByWeekday => 'Días de la semana',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ByDate => 'Se ejecuta en los meses y días que elijas.',
            self::ByWeekday => 'Se ejecuta cada semana en los días que elijas.',
        };
    }
}
