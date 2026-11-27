<?php

namespace App\Services\Operaciones;

use Carbon\CarbonImmutable;

/**
 * check_holiday() de inc/calendar.inc: código de festivo japonés de un día (0 = laborable), con las mismas
 * reglas que el legacy (incluidas las ya desactualizadas, p. ej. 天皇誕生日 el 23/12 y sin 山の日): solo
 * colorea los calendarios, así que se conserva la paridad.
 */
class LegacyHolidays
{
    /** get_holiday(): nombre de cada código */
    public const NAMES = ['', '元日', '成人の日', '建国記念日', '春分の日', 'みどりの日', '憲法記念日', 'こどもの日', '海の日', '敬老の日',
        '秋分の日', '体育の日', '文化の日', '勤労感謝の日', '天皇誕生日', '国民の休日', '振替休日'];

    public static function code(int $year, int $month, int $day): int
    {
        $wday = CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'Asia/Tokyo')->dayOfWeek;
        $monday = $wday === 1;
        $md = 100 * $month + $day;

        // 春分日・秋分日 (1980〜2099)
        $spring = 300 + (int) (20.8431 + 0.242194 * ($year - 1980) - (int) (($year - 1980) / 4));
        $autumn = 900 + (int) (23.2488 + 0.242194 * ($year - 1980) - (int) (($year - 1980) / 4));

        return match (true) {
            $md === 101 => 1,
            $md === 102 && $monday => 16,
            $md === 211 => 3,
            $md === 212 && $monday => 16,
            $md === $spring => 4,
            $md === $spring + 1 && $monday => 16,
            $md === 429 => 5,
            $md === 430 && $monday => 16,
            $md === 503 => 6,
            $md === 504 => 15,
            $md === 505 => 7,
            $md === 506 && $monday => 16,
            $md === $autumn => 10,
            $md === $autumn + 1 && $monday => 16,
            $md === 1103 => 12,
            $md === 1104 && $monday => 16,
            $md === 1123 => 13,
            $md === 1124 && $monday => 16,
            $md === 1223 => 14,
            $md === 1224 && $monday => 16,
            $year < 2000 && $md === 115 => 2,
            $year >= 2000 && $md >= 108 && $md <= 114 && $monday => 2,
            $year < 2000 && $md === 116 && $monday => 16,
            $year < 2000 && $md === 1010 => 11,
            $year >= 2000 && $md >= 1008 && $md <= 1014 && $monday => 11,
            $year < 2000 && $md === 1011 && $monday => 16,
            $year >= 1996 && $year < 2003 && $md === 720 => 8,
            $year >= 1996 && $year < 2003 && $md === 721 && $monday => 16,
            $year >= 2003 && $md >= 715 && $md <= 721 && $monday => 8,
            $year < 2003 && $md === 915 => 9,
            $year >= 2003 && $md >= 915 && $md <= 921 && $monday => 9,
            $year < 2003 && $md === 916 && $monday => 16,
            in_array($year, [2009, 2015, 2026], true) && $md === 922 => 16,
            default => 0,
        };
    }
}
