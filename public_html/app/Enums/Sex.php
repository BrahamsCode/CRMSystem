<?php

namespace App\Enums;

/**
 * ISO 5218. El estándar interno documenta 1 y 2; el sistema usa también 0 y 9,
 * como pide la propia norma (ESTANDARES_BD.md §2.1):
 * - 0: el legacy permite dejar el sexo sin marcar (男性 / 女性 / vacío).
 * - 9: los clientes de tipo empresa no tienen sexo.
 */
enum Sex: int
{
    case Unknown = 0;
    case Male = 1;
    case Female = 2;
    case NotApplicable = 9;

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Sin especificar',
            self::Male => 'Hombre',
            self::Female => 'Mujer',
            self::NotApplicable => 'No aplica',
        };
    }

    /** Las opciones que se eligen en un formulario de persona */
    public static function forPeople(): array
    {
        return [self::Male, self::Female, self::Unknown];
    }
}
