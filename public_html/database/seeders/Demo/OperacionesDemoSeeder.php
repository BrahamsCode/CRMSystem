<?php

namespace Database\Seeders\Demo;

use App\Models\Operaciones\Information;
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
 * Datos de demostración de 業務支援管理 (S5), reglas en CONVENCIONES.md §4 «Datos de demostración»:
 * - 営業日設定 (tb_shop_work) del mes actual y el siguiente: 水曜定休 y festivos como 休日.
 * - Turnos (tb_staff_shift) del staff activo en esos dos meses, con su シフト区分.
 * - 勤怠 de los últimos 30 días (hasta ayer): タイムカード de working (tb_work_time) y terminal kintai
 *   (kintai_tb_hiduke + kintai_tb_kintai) con entrada, 外出/戻り, salida, 遅刻/早出/残業/早退 y algún 欠勤/有給.
 * - kintai_tb_config de cada tienda que no lo tenga, tres インフォメーション y los menús de category5 activos.
 *
 * Todo sale de un hash fijo por empleado y día (crc32), así el resultado es reproducible sin depender de
 * mt_rand. Idempotente por día: solo rellena días vacíos y filas que no existen; nunca borra ni pisa nada.
 * CTI y terminales no necesitan datos propios (CTI lee los clientes y visitas de S2; los terminales
 * de señalización y カード son de S4).
 */
class OperacionesDemoSeeder extends Seeder
{
    /** Turnos tipo: [inicio, fin] en horas del día; todos caben en el horario 9-20 del backup */
    private const SHIFTS = [
        '早番' => [[9, 0], [17, 0]],
        '遅番' => [[12, 0], [20, 0]],
        '通し' => [[10, 0], [19, 0]],
    ];

    /** シフト区分 que se crean si la tienda no tiene ninguno (el backup trae ホール y キッチン) */
    private const KINDS = [['ホール', '#99CCFF'], ['キッチン', '#FFCC99'], ['レジ', '#CCFF99']];

    /** 各種設定 para las tiendas sin kintai_tb_config (horario amplio para que se vean los turnos) */
    private const CONFIG = ['kijun_time' => '05<>00', 'start_time' => '08<>00', 'end_time' => '22<>00', 'password' => '9999'];

    /** Menús de category5 que la demo enseña (el backup trae 初期設定 y スケジュール管理 apagados) */
    private const MENUS = ['スタッフ管理', '勤怠管理', '初期設定', '初期設定管理', 'スケジュール管理'];

    private const NOTICES = [
        ['今月のシフト提出について', "来月分の希望シフトは毎月20日までに店長へ提出してください。\n提出がない場合は前月と同じパターンで作成します。"],
        ['年末年始の営業について', "12月31日は18時閉店、1月1日は休業となります。\n年始は1月2日10時から通常営業です。"],
        ['衛生チェックのお願い', '出勤時の手洗い・検温を徹底してください。体調不良の場合は無理をせず、必ず事前に連絡をお願いします。'],
    ];

    private const ATTENDANCE_DAYS = 30;

    /** tb_shop_work leídos por tienda y mes (false = sin fila) */
    private array $shopWorks = [];

    public function run(): void
    {
        $today = CarbonImmutable::now(LegacyMonth::TIMEZONE)->startOfDay();
        $shops = Shop::query()->where('disp_flg', '1')->orderBy('cd')->get();
        if ($shops->isEmpty()) {
            $this->command?->warn('OperacionesDemoSeeder: no hay tiendas activas, nada que sembrar.');

            return;
        }
        $staff = $this->staff($shops->pluck('cd')->all());

        DB::transaction(function () use ($today, $shops, $staff) {
            $this->menus();
            $kinds = $this->shiftKinds();
            $months = [LegacyMonth::of($today->year, $today->month), LegacyMonth::of($today->year, $today->month)->next()];

            foreach ($shops as $shop) {
                $this->kintaiConfig((int) $shop->cd);
                foreach ($months as $month) {
                    $this->shopWork((int) $shop->cd, $month);
                }
            }
            foreach ($staff as $i => $member) {
                foreach ($months as $month) {
                    $this->shifts($member, $i, $month, $kinds);
                }
                $this->attendance($member, $i, $today);
            }
            $this->notices();
        });

        $this->command?->info("OperacionesDemoSeeder: {$shops->count()} tiendas, {$staff->count()} empleados.");
    }

    /** Staff activo de las tiendas activas; el master solo si no hay nadie más */
    private function staff(array $shopCds): Collection
    {
        $all = Staff::active()->whereIn('shop_cd', $shopCds)->orderBy('cd')->get();
        $others = $all->where('login_id', '!=', Staff::MASTER_LOGIN_ID)->values();

        return $others->isEmpty() ? $all : $others;
    }

