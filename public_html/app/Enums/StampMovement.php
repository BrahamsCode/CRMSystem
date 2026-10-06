<?php

namespace App\Enums;

/** Motivo de un movimiento de sellos */
enum StampMovement: int
{
    case Visit = 1;
    case SignupBonus = 2;
    case Exchange = 3;
    case Adjustment = 4;
    case Expiry = 5;
    case CardReset = 6;

    public function label(): string
    {
        return match ($this) {
            self::Visit => 'Visita',
            self::SignupBonus => 'Regalo de alta',
            self::Exchange => 'Canje por cupón',
            self::Adjustment => 'Ajuste manual',
            self::Expiry => 'Vencimiento',
            self::CardReset => 'Tarjeta completada',
        };
    }
}
