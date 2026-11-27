<?php

namespace App\Services\Operaciones;

use App\Models\Operaciones\KintaiConfig;
use App\Models\Operaciones\StaffShift;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Tablero de turnos de スケジュール管理 (schedule/staff_shift.php y staff_shift_month.php):
 * franjas de 15 minutos del horario de la tienda (kintai_tb_config: start_time → end_time, que pasa al día
 * siguiente si termina antes de kijun_time), lectura de los turnos de un día y guardado.
 *
 * Los turnos se guardan en tb_staff_shift (una fila por tienda, empleado y mes; syukkinbi = epoch del día 1)
 * como listas paralelas `<>` del día: inicio y fin (epoch), シフト区分 y comentario.
 */
class ShiftBoard
{
    public const SPAN_MINUTES = 15;

    /**
     * Horario de la tienda: [inicio [h, m], minutos totales]. Sin fila de kintai_tb_config se usan los valores
     * con los que el legacy la crea (el legacy redirigía a 勤怠一覧, ⏸).
     *
     * @return array{start: array{int, int}, minutes: int}
     */
    public function hours(int $shopCd): array
    {
        $config = KintaiConfig::query()->where('tb_shop_cd', $shopCd)->orderByDesc('cd')->first();
        $value = fn (string $field) => KintaiConfig::splitTime($config?->{$field} ?? KintaiConfig::DEFAULTS[$field]);
        [$kh, $km] = $value('kijun_time');
        [$sh, $sm] = $value('start_time');
        [$eh, $em] = $value('end_time');

        $endsNextDay = $eh < $kh || ($eh === $kh && $em <= $km);
        $minutes = ($endsNextDay ? 24 * 60 : 0) + ($eh * 60 + $em) - ($sh * 60 + $sm);

        return ['start' => [$sh, $sm], 'minutes' => $minutes];
    }

    /**
     * Franjas del día (una más que el horario, como el legacy): h (24+ si cae al día siguiente), m y epoch.
     *
     * @return list<array{h: string, m: string, u: int}>
     */
    public function slots(CarbonImmutable $day, array $hours): array
    {
        $start = $day->setTime($hours['start'][0], $hours['start'][1]);
        $slots = [];
        for ($minutes = 0; $minutes < $hours['minutes'] + self::SPAN_MINUTES; $minutes += self::SPAN_MINUTES) {
            $at = $start->addMinutes($minutes);
            $hour = $at->day !== $day->day ? (int) $at->format('H') + 24 : $at->format('H');
            $slots[] = ['h' => (string) $hour, 'm' => $at->format('i'), 'u' => $at->getTimestamp()];
        }

        return $slots;
    }

    /**
     * Turnos guardados de un día: staff_cd, inicio y fin 'H:i' (24+ si pasan del día), epoch, シフト区分 y comentario.
     *
     * @param  iterable<StaffShift>  $rows
     */
    public function dayShifts(iterable $rows, CarbonImmutable $day): array
    {
        $endOfDay = $day->setTime(23, 59, 59)->getTimestamp();
        $clock = function (int $epoch) use ($endOfDay) {
            $at = CarbonImmutable::createFromTimestamp($epoch, LegacyMonth::TIMEZONE);
            $hour = $epoch > $endOfDay ? (int) $at->format('H') + 24 : $at->format('H');

            return $hour.':'.$at->format('i');
        };

        $data = [];
        foreach ($rows as $row) {
            foreach ($row->shifts($day->day) as $shift) {
                $data[] = [
                    'staff_cd' => (int) $row->staff_cd,
                    'start' => $clock($shift['start']),
                    'end' => $clock((int) $shift['end']),
                    'start_unix' => $shift['start'],
                    'end_unix' => (int) $shift['end'],
                    'kubun' => (string) ($shift['kind_cd'] ?? 0),
                    'komment' => (string) $shift['comment'],
                ];
            }
        }

        return $data;
    }

    /**
     * Turnos del mes de un empleado (staff_shift_month.php): como dayShifts, pero `staff_cd` es el índice del
     * día (día - 1), que es lo que el JS usa como fila.
     */
    public function monthShifts(?StaffShift $row, LegacyMonth $month): array
    {
        if (! $row) {
            return [];
        }

        $data = [];
        foreach (range(1, $month->daysInMonth()) as $day) {
            foreach ($this->dayShifts([$row], $month->day($day)) as $shift) {
                $data[] = ['staff_cd' => $day - 1] + $shift;
            }
        }

        return $data;
    }

    /**
     * Guarda el mes de un empleado (staff_shift_month.php, mode=update): sch[] = "<día-1>_<inicio>_<fin>_<区分>_<comentario>";
     * los días sin turnos quedan vacíos.
     *
     * @param  list<string>  $values
     */
    public function saveMonth(int $shopCd, int $staffCd, LegacyMonth $month, array $values): void
    {
        $byDay = [];
        foreach ($values as $value) {
            $shift = self::parse($value);
            if (is_numeric($shift['staff_cd'])) {
                $byDay[(int) $shift['staff_cd'] + 1][] = $shift;
            }
        }

        DB::transaction(function () use ($shopCd, $staffCd, $month, $byDay) {
            $row = $this->row($shopCd, $staffCd, $month);
            foreach (range(1, $month->daysInMonth()) as $day) {
                $row->setShifts($day, $byDay[$day] ?? []);
            }
            $row->save();
        });
    }

    /** `sch[]` del formulario: "<staff_cd>_<inicio>_<fin>_<区分>_<comentario>" */
    public static function parse(string $value): array
    {
        [$staffCd, $start, $end, $kind, $comment] = array_pad(explode('_', $value, 5), 5, null);

        return ['staff_cd' => $staffCd, 'start' => (string) $start, 'end' => (string) $end, 'kind_cd' => (string) $kind, 'comment' => (string) $comment];
    }

    /** Fila de tb_staff_shift de tienda, empleado y mes (nueva: el cd lo da hatuban al guardar) */
    public function row(int $shopCd, int $staffCd, LegacyMonth $month): StaffShift
    {
        return StaffShift::query()->where('shop_cd', $shopCd)->where('staff_cd', $staffCd)->forMonth($month)->first()
            ?? new StaffShift(['shop_cd' => $shopCd, 'staff_cd' => $staffCd, 'syukkinbi' => $month->epoch()]);
    }

    /**
     * Guarda los turnos de un día de todos los empleados (staff_shift.php, mode=update): los empleados activos
     * de la tienda sin turnos enviados quedan con el día vacío.
     *
     * @param  list<string>  $values  sch[]
     */
    public function saveDay(int $shopCd, CarbonImmutable $day, array $values, array $activeStaffCds): void
    {
        $byStaff = array_fill_keys($activeStaffCds, []);
        foreach ($values as $value) {
            $shift = self::parse($value);
            $byStaff[$shift['staff_cd']][] = $shift;
        }

        $month = LegacyMonth::of($day->year, $day->month);
        DB::transaction(function () use ($byStaff, $shopCd, $month, $day) {
            foreach ($byStaff as $staffCd => $shifts) {
                if (! is_numeric($staffCd)) {
                    continue;
                }
                $row = $this->row($shopCd, (int) $staffCd, $month);
                $row->setShifts($day->day, $shifts)->save();
            }
        });
    }
}
