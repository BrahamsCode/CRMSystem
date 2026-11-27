<?php

namespace Database\Seeders\Demo\Operaciones;

use App\Models\Operaciones\KintaiConfig;
use App\Models\Operaciones\KintaiHiduke;
use App\Models\Operaciones\ShopWork;
use App\Models\Operaciones\StaffShift;
use App\Models\Operaciones\StaffShiftKind;
use App\Models\Operaciones\WorkTime;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\KintaiService;
use App\Services\Operaciones\LegacyHolidays;
use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 営業日, turnos y 勤怠 de demostración:
 * - 営業日設定 (tb_shop_work) del mes actual y el siguiente: 水曜 y 祝日 = 休日.
 * - Turnos (tb_staff_shift) de esos dos meses: 早番/遅番/通し y 締め (17:00-翌1:00, cruza la medianoche);
 *   algunos días con dos turnos separados (hueco) o solapados, y 祝日 con 休日出勤.
 * - 勤怠 de los últimos ATTENDANCE_DAYS días en tb_work_time (タイムカード de working) y en el terminal
 *   kintai (kintai_tb_hiduke + kintai_tb_kintai), los días con turno y en la jornada estándar de la tienda (la
 *   que usan las pantallas para 遅刻/早出/残業/早退): 残業 de hasta 4 h, 欠勤 y 有給, fichajes sin salida o sin
 *   戻り, 休日出勤, y 残業 hasta pasada la medianoche el último día de mes y el 31/12 → 1/1; la jornada de
 *   hoy en curso (solo entrada).
 * - kintai_tb_config de las tiendas que no lo tengan.
 *
 * Todo sale de crc32 por empleado, día y aspecto: reproducible sin depender de mt_rand. Idempotente
 * por día: solo rellena días vacíos y filas que no existen.
 */
class AsistenciaDemoSeeder extends Seeder
{
    /** Turnos tipo: [[hora, min] inicio, [hora, min] fin]; horas 24+ = día siguiente */
    public const SHIFTS = [
        '早番' => [[9, 0], [17, 0]],
        '遅番' => [[12, 0], [20, 0]],
        '通し' => [[10, 0], [19, 0]],
        '締め' => [[17, 0], [25, 0]],
    ];

    /** シフト区分 que se crean si no hay ninguno (el backup trae ホール y キッチン) */
    private const KINDS = [['ホール', '#99CCFF'], ['キッチン', '#FFCC99'], ['レジ', '#CCFF99']];

    /** 各種設定 para las tiendas sin kintai_tb_config (8:00 a 2:00 del día siguiente: caben los turnos 締め) */
    private const CONFIG = ['kijun_time' => '05<>00', 'start_time' => '08<>00', 'end_time' => '02<>00', 'password' => '9999'];

    /** Menús de category5 que la demo enseña */
    private const MENUS = ['スタッフ管理', '勤怠管理', '初期設定', '初期設定管理', 'スケジュール管理'];

    public const ATTENDANCE_DAYS = 92;

    /** tb_shop_work leídos por tienda y mes (false = sin fila) */
    private array $shopWorks = [];

    public function run(): void
    {
        $now = CarbonImmutable::now(LegacyMonth::TIMEZONE);
        $today = $now->startOfDay();
        $shops = Shop::query()->where('disp_flg', '1')->orderBy('cd')->get();
        if ($shops->isEmpty()) {
            $this->command?->warn('AsistenciaDemoSeeder: no hay tiendas activas, nada que sembrar.');

            return;
        }
        $staff = self::staff($shops->pluck('cd')->all());

        DB::transaction(function () use ($now, $today, $shops, $staff) {
            DB::table('tb_base_system_child')->where('tb_base_system_cd', 5)->whereIn('system_name', self::MENUS)
                ->where('system_flg', '!=', '1')->update(['system_flg' => '1']);
            $kinds = $this->shiftKinds();
            $months = [LegacyMonth::of($today->year, $today->month), LegacyMonth::of($today->year, $today->month)->next()];

            foreach ($shops as $shop) {
                KintaiConfig::query()->firstOrCreate(['tb_shop_cd' => $shop->cd], self::CONFIG);
                foreach ($months as $month) {
                    $this->shopWork((int) $shop->cd, $month);
                }
            }
            $shopsByCd = $shops->keyBy('cd');
            foreach ($staff as $i => $member) {
                foreach ($months as $month) {
                    $this->shifts($member, $i, $month, $kinds);
                }
                $this->attendance($member, $shopsByCd[$member->shop_cd], $i, $now, self::edgeDays($today, $i));
            }
        });

        $this->command?->info("AsistenciaDemoSeeder: {$shops->count()} tiendas, {$staff->count()} empleados.");
    }

