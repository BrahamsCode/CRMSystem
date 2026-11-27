<?php

namespace App\Services\Operaciones\Working;

use App\Models\Operaciones\WorkTime;
use App\Models\Shop;
use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * 勤怠一覧 del día (category5/working/work_list.php, work_list2.php, ng_work_list.php) y la corrección
 * de una marca (work_list_update.php, change_syukkin.php) sobre `tb_work_time` (modelo WorkTime de S5).
 *
 * Las tres pantallas calculan el 労働時間 casi igual pero no del todo: cada variante reproduce su
 * cálculo (ver calculate()). Las horas son epoch en texto, como las escribe la タイムカード.
 */
final class WorkTimeService
{
    public const VARIANT_DAY = 'day';   // work_list.php

    public const VARIANT_DAY2 = 'day2'; // work_list2.php

    public const VARIANT_NG = 'ng';     // ng_work_list.php

    /** Hora de corte de la jornada: una marca antes de las 5:00 es del día siguiente (調整(５時以下なら翌日)) */
    public const DAY_CUTOFF_HOUR = 5;

    /**
     * Momento real de una marca de la jornada `$workDay` a las `$hour:$minute`. Si la hora es menor
     * que el corte (5), la jornada empezó el día anterior y la marca cae en el día siguiente (+24 h),
     * como `$time += 86400` del legacy.
     */
    public function resolveWorkDate(CarbonImmutable $workDay, int $hour, int $minute): CarbonImmutable
    {
        $time = $workDay->setTimezone(LegacyMonth::TIMEZONE)->startOfDay()->setTime($hour, $minute);

        return $hour < self::DAY_CUTOFF_HOUR ? $time->addDay() : $time;
    }

