<?php

namespace App\Enums;

/** Tipo de descuento del cupón. Mismos códigos que `coupons.type` del estándar. */
enum CouponDiscountType: int
{
    case Percentage = 1;
    case Fixed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Porcentaje',
            self::Fixed => 'Importe fijo',
        };
    }

    /** Texto del descuento: «10 %» o «¥1,500» */
    public function format(int $value): string
    {
        return match ($this) {
            self::Percentage => $value . ' %',
            self::Fixed => \App\Support\Money::format($value),
        };
    }
}