    /** Staff activo de las tiendas activas; el master solo si no hay nadie más */
    public static function staff(array $shopCds): Collection
    {
        $all = Staff::active()->whereIn('shop_cd', $shopCds)->orderBy('cd')->get();
        $others = $all->where('login_id', '!=', Staff::MASTER_LOGIN_ID)->values();

        return $others->isEmpty() ? $all : $others;
    }

    /**
     * Días fuera de lo normal que trabajan los dos primeros empleados con 残業 hasta pasada la medianoche:
     * el 31/12 más reciente y el 1/1 siguiente (fin de año y 祝日), y el último día de los dos meses
     * anteriores (fin de mes). Solo los que ya pasaron y caen en los últimos 12 meses.
     *
     * @return list<CarbonImmutable>
     */
    public static function edgeDays(CarbonImmutable $today, int $index): array
    {
        if ($index > 1) {
            return [];
        }
        $newYearsEve = CarbonImmutable::create($today->year - 1, 12, 31, 0, 0, 0, LegacyMonth::TIMEZONE);
        $days = [
            $newYearsEve,
            $newYearsEve->addDay(),
            $today->startOfMonth()->subDay(),
            $today->startOfMonth()->subMonth()->subDay(),
        ];

        return array_values(array_filter($days, fn (CarbonImmutable $d) => $d->lt($today) && $d->gte($today->subYear())));
    }

    /** cd de los シフト区分 activos; si no hay, crea los de KINDS */
    private function shiftKinds(): array
    {
        if (! StaffShiftKind::query()->exists()) {
            foreach (self::KINDS as $rank => [$name, $color]) {
                StaffShiftKind::create(['name' => $name, 'color' => $color, 'rank' => $rank + 1, 'disp_flg' => '1']);
            }
        }

        return StaffShiftKind::query()->ordered()->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
    }

    /** 営業日設定: 水曜と祝日は 休日, el resto 営業日; solo los días sin valor */
    private function shopWork(int $shopCd, LegacyMonth $month): void
    {
        $row = ShopWork::forMonthOrNew($shopCd, $month);
        foreach (range(1, $month->daysInMonth()) as $day) {
            if ((string) $row->getDayValue('syukkin_time', $day) === '') {
                $row->setOpen($day, ! self::isClosed($month->day($day)));
            }
        }
        if ($row->isDirty()) {
            $row->save();
        }
    }

    public static function isClosed(CarbonImmutable $date): bool
    {
        return $date->dayOfWeek === CarbonImmutable::WEDNESDAY || self::isHoliday($date);
    }

    private static function isHoliday(CarbonImmutable $date): bool
    {
        return LegacyHolidays::code($date->year, $date->month, $date->day) > 0;
    }