    /** Fecha 'Y/n/j' del parámetro `dd` del legacy; null si no es una fecha válida */
    public function parseDate(mixed $dd): ?CarbonImmutable
    {
        if (! is_string($dd) || ! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', trim($dd), $m)) {
            return null;
        }

        try {
            return LegacyMonth::of((int) $m[1], (int) $m[2])->day((int) $m[3]);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Guarda la entrada, la salida, 出勤状況 y 出勤区分 de un día (UPDATE como el legacy: si el
     * empleado no tiene fila del mes no se crea). Una hora con hora o minuto vacío se borra (''),
     * pero una hora que no se envía (null) no se toca: ng_work_list.php solo manda 状況/区分 y el
     * legacy le borraba las marcas.
     *
     * @param  array{0: string|null, 1: string|null}|null  $in  [hora, minuto] de 出勤
     * @param  array{0: string|null, 1: string|null}|null  $out  [hora, minuto] de 退勤
     */
    public function saveDay(int $staffCd, CarbonImmutable $day, ?array $in, ?array $out, ?string $flg, ?string $kubun): ?WorkTime
    {
        $row = WorkTime::query()->where('staff_cd', $staffCd)
            ->forMonth(LegacyMonth::of($day->year, $day->month))->first();
        if (! $row) {
            return null;
        }

        foreach (['syukkin_time' => $in, 'taikin_time' => $out] as $prefix => $time) {
            if ($time !== null) {
                $row->setDayValue($prefix, $day->day, $this->stamp($day, $time));
            }
        }
        $row->setDayValue('syukkin_flg', $day->day, (string) $flg);
        $row->setDayValue('syukkin_kubun', $day->day, (string) $kubun);

        // tb_work_time no tiene clave primaria única fiable: se actualiza por empleado y mes, como el legacy
        if ($row->isDirty()) {
            WorkTime::query()->where('staff_cd', $staffCd)->where('syukkinbi', $row->syukkinbi)
                ->update($row->getDirty());
        }

        return $row;
    }

    /**
     * Filas de la lista del día: una por empleado, con lo que pinta cada pantalla.
     *
     * @param  Collection<int, \App\Models\Staff>  $staff
     */
    public function dayRows(string $variant, ?Shop $shop, Collection $staff, CarbonImmutable $day, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now(LegacyMonth::TIMEZONE);
        $works = $staff->isEmpty() ? collect() : WorkTime::query()
            ->whereIn('staff_cd', $staff->pluck('cd'))
            ->forMonth(LegacyMonth::of($day->year, $day->month))
            ->get()->keyBy('staff_cd');

        return $staff->values()->map(function ($member) use ($variant, $shop, $day, $now, $works) {
            $work = $works->get($member->cd);
            if (! $work) {
                return ['staff' => $member, 'record' => false];
            }

            $values = $work->day($day->day);
            $in = $this->epoch($values['syukkin_time']);
            $out = $this->epoch($values['taikin_time']);
            $calc = $this->calculate($variant, $this->shopRules($shop), $day, $in, $out,
                (string) $values['syukkin_flg'], (string) $values['syukkin_kubun'], $now);

            // 休憩回数: elementos de la lista de 外出 (explode sin limpiar, como el legacy)
            $outings = explode('<>', (string) $values['gaisyutu_time']);

            return [
                'staff' => $member,
                'record' => true,
                'in' => $in,
                'out' => $out,
                'start' => $calc['start'],
                'end' => $calc['end'],
                'sum' => $calc['sum'],
                'breaks' => $outings[0] !== '' ? count($outings) : 0,
                'flg' => (string) $values['syukkin_flg'],
                'kubun' => (string) $values['syukkin_kubun'],
            ];
        })->all();
    }

    /**
     * 労働時間 de un día. `start` = entrada redondeada, `end` = salida redondeada, `sum` = horas (float).
     * Unidad de redondeo m = kyuyo_marume minutos; inicio de jornada S = start_work_time del día;
     * fin de jornada E = S + 8 h (+ break_time minutos solo en work_list.php).
     *
     * 出勤状況: 早出 → start = S - floor((S - entrada)/m)·m; 出勤 → start = S; 遅刻 → start = S + ceil((entrada - S)/m)·m.
     * 出勤区分: '' → ahora - start; 退社 → E - start; 残業 y 早退 según la variante:
     * - day: 残業 con 早出 → end = E + floor((salida - E)/m)·m; 残業 con 出勤/遅刻 y todo 早退 → end = E - ceil((E - salida)/m)·m; sum = end - start.
     * - day2: con 出勤 como day; con 早出/遅刻, 残業 → salida - start y 早退 → vacío (el legacy restaba una variable sin definir).
     * - ng: 残業 y 早退 → salida - start.
     *
     * @param  array{start_hour: int, start_minute: int, break: float, marume: float}  $rules
     * @return array{start: ?CarbonImmutable, end: ?CarbonImmutable, sum: ?float}
     */
    public function calculate(string $variant, array $rules, CarbonImmutable $day, ?CarbonImmutable $in, ?CarbonImmutable $out, string $flg, string $kubun, CarbonImmutable $now): array
    {
        $result = ['start' => null, 'end' => null, 'sum' => null];
        $unit = max(1.0, $rules['marume']) * 60; // segundos; el legacy dividía por cero con kyuyo_marume vacío
        $s = $day->setTimezone(LegacyMonth::TIMEZONE)->startOfDay()->addHours($rules['start_hour'])->addMinutes($rules['start_minute']);
        $e = $s->addHours(8);
        if ($variant === self::VARIANT_DAY) {
            $e = $e->addMinutes((int) $rules['break']);
        }
        $ts = fn (?CarbonImmutable $t) => $t?->getTimestamp();

        $start = match ($flg) {
            '早出' => $in ? $s->getTimestamp() - floor(($s->getTimestamp() - $in->getTimestamp()) / $unit) * $unit : null,
            '出勤' => $s->getTimestamp(),
            '遅刻' => $in ? $s->getTimestamp() + ceil(($in->getTimestamp() - $s->getTimestamp()) / $unit) * $unit : null,
            default => null,
        };
        if ($start === null) {
            return $result;
        }
        $result['start'] = CarbonImmutable::createFromTimestamp((int) $start, LegacyMonth::TIMEZONE);

        $endRoundedDown = fn () => $out ? $e->getTimestamp() - ceil(($e->getTimestamp() - $out->getTimestamp()) / $unit) * $unit : null;
        $end = null;
        $sum = null;
        switch ($kubun) {
            case '':
                $sum = ($now->getTimestamp() - $start) / 3600;
                break;
            case '退社':
                $sum = ($e->getTimestamp() - $start) / 3600;
                if ($variant === self::VARIANT_DAY) {
                    $end = $e->getTimestamp();
                }
                break;
            case '残業':
            case '早退':
                if ($variant === self::VARIANT_NG || ($variant === self::VARIANT_DAY2 && $flg !== '出勤')) {
                    if ($kubun === '残業' || $variant === self::VARIANT_NG) {
                        $sum = $out ? ($ts($out) - $start) / 3600 : null;
                    }
                    break;
                }
                $end = ($variant === self::VARIANT_DAY && $kubun === '残業' && $flg === '早出')
                    ? ($out ? $e->getTimestamp() + floor(($out->getTimestamp() - $e->getTimestamp()) / $unit) * $unit : null)
                    : $endRoundedDown();
                $sum = $end === null ? null : ($end - $start) / 3600;
                break;
        }

        $result['end'] = $end === null ? null : CarbonImmutable::createFromTimestamp((int) $end, LegacyMonth::TIMEZONE);
        $result['sum'] = $sum === null ? null : (float) $sum;

        return $result;
    }

    /** Reglas de la tienda: start_work_time 'H<>M', break_time (min) y kyuyo_marume (min) */
    public function shopRules(?Shop $shop): array
    {
        $startTime = explode('<>', (string) $shop?->start_work_time);

        return [
            'start_hour' => (int) ($startTime[0] ?? 0),
            'start_minute' => (int) ($startTime[1] ?? 0),
            'break' => (float) $shop?->break_time,
            'marume' => (float) $shop?->kyuyo_marume,
        ];
    }

    /** Epoch de la marca, o '' si falta la hora o el minuto */
    private function stamp(CarbonImmutable $day, array $time): string
    {
        [$hour, $minute] = $time + [null, null];
        if ((string) $hour === '' || (string) $minute === '') {
            return '';
        }

        return (string) $this->resolveWorkDate($day, (int) $hour, (int) $minute)->getTimestamp();
    }

    private function epoch(?string $value): ?CarbonImmutable
    {
        return trim((string) $value) === '' ? null : CarbonImmutable::createFromTimestamp((int) $value, LegacyMonth::TIMEZONE);
    }
}
