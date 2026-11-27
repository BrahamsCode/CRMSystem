<?php

namespace App\Services\Operaciones\Working;

use App\Models\Operaciones\ShopWork;
use App\Models\Operaciones\WorkTime;
use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;

/**
 * Cálculos de スタッフ一覧 (working_list.php) y スタッフ勤怠集計一覧 (working_total_list.php).
 *
 * - Jornada: antes de la hora de corte (04:59:59 en working_list, 04:40:00 en working_total_list) el día que se
 *   muestra es el anterior.
 * - 労働時間 de un día: entrada redondeada al siguiente múltiplo de 15 min y salida al anterior
 *   (utAfterKizamiTime / utBeforeKizamiTime / aTimeBetweenHours; en el legacy esas funciones usaban `['kizami']`
 *   en lugar de `$kizami` y daban un error fatal en PHP 8 en cuanto había una marca).
 */
class WorkingTotals
{
    /** 何分刻み de working_list.php */
    public const KIZAMI_MINUTES = 15;

    /** Corte de jornada de working_list.php ($eigyo_end_time) */
    public const MONTHLY_CUTOFF = '04:59:59';

    /** Corte de jornada de working_total_list.php */
    public const TOTALS_CUTOFF = '04:40:00';

    /** 出勤状況 que cuenta como día trabajado en 今月の出勤日数 */
    public const ATTENDED_FLAG = '出勤';

    /** Día de trabajo: si la hora es menor que el corte ('H:i:s'), el día anterior */
    public static function businessDate(CarbonImmutable $now, string $cutoff): CarbonImmutable
    {
        $now = $now->setTimezone(LegacyMonth::TIMEZONE);

        return ($now->format('H:i:s') < $cutoff ? $now->subDay() : $now)->startOfDay();
    }

    /** Entrada redondeada al siguiente múltiplo de 15 min (utAfterKizamiTime); 0 sin marca */
    public static function roundUp(?string $time): int
    {
        if (! self::filled($time)) {
            return 0;
        }
        $ts = (int) $time;
        $min = (int) CarbonImmutable::createFromTimestamp($ts, LegacyMonth::TIMEZONE)->format('i');
        if ($min % self::KIZAMI_MINUTES === 0) {
            return $ts;
        }

        return $ts + ((int) ceil($min / self::KIZAMI_MINUTES) * self::KIZAMI_MINUTES - $min) * 60;
    }

    /** Salida redondeada al múltiplo de 15 min anterior (utBeforeKizamiTime); 0 sin marca */
    public static function roundDown(?string $time): int
    {
        if (! self::filled($time)) {
            return 0;
        }
        $ts = (int) $time;
        $min = (int) CarbonImmutable::createFromTimestamp($ts, LegacyMonth::TIMEZONE)->format('i');
        if ($min % self::KIZAMI_MINUTES === 0) {
            return $ts;
        }

        return $ts + ((int) floor($min / self::KIZAMI_MINUTES) * self::KIZAMI_MINUTES - $min) * 60;
    }

    /**
     * 労働時間 en segundos de una entrada y salida (0 si falta la salida o es anterior a la entrada, como
     * aTimeBetweenHours).
     */
    public static function workedSeconds(?string $in, ?string $out): int
    {
        $begin = self::roundUp($in);
        $end = self::roundDown($out);

        return $begin < $end ? $end - $begin : 0;
    }

    /**
     * Una fila de スタッフ一覧 para el día $day: horas formateadas, 労働時間, 休憩回数, 出勤状況 y 区分.
     *
     * @return array{in: ?string, out: ?string, hours: ?int, mins: ?int, breaks: ?int, flag: ?string, kubun: ?string}
     */
    public static function dayRow(?WorkTime $row, int $day): array
    {
        $in = $row?->getDayValue('syukkin_time', $day);
        $out = $row?->getDayValue('taikin_time', $day);
        $attended = self::filled($in);
        $secs = $attended ? self::workedSeconds($in, $out) % 86400 : 0; // aTimeBetweenHours separa los días

        return [
            'in' => $attended ? self::clock($in, 'H時i分') : null,
            'out' => self::filled($out) ? self::clock($out, 'H時i分') : null,
            'hours' => $attended ? intdiv($secs, 3600) : null,
            'mins' => $attended ? intdiv($secs % 3600, 60) : null,
            'breaks' => $attended ? count($row->getDayList('gaisyutu_time', $day)) : null,
            'flag' => $attended ? (string) $row->getDayValue('syukkin_flg', $day) : null,
            'kubun' => $row?->getDayValue('syukkin_kubun', $day),
        ];
    }

    /**
     * Totales del mes de un empleado (working_total_list.php).
     * - attendanceDays: días con 出勤状況 = 出勤 (como el legacy: 遅刻/早出 no cuentan).
     * - workMinutes: suma de 労働時間 de los días con entrada (el legacy pintaba otra vez los días).
     * - closedDays: 休日 de 営業日設定 de la tienda del empleado en ese mes (el legacy leía la primera fila de toda
     *   tb_shop_work, de cualquier tienda y mes).
     * - absenceDays: días del mes − 休日 − attendanceDays (欠勤日数).
     * - outTime / backTime: primer 外出 / 帰社 del día $day ('H時i分').
     *
     * @return array{attendanceDays: int, workMinutes: int, closedDays: int, absenceDays: int, outTime: ?string, backTime: ?string}
     */
    public static function month(?WorkTime $row, ?ShopWork $shopWork, LegacyMonth $month, int $day): array
    {
        $daysInMonth = $month->daysInMonth();
        $attendance = 0;
        $seconds = 0;
        $closed = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            if ($row?->getDayValue('syukkin_flg', $d) === self::ATTENDED_FLAG) {
                $attendance++;
            }
            if ($row && self::filled($row->getDayValue('syukkin_time', $d))) {
                $seconds += self::workedSeconds($row->getDayValue('syukkin_time', $d), $row->getDayValue('taikin_time', $d));
            }
            if ($shopWork?->getDayValue('syukkin_time', $d) === ShopWork::CLOSED) {
                $closed++;
            }
        }

        $first = fn (string $prefix) => ($list = $row?->getDayList($prefix, $day)) && self::filled($list[0])
            ? self::clock($list[0], 'H時i分') : null;

        return [
            'attendanceDays' => $attendance,
            'workMinutes' => intdiv($seconds, 60),
            'closedDays' => $closed,
            'absenceDays' => $daysInMonth - $closed - $attendance,
            'outTime' => $first('gaisyutu_time'),
            'backTime' => $first('kisha_time'),
        ];
    }

    /** Hora de un timestamp del legacy en hora de Japón */
    public static function clock(string $time, string $format): string
    {
        return CarbonImmutable::createFromTimestamp((int) $time, LegacyMonth::TIMEZONE)->format($format);
    }

    private static function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
