<?php

namespace App\Enums;

/** Vigencia del cupón desde que se entrega (有効期限) */
enum CouponValidity: int
{
    case OneWeek = 1;   // 発行から1週間
    case OneMonth = 2;   // 発行から1ヶ月
    case ThreeMonths = 3;   // 発行から3ヶ月
    case Days = 4;   // 発行から○○日間
    case UntilDate = 5;   // 指定日付まで

    public function label(): string
    {
        return match ($this) {
            self::OneWeek => '1 semana desde la entrega',
            self::OneMonth => '1 mes desde la entrega',
            self::ThreeMonths => '3 meses desde la entrega',
            self::Days => 'N días desde la entrega',
            self::UntilDate => 'Hasta una fecha',
        };
    }

    /** Vencimiento de un cupón entregado en $issuedAt */
    public function expiresAt(\Carbon\CarbonInterface $issuedAt, ?int $days, ?\Carbon\CarbonInterface $until): ?\Carbon\CarbonInterface
    {
        return match ($this) {
            self::OneWeek => $issuedAt->copy()->addWeek()->endOfDay(),
            self::OneMonth => $issuedAt->copy()->addMonth()->endOfDay(),
            self::ThreeMonths => $issuedAt->copy()->addMonths(3)->endOfDay(),
            self::Days => $days ? $issuedAt->copy()->addDays($days)->endOfDay() : null,
            self::UntilDate => $until?->copy()->endOfDay(),
        };
    }
}
