<?php

namespace App\Enums;

/** Motivo de un movimiento de puntos */
enum PointMovement: int
{
    case Purchase = 1;
    case Signup = 2;
    case Referral = 3;
    case Referred = 4;
    case Exchange = 5;
    case Use = 6;
    case Adjustment = 7;
    case Expiry = 8;

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Compra',
            self::Signup => 'Alta de socio',
            self::Referral => 'Por referir',
            self::Referred => 'Por ser referido',
            self::Exchange => 'Canje por cupón',
            self::Use => 'Uso en caja',
            self::Adjustment => 'Ajuste manual',
            self::Expiry => 'Vencimiento',
        };
    }
}
