<?php

namespace App\Services\Operaciones;

use Carbon\CarbonImmutable;

/**
 * Filas de los CSV de 勤怠 (kintai/csv_out.php y monthly_csv_out.php). Una fila 出勤/退勤 por jornada con 総時間
 * y 分換算, y una fila por cada pareja 外出/戻り. Las jornadas de descanso (欠勤, 有給休暇…) no salen.
 * Los textos llevan delante un tabulador dentro de las comillas (así Excel no los convierte), como el legacy.
 *
 * Corrección: el legacy decidía si sumar 24 h a 外出/戻り mirando la última marca de 出勤/退勤 (variable
 * reciclada); aquí cada hora usa su propio día.
 */
class KintaiCsv
{
    /**
     * CSV diario de la tienda. Sin jornadas, el legacy no escribía nada (ni la cabecera).
     *
     * @return list<list<string>>
     */
    public static function daily(array $entries, CarbonImmutable $day): array
    {
        if ($entries === []) {
            return [];
        }

        $date = $day->format('m/d');
        $rows = [[$day->format('Y/m/d').'の勤怠情報'], ['日付', '名前', '社員番号', '出勤', '外出', '戻り', '退勤', '備考', '部署', '区分', '総時間', '分換算']];

        foreach ($entries as $entry) {
            if (KintaiService::isRestDay($entry['status'])) {
                continue;
            }
            [$in, $out, $total, $minutes] = self::workTimes($entry, $day->day);
            [$name, $id, $unit, $section] = [self::t($entry['name']), self::t($entry['id']), self::t($entry['unit']), self::t($entry['section'])];

            $rows[] = [$date, $name, $id, self::t($in), '', '', self::t($out), self::t($entry['bikou']), $unit, $section, $total, $minutes];
            foreach (self::outings($entry, $day->day) as [$go, $back, $total, $minutes]) {
                $rows[] = [$date, $name, $id, '', $go, $back, '', '', $unit, $section, $total, $minutes];
            }
        }

        return $rows;
    }

    /**
     * CSV mensual de un empleado. El 年月日 de cada jornada es el de su `hiduke`, o el día anterior si la
     * hora (HHMM) no supera el corte kijun (comparación «>» estricta, como el legacy).
     *
     * @return list<list<string>>
     */
    public static function monthly(array $entries, int $kijunHour, int $kijunMinute): array
    {
        if ($entries === []) {
            return [];
        }

        $rows = [['年月日', '社員番号', '名前', '出勤', '外出', '戻り', '退勤', '備考', '年', '月', '日', '総時間', '分換算']];
        $cut = (int) sprintf('%02d%02d', $kijunHour, $kijunMinute);

        foreach ($entries as $entry) {
            $at = CarbonImmutable::createFromTimestamp((int) $entry['hiduke'], LegacyMonth::TIMEZONE);
            $workDay = (int) $at->format('Hi') > $cut ? $at : $at->subDay();
            [$yy, $mm, $dd] = [$workDay->format('Y'), $workDay->format('m'), $workDay->format('d')];

            if (KintaiService::isRestDay($entry['status'])) {
                continue;
            }
            [$in, $out, $total, $minutes] = self::workTimes($entry, $workDay->day);
            [$id, $name] = [self::t($entry['id']), self::t($entry['name'])];

            $rows[] = [$workDay->format('Y/m/d'), $id, $name, self::t($in), '', '', self::t($out), self::t($entry['bikou']), $yy, $mm, $dd, $total, $minutes];
            foreach (self::outings($entry, $workDay->day) as [$go, $back, $total, $minutes]) {
                $rows[] = ['', $id, $name, '', $go, $back, '', '', $yy, $mm, $dd, $total, $minutes];
            }
        }

        return $rows;
    }

    /** 出勤 y 退勤 (la última de cada una) y la duración entre ambos */
    private static function workTimes(array $entry, int $dayOfMonth): array
    {
        $in = self::last($entry, '1');
        $out = self::last($entry, '2');
        [$total, $minutes] = $in !== null && $out !== null ? KintaiService::duration($in, $out) : ['', ''];

        return [
            $in !== null ? KintaiService::displayTime($in, $dayOfMonth) : '',
            $out !== null ? KintaiService::displayTime($out, $dayOfMonth) : '',
            $total,
            (string) $minutes,
        ];
    }

    /** Parejas 外出/戻り por posición, recorridas por los 外出 */
    private static function outings(array $entry, int $dayOfMonth): array
    {
        $out = [];
        $backs = $entry['records']['4'] ?? [];
        foreach ($entry['records']['3'] ?? [] as $i => $record) {
            $go = (int) $record->kintai_time;
            $back = isset($backs[$i]) ? (int) $backs[$i]->kintai_time : null;
            [$total, $minutes] = $back !== null ? KintaiService::duration($go, $back) : ['', ''];
            $out[] = [
                self::t(KintaiService::displayTime($go, $dayOfMonth)),
                self::t($back !== null ? KintaiService::displayTime($back, $dayOfMonth) : ''),
                $total,
                (string) $minutes,
            ];
        }

        return $out;
    }

    private static function last(array $entry, string $flag): ?int
    {
        $records = $entry['records'][$flag] ?? [];

        return $records === [] ? null : (int) end($records)->kintai_time;
    }

    /** "\t" + texto (también en las celdas vacías, como el legacy) */
    private static function t(mixed $value): string
    {
        return "\t".(string) $value;
    }
}
