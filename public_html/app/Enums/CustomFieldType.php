<?php

namespace App\Enums;

/**
 * Tipos de campo de los formularios personalizados.
 *
 * Pendiente: la documentación del legacy dice «14 tipos» pero enumera 15.
 * Aquí están los 15 de la lista; hay que confirmar cuál sobra.
 */
enum CustomFieldType: int
{
    case Text = 1;
    case Email = 2;
    case Alphanumeric = 3;
    case Numeric = 4;
    case Textarea = 5;
    case Checkbox = 6;
    case Select = 7;
    case Radio = 8;
    case Year = 9;
    case YearMonth = 10;
    case YearMonthDay = 11;
    case MonthDay = 12;
    case ReferenceDate = 13;
    case Table = 14;
    case Menu = 15;

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Email => 'Email',
            self::Alphanumeric => 'Alfanumérico',
            self::Numeric => 'Numérico',
            self::Textarea => 'Área de texto',
            self::Checkbox => 'Casillas',
            self::Select => 'Selección',
            self::Radio => 'Botones de opción',
            self::Year => 'Año',
            self::YearMonth => 'Año y mes',
            self::YearMonthDay => 'Año, mes y día',
            self::MonthDay => 'Mes y día',
            self::ReferenceDate => 'Fecha de referencia',
            self::Table => 'Tabla',
            self::Menu => 'Menú',
        };
    }

    /** Los tipos que necesitan una lista de opciones configurable */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox], true);
    }
}