    /** Turnos del mes en los días vacíos: uno normal, o dos con hueco o solapados */
    private function shifts(Staff $member, int $index, LegacyMonth $month, array $kinds): void
    {
        $row = StaffShift::query()->where('shop_cd', $member->shop_cd)->where('staff_cd', $member->cd)->forMonth($month)->first()
            ?? new StaffShift(['shop_cd' => $member->shop_cd, 'staff_cd' => $member->cd, 'syukkinbi' => $month->epoch()]);
        $kind = fn (int $offset) => $kinds === [] ? '' : $kinds[($index + $offset) % count($kinds)];

        foreach (range(1, $month->daysInMonth()) as $day) {
            $date = $month->day($day);
            $plan = $this->plan($member, $index, $date);
            if ($plan === null || $row->shifts($day) !== []) {
                continue;
            }
            $shape = $plan['name'] === '締め' ? 99 : self::roll($member, $date, 'shape', 100);
            $shifts = match (true) {
                // 中抜け: mañana y tarde con dos horas libres en medio
                $shape < 8 => [
                    ['start' => $plan['start'], 'end' => $plan['start'] + 3 * 3600, 'kind_cd' => $kind(0), 'comment' => '中抜け'],
                    ['start' => $plan['start'] + 5 * 3600, 'end' => $plan['end'], 'kind_cd' => $kind(0), 'comment' => ''],
                ],
                // Solape: un segundo turno (otro 区分) que empieza antes de acabar el primero
                $shape < 12 => [
                    ['start' => $plan['start'], 'end' => $plan['end'], 'kind_cd' => $kind(0), 'comment' => $plan['name']],
                    ['start' => $plan['end'] - 2 * 3600, 'end' => $plan['end'] + 3600, 'kind_cd' => $kind(1), 'comment' => '応援'],
                ],
                default => [['start' => $plan['start'], 'end' => $plan['end'], 'kind_cd' => $kind(0), 'comment' => $plan['name']]],
            };
            $row->setShifts($day, $shifts);
        }
        if ($row->isDirty()) {
            $row->save();
        }
    }

    /**
     * Turno previsto del empleado ese día, o null si libra: dos días libres fijos por semana; los 休日 del
     * 営業日設定 no se trabaja salvo algún 祝日 (休日出勤); tipo de turno según el empleado (el cuarto
     * hace 締め), con algún cambio puntual.
     *
     * @return array{name: string, start: int, end: int}|null
     */
    private function plan(Staff $member, int $index, CarbonImmutable $date): ?array
    {
        $offA = ($index * 2) % 7;
        $offB = ($index * 2 + 3) % 7;
        if (in_array($date->dayOfWeek, [$offA, $offB], true)) {
            return null;
        }
        if (! $this->isOpen((int) $member->shop_cd, $date)) {
            $holidayWork = $date->dayOfWeek !== CarbonImmutable::WEDNESDAY && self::isHoliday($date)
                && self::roll($member, $date, 'holiday', 100) < 35;
            if (! $holidayWork) {
                return null;
            }
        }
        $names = array_keys(self::SHIFTS);
        $name = self::roll($member, $date, 'swap', 100) < 15
            ? $names[($index + 1) % count($names)]
            : $names[$index % count($names)];

        return self::shiftTimes($name, $date);
    }

    /** @return array{name: string, start: int, end: int} */
    private static function shiftTimes(string $name, CarbonImmutable $date): array
    {
        [[$sh, $sm], [$eh, $em]] = self::SHIFTS[$name];

        return ['name' => $name, 'start' => KintaiService::at($date, $sh, $sm), 'end' => KintaiService::at($date, $eh, $em)];
    }

    /** 営業日 según tb_shop_work (sin fila, como el calendario: abierto salvo 水曜/祝日) */
    private function isOpen(int $shopCd, CarbonImmutable $date): bool
    {
        $month = LegacyMonth::of($date->year, $date->month);
        $key = $shopCd.'|'.$month->slash();
        $this->shopWorks[$key] ??= ShopWork::query()->forMonth($shopCd, $month)->first() ?? false;

        return $this->shopWorks[$key] === false ? ! self::isClosed($date) : $this->shopWorks[$key]->isOpen($date->day);
    }

