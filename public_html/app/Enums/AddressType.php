<?php

namespace App\Enums;

/**
 * Tipo de dirección de correo (アドレス区分).
 *
 * Es lo que alimenta el desglose «PC / DoCoMo / au / SoftBank / Sin identificar»
 * de los resultados de búsqueda. En el legacy se guarda la etiqueta en texto;
 * aquí va como smallint, según el estándar §4.
 */
enum AddressType: int
{
    case Pc = 1;          // パソコン
    case Docomo = 2;      // DoCoMo
    case Au = 3;          // AU
    case Softbank = 4;    // Softbank
    case Otros = 5;       // その他

    public function label(): string
    {
        return match ($this) {
            self::Pc => 'PC',
            self::Docomo => 'DoCoMo',
            self::Au => 'au',
            self::Softbank => 'SoftBank',
            self::Otros => 'Otros',
        };
    }
}
