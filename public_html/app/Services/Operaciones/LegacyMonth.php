<?php

namespace App\Services\Operaciones;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * El mes como lo guarda el legacy, sin el mktime() que daba 1970 (PENDING 1b.1):
 * - tb_work_time / tb_staff_shift: `syukkinbi` = epoch del día 1 a las 0:00 (hora de Japón), en texto.
 * - tb_shop_work: `syukkinbi` = 'Y/n' ('2026/1', sin cero a la izquierda).
 */
final class LegacyMonth
{
    public const TIMEZONE = 'Asia/Tokyo';

    private function __construct(public readonly CarbonImmutable $start)
    {
    }

    public static function of(int $year, int $month): self
    {
        if ($month < 1 || $month > 12 || $year < 1971 || $year > 9999) {
            throw new InvalidArgumentException("Mes no válido: {$year}/{$month}");
        }

        return new self(CarbonImmutable::create($year, $month, 1, 0, 0, 0, self::TIMEZONE));
    }

    public static function current(): self
    {
        $now = CarbonImmutable::now(self::TIMEZONE);

        return self::of($now->year, $now->month);
    }

    /** Desde el `syukkinbi` epoch de tb_work_time / tb_staff_shift */
    public static function fromEpoch(int|string $epoch): self
    {
        $date = CarbonImmutable::createFromTimestamp((int) $epoch, self::TIMEZONE);

        return self::of($date->year, $date->month);
    }

    /** Desde el `syukkinbi` 'Y/n' de tb_shop_work */
    public static function fromSlash(string $value): self
    {
        if (! preg_match('#^(\d{4})/(\d{1,2})$#', trim($value), $m)) {
            throw new InvalidArgumentException("syukkinbi no válido: {$value}");
        }

        return self::of((int) $m[1], (int) $m[2]);
    }

    /** `syukkinbi` de tb_work_time / tb_staff_shift */
    public function epoch(): string
    {
        return (string) $this->start->getTimestamp();
    }

    /** `syukkinbi` de tb_shop_work */
    public function slash(): string
    {
        return $this->start->format('Y/n');
    }

    public function daysInMonth(): int
    {
        return $this->start->daysInMonth;
    }

    public function day(int $day): CarbonImmutable
    {
        if ($day < 1 || $day > $this->daysInMonth()) {
            throw new InvalidArgumentException("Día fuera del mes: {$day}");
        }

        return $this->start->setDay($day);
    }

    public function previous(): self
    {
        $prev = $this->start->subMonthNoOverflow();

        return self::of($prev->year, $prev->month);
    }

    public function next(): self
    {
        $next = $this->start->addMonthNoOverflow();

        return self::of($next->year, $next->month);
    }
}