    /**
     * Jornada estándar de la tienda con la que las pantallas de 勤怠 calculan 出勤/遅刻/早出 y 退社/残業/早退
     * (WorkTimeService): de 始業時間 (`start_work_time` 'H<>MM', 9:00 si falta) a 8 h + 休憩 (`break_time`) después.
     *
     * @return array{name: string, start: int, end: int}
     */
    public static function standardDay(Shop $shop, CarbonImmutable $date): array
    {
        [$hour, $minute] = array_pad(explode('<>', (string) $shop->start_work_time), 2, '0');
        $hour = is_numeric($hour) ? (int) $hour : 9;
        $break = is_numeric($shop->break_time) ? (int) $shop->break_time : 60;
        $start = KintaiService::at($date, $hour, (int) $minute);

        return ['name' => '通常', 'start' => $start, 'end' => $start + (8 * 60 + $break) * 60];
    }

    /**
     * 勤怠 de los últimos ATTENDANCE_DAYS días, los días límite y hoy (solo la entrada, si ya empezó la jornada),
     * en tb_work_time y en el terminal kintai. Se trabaja los días con turno, en la jornada estándar de la tienda.
     *
     * @param  list<CarbonImmutable>  $edgeDays
     */
    private function attendance(Staff $member, Shop $shop, int $index, CarbonImmutable $now, array $edgeDays): void
    {
        $today = $now->startOfDay();
        $config = KintaiConfig::forShop((int) $member->shop_cd);
        $kintai = app(KintaiService::class);
        $rows = [];

        $days = [];
        for ($back = self::ATTENDANCE_DAYS; $back >= 0; $back--) {
            $date = $today->subDays($back);
            $days[$date->toDateString()] = [$date, $this->plan($member, $index, $date) === null ? null : self::standardDay($shop, $date)];
        }
        $edgeKeys = [];
        foreach ($edgeDays as $date) {
            $edgeKeys[] = $date->toDateString();
            $days[$date->toDateString()] = [$date, self::standardDay($shop, $date)];
        }

        foreach ($days as [$date, $plan]) {
            if ($plan === null) {
                continue;
            }
            $day = $this->workedDay($member, $date, $plan, in_array($date->toDateString(), $edgeKeys, true));
            if ($date->equalTo($today)) {
                if ($plan['start'] > $now->getTimestamp() || $day['in'] === null) {
                    continue;
                }
                // Jornada en curso: solo la entrada
                $day = ['in' => $day['in'], 'out' => null, 'break' => [null, null]] + $day;
            }

            $month = LegacyMonth::of($date->year, $date->month);
            $row = $rows[$month->epoch()] ??= WorkTime::query()->where('shop_cd', $member->shop_cd)->where('staff_cd', $member->cd)->forMonth($month)->first()
                ?? new WorkTime(['shop_cd' => $member->shop_cd, 'staff_cd' => $member->cd, 'syukkinbi' => $month->epoch(), 'felica_no' => (string) $member->felica_no]);
            if ($day['in'] !== null && (string) $row->getDayValue('syukkin_time', $date->day) === '') {
                $row->setDayValue('syukkin_time', $date->day, (string) $day['in'])
                    ->setDayValue('taikin_time', $date->day, (string) $day['out'])
                    ->setDayValue('gaisyutu_time', $date->day, (string) $day['break'][0])
                    ->setDayValue('kisha_time', $date->day, (string) $day['break'][1])
                    ->setDayValue('syukkin_flg', $date->day, $day['flg'])
                    ->setDayValue('syukkin_kubun', $date->day, $day['out'] === null ? '' : $day['kubun']);
            }

            [$from, $to] = KintaiService::window($config, $date);
            $exists = KintaiHiduke::query()->where('tb_staff_cd', $member->cd)
                ->where('hiduke', '>=', (string) $from)->where('hiduke', '<', (string) $to)->exists();
            if (! $exists) {
                $times = $day['in'] === null
                    ? ['shukkin' => $to - 1] // 欠勤/有給 sin hora: como kintai_new.php, al final de la jornada
                    : array_filter(['shukkin' => $day['in'], 'gaishutu' => $day['break'][0], 'modori' => $day['break'][1], 'taikin' => $day['out']]);
                $kintai->create($member, $day['status'], $day['bikou'], $times);
            }
        }

        foreach ($rows as $row) {
            if ($row->isDirty()) {
                $row->save();
            }
        }
    }

