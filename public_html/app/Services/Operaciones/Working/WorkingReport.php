<?php

namespace App\Services\Operaciones\Working;

use App\Models\Operaciones\WorkTime;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * 帳票管理 (working_chouhyou.php) y CSV 勤怠 (csv_download.php / csv_download/csv_file_out.php).
 *
 * Horario de la tienda (tb_shop): `start_work_time` = 'H<>M' (始業), 終業 = 始業 + 8 h + `break_time` min, y
 * `kyuyo_marume` = minutos de redondeo. Hora de entrada "de cálculo" según 出勤状況 y de salida según 出勤区分:
 * - 早出: 始業 − múltiplos enteros del redondeo hasta la entrada (floor). 出勤: 始業. 遅刻: 始業 + múltiplos (ceil).
 * - 退社: 終業. 残業: 終業 + múltiplos completos hasta la salida. 早退: 終業 − múltiplos (ceil) hasta la salida.
 * - Sin 区分 (aún trabajando): horas = ahora − entrada de cálculo.
 * A diferencia del legacy, los valores no se arrastran de un día (o empleado) al siguiente: sin dato, vacío.
 */
class WorkingReport
{
    /** 帳票: la tabla se parte en dos columnas a partir de este día (折り返し日) */
    public const RETURN_DAY = 16;

    public const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    /** Segunda línea del CSV 勤怠 (la primera es el nombre de la tienda) */
    public const CSV_TITLES = ['スタッフID', '名前', '出勤時刻', '退勤時刻', '勤務時間', '休憩回数', '出勤状況', '出勤区分'];

    /**
     * Entrada y salida de cálculo y horas trabajadas de un día.
     *
     * @return array{start: ?int, end: ?int, hours: ?float}
     */
    public static function figures(?Shop $shop, CarbonImmutable $date, ?string $in, ?string $out, ?string $flag, ?string $kubun, CarbonImmutable $now): array
    {
        $none = ['start' => null, 'end' => null, 'hours' => null];
        [$hour, $minute] = array_map('intval', array_pad(explode('<>', (string) $shop?->start_work_time), 2, 0));
        $break = (float) $shop?->break_time;
        $unit = max(1.0, (float) $shop?->kyuyo_marume) * 60; // el legacy dividía por 0 sin 給与丸め
        $day = $date->setTimezone(LegacyMonth::TIMEZONE)->startOfDay();
        $startWork = $day->addHours($hour)->addMinutes($minute)->getTimestamp();
        $endWork = (int) $day->addHours($hour + 8)->addSeconds((int) round(($minute + $break) * 60))->getTimestamp();
        $in = (int) $in;
        $out = (int) $out;

        $start = match ($flag) {
            '早出' => (int) ($startWork - floor(($startWork - $in) / $unit) * $unit),
            '出勤' => $startWork,
            '遅刻' => (int) ($startWork + ceil(($in - $startWork) / $unit) * $unit),
            default => null,
        };
        if ($start === null) {
            return $none;
        }

        $end = match ($kubun) {
            '退社' => $endWork,
            '残業' => (int) ($endWork + floor(($out - $endWork) / $unit) * $unit),
            '早退' => (int) ($endWork - ceil(($endWork - $out) / $unit) * $unit),
            default => null,
        };
        $hours = match (true) {
            (string) $kubun === '' => (float) ($now->getTimestamp() - $start) / 3600,
            $end !== null => (float) ($end - $start) / 3600,
            default => null,
        };

        return ['start' => $start, 'end' => $end, 'hours' => $hours];
    }

