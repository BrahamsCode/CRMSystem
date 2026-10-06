<?php

namespace App\Enums;

/**
 * Ancho del control de un campo personalizado (スタイルクラス名).
 *
 * El legacy guarda el nombre de la clase CSS (`inputS`, `writeArea`…). Aquí va
 * como smallint y la clase se resuelve al pintar, para no atar la base de datos
 * a los nombres de clase de la hoja de estilos.
 */
enum FieldSize: int
{
    case Default = 1;   // inputD
    case Dos = 2;       // input3S  — 2 caracteres
    case Tres = 3;      // input2S  — 3 caracteres
    case Seis = 4;      // inputS   — 6 caracteres
    case Nueve = 5;     // inputM   — 9 caracteres
    case Dieciseis = 6; // input2M  — 16 caracteres
    case Veinticuatro = 7; // inputL — 24 caracteres
    case Cuarenta = 8;  // writeArea — 40 caracteres
    case Casilla = 9;   // checkBtn  — para casillas y botones de opción
    case Seleccion = 10; // selectBox — para desplegables

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Por defecto',
            self::Dos => 'Unos 2 caracteres',
            self::Tres => 'Unos 3 caracteres',
            self::Seis => 'Unos 6 caracteres',
            self::Nueve => 'Unos 9 caracteres',
            self::Dieciseis => 'Unos 16 caracteres',
            self::Veinticuatro => 'Unos 24 caracteres',
            self::Cuarenta => 'Unos 40 caracteres',
            self::Casilla => 'Para casillas y opciones',
            self::Seleccion => 'Para desplegables',
        };
    }

    /** Clase de Tailwind con la que se pinta el control */
    public function cssClass(): string
    {
        return match ($this) {
            self::Default => 'w-full',
            self::Dos => 'w-16',
            self::Tres => 'w-20',
            self::Seis => 'w-32',
            self::Nueve => 'w-44',
            self::Dieciseis => 'w-64',
            self::Veinticuatro => 'w-80',
            self::Cuarenta, self::Casilla, self::Seleccion => 'w-full',
        };
    }
}
