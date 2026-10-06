<?php

namespace App\Enums;

enum CustomCategoryType: int
{
    /** Un registro por cliente: el valor vive en customers.custom_data */
    case Normal = 1;

    /** Varios registros por cliente (ej. varias mascotas): tabla custom_values */
    case Multiple = 2;

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Multiple => 'Múltiple',
        };
    }
}