    /**
     * Lo que pasó ese día: 欠勤/有給 (sin horas) o entrada, descanso y salida con su 出勤状況 / 出勤区分.
     * Los días límite siempre se trabajan, con 残業.
     *
     * @return array{in: ?int, out: ?int, break: array{0: ?int, 1: ?int}, flg: string, kubun: string, status: string, bikou: string}
     */
    private function workedDay(Staff $member, CarbonImmutable $date, array $plan, bool $edge): array
    {
        $roll = fn (string $aspect, int $max) => self::roll($member, $date, $aspect, $max);
        $absence = $edge ? 99 : $roll('absence', 100);
        if ($absence < 3) {
            return ['in' => null, 'out' => null, 'break' => [null, null], 'flg' => '', 'kubun' => '', 'status' => '欠勤', 'bikou' => '体調不良のため'];
        }
        if ($absence < 7) {
            return ['in' => null, 'out' => null, 'break' => [null, null], 'flg' => '', 'kubun' => '', 'status' => '有給休暇', 'bikou' => ''];
        }

        $minute = 60;
        $arrival = $roll('in', 100);
        [$in, $flg, $status, $bikou] = match (true) {
            $arrival < 10 => [$plan['start'] + (3 + $roll('late', 23)) * $minute, '遅刻', '遅刻', '電車遅延'],
            $arrival < 25 => [$plan['start'] - (20 + $roll('early', 21)) * $minute, '早出', '出勤', ''],
            default => [$plan['start'] - (1 + $roll('ontime', 10)) * $minute, '出勤', '出勤', ''],
        };
        $leave = $edge ? -1 : $roll('out', 100);
        [$out, $kubun] = match (true) {
            $leave < 0 => [$plan['end'] + (420 + $roll('night', 61)) * $minute, '残業'], // hasta pasada la medianoche
            $leave < 4 => [$plan['end'] + (180 + $roll('long', 61)) * $minute, '残業'], // 3-4 h de 残業
            $leave < 15 => [$plan['end'] + (30 + $roll('over', 91)) * $minute, '残業'],
            $leave < 20 => [$plan['end'] - (60 + $roll('leave', 61)) * $minute, '早退'],
            $leave < 23 => [null, '退社'], // olvidó fichar la salida
            default => [$plan['end'] + $roll('exit', 11) * $minute, '退社'],
        };
        if ($kubun === '早退' && $status === '出勤') {
            [$status, $bikou] = ['早退', '通院のため'];
        }
        if (self::isHoliday($date)) {
            $bikou = trim('休日出勤 '.$bikou);
        }
        // Descanso a media jornada: sale entre 4 h y 4 h 30 después de entrar y vuelve a los 45-60 min
        // (a veces no ficha la vuelta); los turnos cortos por 早退 no descansan.
        $goOut = $in + (240 + $roll('break', 31)) * $minute;
        $back = $roll('noback', 100) < 3 ? null : $goOut + (45 + $roll('back', 16)) * $minute;
        $break = $out !== null && $goOut >= $out ? [null, null] : [$goOut, $back];

        return ['in' => $in, 'out' => $out, 'break' => $break, 'flg' => $flg, 'kubun' => $kubun, 'status' => $status, 'bikou' => $bikou];
    }

    /** Número fijo en [0, max) para el empleado, el día y el aspecto */
    private static function roll(Staff $member, CarbonImmutable $date, string $aspect, int $max): int
    {
        return crc32("{$member->cd}|{$date->toDateString()}|{$aspect}") % $max;
    }
}