    private function menus(): void
    {
        DB::table('tb_base_system_child')->where('tb_base_system_cd', 5)->whereIn('system_name', self::MENUS)
            ->where('system_flg', '!=', '1')->update(['system_flg' => '1']);
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

    private function kintaiConfig(int $shopCd): void
    {
        KintaiConfig::query()->firstOrCreate(['tb_shop_cd' => $shopCd], self::CONFIG);
    }

    /** 営業日設定: 水曜と祝日は 休日, el resto 営業日; solo los días sin valor */
    private function shopWork(int $shopCd, LegacyMonth $month): void
    {
        $row = ShopWork::forMonthOrNew($shopCd, $month);
        foreach (range(1, $month->daysInMonth()) as $day) {
            if ((string) $row->getDayValue('syukkin_time', $day) === '') {
                $row->setOpen($day, ! $this->isClosed($month->day($day)));
            }
        }
        if ($row->isDirty()) {
            $row->save();
        }
    }

    private function isClosed(CarbonImmutable $date): bool
    {
        return $date->dayOfWeek === CarbonImmutable::WEDNESDAY
            || LegacyHolidays::code($date->year, $date->month, $date->day) > 0;
    }

    /** Turnos del mes en los días vacíos */
    private function shifts(Staff $member, int $index, LegacyMonth $month, array $kinds): void
    {
        $row = StaffShift::query()->where('shop_cd', $member->shop_cd)->where('staff_cd', $member->cd)->forMonth($month)->first()
            ?? new StaffShift(['shop_cd' => $member->shop_cd, 'staff_cd' => $member->cd, 'syukkinbi' => $month->epoch()]);
        $kind = $kinds === [] ? '' : $kinds[$index % count($kinds)];

        foreach (range(1, $month->daysInMonth()) as $day) {
            $plan = $this->plan($member, $index, $month->day($day));
            if ($plan === null || $row->shifts($day) !== []) {
                continue;
            }
            $row->setShifts($day, [['start' => $plan['start'], 'end' => $plan['end'], 'kind_cd' => $kind, 'comment' => $plan['name']]]);
        }
        if ($row->isDirty()) {
            $row->save();
        }
    }

    /**
     * Turno previsto del empleado ese día, o null si libra: dos días libres fijos por semana, cerrado
     * los 休日 del 営業日設定 y tipo de turno según el empleado (con algún cambio puntual).
     *
     * @return array{name: string, start: int, end: int}|null
     */
    private function plan(Staff $member, int $index, CarbonImmutable $date): ?array
    {
        $offA = ($index * 2) % 7;
        $offB = ($index * 2 + 3) % 7;
        if (in_array($date->dayOfWeek, [$offA, $offB], true) || ! $this->isOpen((int) $member->shop_cd, $date)) {
            return null;
        }
        $names = array_keys(self::SHIFTS);
        $name = $this->roll($member, $date, 'swap', 100) < 15
            ? $names[($index + 1) % count($names)]
            : $names[$index % count($names)];
        [[$sh, $sm], [$eh, $em]] = self::SHIFTS[$name];

        return ['name' => $name, 'start' => KintaiService::at($date, $sh, $sm), 'end' => KintaiService::at($date, $eh, $em)];
    }

    /** 営業日 según tb_shop_work (sin fila, como el calendario: abierto salvo 水曜/祝日) */
    private function isOpen(int $shopCd, CarbonImmutable $date): bool
    {
        $month = LegacyMonth::of($date->year, $date->month);
        $key = $shopCd.'|'.$month->slash();
        $this->shopWorks[$key] ??= ShopWork::query()->forMonth($shopCd, $month)->first() ?? false;

        return $this->shopWorks[$key] === false ? ! $this->isClosed($date) : $this->shopWorks[$key]->isOpen($date->day);
    }

    /** 勤怠 de los últimos ATTENDANCE_DAYS días (hasta ayer) en tb_work_time y en el terminal kintai */
    private function attendance(Staff $member, int $index, CarbonImmutable $today): void
    {
        $config = KintaiConfig::forShop((int) $member->shop_cd);
        $kintai = app(KintaiService::class);
        $rows = [];

        for ($back = self::ATTENDANCE_DAYS; $back >= 1; $back--) {
            $date = $today->subDays($back);
            $plan = $this->plan($member, $index, $date);
            if ($plan === null) {
                continue;
            }
            $day = $this->workedDay($member, $date, $plan);

            $month = LegacyMonth::of($date->year, $date->month);
            $row = $rows[$month->epoch()] ??= WorkTime::query()->where('shop_cd', $member->shop_cd)->where('staff_cd', $member->cd)->forMonth($month)->first()
                ?? new WorkTime(['shop_cd' => $member->shop_cd, 'staff_cd' => $member->cd, 'syukkinbi' => $month->epoch()]);
            if ($day['in'] !== null && (string) $row->getDayValue('syukkin_time', $date->day) === '') {
                $row->setDayValue('syukkin_time', $date->day, (string) $day['in'])
                    ->setDayValue('taikin_time', $date->day, (string) $day['out'])
                    ->setDayValue('gaisyutu_time', $date->day, (string) $day['break'][0])
                    ->setDayValue('kisha_time', $date->day, (string) $day['break'][1])
                    ->setDayValue('syukkin_flg', $date->day, $day['flg'])
                    ->setDayValue('syukkin_kubun', $date->day, $day['kubun']);
            }

            [$from, $to] = KintaiService::window($config, $date);
            $exists = KintaiHiduke::query()->where('tb_staff_cd', $member->cd)
                ->where('hiduke', '>=', (string) $from)->where('hiduke', '<', (string) $to)->exists();
            if (! $exists) {
                $times = $day['in'] === null
                    ? ['shukkin' => $to - 1] // 欠勤/有給 sin hora: como kintai_new.php, al final de la jornada
                    : ['shukkin' => $day['in'], 'gaishutu' => $day['break'][0], 'modori' => $day['break'][1], 'taikin' => $day['out']];
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
     *
     * @return array{in: ?int, out: ?int, break: array{0: ?int, 1: ?int}, flg: string, kubun: string, status: string, bikou: string}
     */
    private function workedDay(Staff $member, CarbonImmutable $date, array $plan): array
    {
        $absence = $this->roll($member, $date, 'absence', 100);
        if ($absence < 3) {
            return ['in' => null, 'out' => null, 'break' => [null, null], 'flg' => '', 'kubun' => '', 'status' => '欠勤', 'bikou' => '体調不良のため'];
        }
        if ($absence < 7) {
            return ['in' => null, 'out' => null, 'break' => [null, null], 'flg' => '', 'kubun' => '', 'status' => '有給休暇', 'bikou' => ''];
        }

        $minute = 60;
        $arrival = $this->roll($member, $date, 'in', 100);
        [$in, $flg, $status, $bikou] = match (true) {
            $arrival < 10 => [$plan['start'] + (3 + $this->roll($member, $date, 'late', 23)) * $minute, '遅刻', '遅刻', '電車遅延'],
            $arrival < 25 => [$plan['start'] - (20 + $this->roll($member, $date, 'early', 21)) * $minute, '早出', '出勤', ''],
            default => [$plan['start'] - (1 + $this->roll($member, $date, 'ontime', 10)) * $minute, '出勤', '出勤', ''],
        };
        $leave = $this->roll($member, $date, 'out', 100);
        [$out, $kubun] = match (true) {
            $leave < 15 => [$plan['end'] + (30 + $this->roll($member, $date, 'over', 91)) * $minute, '残業'],
            $leave < 20 => [$plan['end'] - (60 + $this->roll($member, $date, 'leave', 61)) * $minute, '早退'],
            default => [$plan['end'] + $this->roll($member, $date, 'exit', 11) * $minute, '退社'],
        };
        if ($kubun === '早退' && $status === '出勤') {
            [$status, $bikou] = ['早退', '通院のため'];
        }
        // Descanso a media jornada: sale entre 4 h y 4 h 30 después de entrar y vuelve a los 45-60 min
        $goOut = $in + (240 + $this->roll($member, $date, 'break', 31)) * $minute;
        $back = $goOut + (45 + $this->roll($member, $date, 'back', 16)) * $minute;

        return ['in' => $in, 'out' => $out, 'break' => [$goOut, $back], 'flg' => $flg, 'kubun' => $kubun, 'status' => $status, 'bikou' => $bikou];
    }

    /** Número fijo en [0, max) para el empleado, el día y el aspecto */
    private function roll(Staff $member, CarbonImmutable $date, string $aspect, int $max): int
    {
        return crc32("{$member->cd}|{$date->toDateString()}|{$aspect}") % $max;
    }

    /** インフォメーション para todas las tiendas; se reconocen por el texto */
    private function notices(): void
    {
        foreach (self::NOTICES as [$title, $comment]) {
            if (Information::query()->where('comment', $comment)->exists()) {
                continue;
            }
            Information::create([
                'shop_cd' => null, 'title' => $title, 'comment' => $comment, 'belongpost' => '', 'employmentsystem' => '',
                'officialposition' => '', 'staff_name' => '店長', 'disp_flg' => '1',
            ]);
        }
    }
}