    /**
     * Las filas del 帳票 de un empleado en un mes y sus totales (en minutos).
     *
     * @return array{days: array<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public static function month(?Shop $shop, ?WorkTime $row, LegacyMonth $month, CarbonImmutable $now): array
    {
        $days = [];
        $totals = ['working' => 0, 'overtime' => 0, 'holiday' => 0.0, 'attendances' => 0, 'absenteeism' => 0, 'paidUseDays' => 0];

        foreach (range(1, $month->daysInMonth()) as $d) {
            $date = $month->day($d);
            $w = (int) $date->dayOfWeek;
            $in = $row?->getDayValue('syukkin_time', $d);
            $out = $row?->getDayValue('taikin_time', $d);
            $breaks = $row ? count($row->getDayList('gaisyutu_time', $d)) : 0;
            $fig = $row ? self::figures($shop, $date, $in, $out, $row->getDayValue('syukkin_flg', $d), $row->getDayValue('syukkin_kubun', $d), $now)
                : ['start' => null, 'end' => null, 'hours' => null];

            $attendance = '---';
            if (self::filled($in)) {
                $attendance = self::hm((int) $in).($fig['start'] ? '('.self::hm($fig['start']).')' : '');
            }
            $returnOffice = '---';
            if (self::filled($out)) {
                $returnOffice = self::hm((int) $out).($fig['end'] !== null ? '('.self::hm($fig['end']).')' : '');
            }

            $days[$d] = [
                'day' => $d,
                'w' => self::WEEKDAYS[$w],
                'color' => $w === 0 ? 'red' : ($w === 6 ? 'blue' : ''),
                'attendance' => $attendance,
                'return_office' => $returnOffice,
            ];

            // 休出勤合計: el legacy suma 休憩回数 × 休憩時間 de la tienda (se reproduce tal cual)
            $totals['holiday'] += $breaks * (float) $shop?->break_time;
            if (self::filled($in) && self::filled($out)) {
                // 就業時間合計: salida − entrada en minutos (el legacy restaba solo H:i y daba negativo al pasar la medianoche)
                $totals['working'] += intdiv((int) $out, 60) - intdiv((int) $in, 60);
            }
            if (self::filled($in)) {
                $totals['attendances']++;
            }
        }

        return ['days' => $days, 'totals' => $totals];
    }

    /** min2HourMin(): minutos → 'HH:MM' ('00:00' si no es positivo) */
    public static function hourMin(int|float $minutes): string
    {
        if ((int) $minutes <= 0) {
            return '00:00';
        }

        return sprintf('%02d', (int) floor($minutes / 60)).':'.sprintf('%02d', (int) $minutes % 60);
    }

    /**
     * Filas del CSV 勤怠 de un día: スタッフID, 名前, 出勤時刻, 退勤時刻, 勤務時間, 休憩回数, 出勤状況, 出勤区分
     * (horas de cálculo, no las marcadas). $day = null (sin día en `dd`): sin valores, como el legacy.
     *
     * @param  Collection<int, Staff>  $staff
     * @param  Collection<int, WorkTime>  $rows  por staff_cd
     * @return list<list<string>>
     */
    public static function csvRows(?Shop $shop, Collection $staff, Collection $rows, LegacyMonth $month, ?int $day, CarbonImmutable $now): array
    {
        $lines = [];
        foreach ($staff as $member) {
            $row = $rows->get($member->cd);
            if (! $row) {
                $lines[] = [(string) $member->id, (string) $member->name, '', '', '', '', '', ''];

                continue;
            }

            $value = fn (string $prefix) => $day ? $row->getDayValue($prefix, $day) : null;
            $in = $value('syukkin_time');
            $out = $value('taikin_time');
            $flag = (string) $value('syukkin_flg');
            $kubun = (string) $value('syukkin_kubun');
            $fig = $day ? self::figures($shop, $month->day($day), $in, $out, $flag, $kubun, $now) : ['start' => null, 'end' => null, 'hours' => null];

            $lines[] = [
                (string) $member->id,
                (string) $member->name,
                self::filled($in) && $fig['start'] !== null ? self::hm($fig['start']) : '',
                self::filled($out) && $fig['end'] !== null ? self::hm($fig['end']) : '',
                $fig['hours'] !== null && $fig['hours'] != 0 ? number_format($fig['hours'], 1) : '',
                (string) ($day ? count($row->getDayList('gaisyutu_time', $day)) : 0),
                $flag,
                $kubun,
            ];
        }

        return $lines;
    }

    private static function hm(int $ts): string
    {
        return CarbonImmutable::createFromTimestamp($ts, LegacyMonth::TIMEZONE)->format('H:i');
    }

    private static function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
