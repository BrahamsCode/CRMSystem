<?php

namespace App\Services\Reservations;

use App\Models\Operaciones\ShopWork;
use App\Models\Reservations\SimpleReservation;
use App\Models\Shop;
use App\Services\Operaciones\LegacyHolidays;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Listados de 予約管理 (legacy category9/yoyaku): 店舗毎予約数 (view_shop.php), 予約一覧 de tb_reserve
 * (yoyaku_list_calendar.php), 年間カレンダー (pop_calendar.php) y 予約一覧出力 / CSV (print/yoyaku_list_print.php,
 * print/csv_download.php).
 */
class ReservationListService
{
    public const YOUBI = ['日', '月', '火', '水', '木', '金', '土'];

    public function __construct(private readonly ReservationFlowService $flow) {}

    // ---------------------------------------------------------------- 店舗毎予約数

    /**
     * view_shop.php: reservas activas (`tb_yoyaku.disp_flg = '1'`) por tienda del empleado y día del mes.
     *
     * @return array<int, array<string, int>> [shop_cd]['Y/m/d'] => número
     */
    public function countsByShop(Carbon $month): array
    {
        $rows = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->join('tb_staff as tb3', 'tb2.tb_staff_cd', '=', 'tb3.cd')
            ->where('tb1.disp_flg', '1')
            ->where('tb2.moushikomi_date', 'like', $month->format('Y/m') . '%')
            ->groupBy('tb3.shop_cd', 'tb2.moushikomi_date')
            ->select('tb3.shop_cd', 'tb2.moushikomi_date', DB::raw('count(*) as count'))
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row->shop_cd][(string) $row->moushikomi_date] = (int) $row->count;
        }

        return $counts;
    }

    /** Tiendas de view_shop.php: `disp_flg = '1'`, orden cd (cd => kaishamei) */
    public function activeShops(): Collection
    {
        return DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('kaishamei', 'cd');
    }

    /**
     * Cabecera de días del mes: [['day' => 1, 'youbi' => '月', 'color' => '#000000'], …]
     *
     * @return list<array{day: int, youbi: string, color: string}>
     */
    public function monthDays(Carbon $month): array
    {
        $days = [];
        for ($day = $month->copy()->startOfMonth(), $i = 1; $i <= $month->daysInMonth; $i++, $day->addDay()) {
            $youbi = self::YOUBI[$day->dayOfWeek];
            $days[] = [
                'day' => $i,
                'youbi' => $youbi,
                'color' => match ($youbi) { '日' => '#FF3333', '土' => '#3333FF', default => '#000000' },
            ];
        }

        return $days;
    }

    // ---------------------------------------------------------------- 予約一覧 (tb_reserve)

    /**
     * yoyaku_list_calendar.php: semanas del mes con el número de reservas simples (`tb_reserve`) de cada día.
     * Master ve todas las tiendas no borradas; el resto solo la de su sesión.
     *
     * @return array{shops: Collection, weeks: list<list<array<string, mixed>|null>>}
     */
    public function simpleCalendar(Carbon $month, ?int $onlyShopCd, ?Carbon $today = null): array
    {
        $today ??= now('Asia/Tokyo')->startOfDay();
        $shops = Shop::query()
            ->when($onlyShopCd !== null, fn ($q) => $q->where('cd', $onlyShopCd))
            ->orderBy('cd')
            ->get(['cd', 'kaishamei']);

        // tb_shop_work del mes ('Y/n'); el legacy no filtra por tienda: con una sola tienda se usa la suya
        $shopWork = ShopWork::query()
            ->where('syukkinbi', $month->format('Y') . '/' . $month->format('n'))
            ->when($shops->count() === 1, fn ($q) => $q->where('shop_cd', $shops->first()->cd))
            ->first();

        $counts = $this->simpleCountsByDay($month, $shops->count() === 1 ? (int) $shops->first()->cd : null, $shops->isEmpty());

        $wdayFirst = $month->copy()->startOfMonth()->dayOfWeek;
        $mdayLast = $month->daysInMonth;
        $weeks = [];
        for ($i = 0, $weekMax = (int) ceil(($mdayLast + $wdayFirst) / 7); $i < $weekMax; $i++) {
            $week = [];
            for ($j = 0; $j < 7; $j++) {
                $day = 7 * $i + $j - $wdayFirst + 1;
                if ($day < 1 || $day > $mdayLast) {
                    $week[] = null;

                    continue;
                }
                $date = $month->copy()->startOfMonth()->day($day);
                $holiday = LegacyHolidays::code((int) $month->year, (int) $month->month, $day);
                $week[] = [
                    'day' => $day,
                    'dayClass' => $date->isSameDay($today) ? 'nowday' : ($j === 0 || $holiday !== 0 ? 'holiday' : 'normalday'),
                    'closed' => $shopWork !== null && ! $shopWork->isOpen($day),
                    'count' => $counts[$day] ?? 0,
                    'ymd' => $date->format('Ymd'),
                    'dd' => $month->format('Y') . '/' . $month->format('n') . '/' . $day,
                ];
            }
            $weeks[] = $week;
        }

        return ['shops' => $shops, 'weeks' => $weeks];
    }

    /**
     * Reservas de tb_reserve por día del mes (`reserve_time` epoch, de 00:00:00 a 23:59:59 en Asia/Tokyo).
     * `$shopCd` null = todas las tiendas; `$none` = sin tiendas (el legacy contaba `shop_cd = ''`).
     *
     * @return array<int, int> [día => número]
     */
    private function simpleCountsByDay(Carbon $month, ?int $shopCd, bool $none): array
    {
        if ($none) {
            return [];
        }
        $start = $month->copy()->startOfMonth()->startOfDay();
        $end = $month->copy()->endOfMonth()->endOfDay();
        $epoch = "CASE WHEN reserve_time ~ '^[0-9]+$' THEN CAST(reserve_time AS BIGINT) END";

        $times = SimpleReservation::query()
            ->when($shopCd !== null, fn ($q) => $q->where('shop_cd', (string) $shopCd))
            ->whereRaw("{$epoch} BETWEEN ? AND ?", [$start->getTimestamp(), $end->getTimestamp()])
            ->pluck('reserve_time');

        $counts = [];
        foreach ($times as $time) {
            $day = (int) Carbon::createFromTimestamp((int) $time, 'Asia/Tokyo')->day;
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }

        return $counts;
    }

    // ---------------------------------------------------------------- 年間カレンダー

    /**
     * pop_calendar.php: las 12 hojas del año. Cada hoja: 'title' (Y年n月) y 'weeks' (filas de 7 celdas,
     * null = vacía; si no ['day', 'class' (sunday|saturday|weekday + today/selectday), 'ymd']).
     *
     * @return list<array{title: string, weeks: list<list<array<string, mixed>|null>>}>
     */
    public function yearCalendar(int $year, ?Carbon $selected, ?Carbon $today = null): array
    {
        $today ??= now('Asia/Tokyo')->startOfDay();
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $first = Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Tokyo');
            $cells = array_fill(0, $first->dayOfWeek, null);
            for ($day = $first->copy(); $day->month === $m; $day->addDay()) {
                $class = match ($day->dayOfWeek) { 0 => 'sunday', 6 => 'saturday', default => 'weekday' };
                $class .= $day->isSameDay($today) ? ' today' : '';
                $class .= $selected !== null && $day->isSameDay($selected) ? ' selectday' : '';
                $cells[] = ['day' => $day->day, 'class' => $class, 'ymd' => $day->format('Ymd')];
            }
            while (count($cells) % 7 !== 0) {
                $cells[] = null;
            }
            $months[] = ['title' => $year . '年' . $m . '月', 'weeks' => array_chunk($cells, 7)];
        }

        return $months;
    }

    // ---------------------------------------------------------------- 予約一覧出力 / CSV

    /**
     * Columnas opcionales de yoyaku_list_print.php / csv_download.php (`koumoku_select = '1'` de cYoyaku):
     * ['kana' => bool, 'tel' => bool, 'mail' => bool, 'free' => [N => etiqueta], 'shimei' => manageWord('指名')]
     */
    public function printColumns(): array
    {
        $koumoku = $this->flow->koumoku();
        $selected = fn (string $key) => (string) ($koumoku[$key]['koumoku_select'] ?? '') === '1';
        $free = [];
        foreach ($koumoku as $key => $row) {
            if (preg_match('/^free_koumoku_([0-9]+)/', $key, $m) && (string) $row['koumoku_select'] === '1') {
                $free[(int) $m[1]] = (string) $row['tb_koumoku_name'];
            }
        }

        return [
            'kana' => $selected('name_kana'),
            'tel' => $selected('tel'),
            'mail' => $selected('mail'),
            'free' => $free,
            'shimei' => $this->flow->manageWord('指名', 'c9_yoyaku[スタッフ]'),
        ];
    }

    /**
     * Reservas visibles del día de una tienda (empleados de la tienda), orden start_time, ya resueltas para
     * imprimir: time ('HH:MM ～ HH:MM', horas > 24 tras medianoche), name, kana, tel, mail (los del socio si
     * existe), free [N => texto], staff (nombre del empleado de la franja) y menu ('カット / シャンプー').
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function printRows(int $shopCd, string $date): Collection
    {
        $rows = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->where('tb1.disp_flg', '1')
            ->where('tb1.hyouji_flg', '1')
            ->where('tb2.disp_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->where('tb2.moushikomi_date', $date)
            ->whereIn('tb2.tb_staff_cd', DB::table('tb_staff')->select('cd')->where('shop_cd', $shopCd))
            ->orderBy('tb1.start_time')
            ->select('tb1.*', 'tb2.tb_staff_cd', 'tb2.moushikomi_date')
            ->get();

        $customers = DB::table('tb_customer')
            ->whereIn('cd', $rows->pluck('tb_customer_cd')->filter()->map(fn ($cd) => (int) $cd)->unique()->all())
            ->get(['cd', 'name', 'name_kana', 'tel', 'email1'])
            ->keyBy('cd');
        $staff = DB::table('tb_staff')->whereIn('cd', $rows->pluck('tb_staff_cd')->filter()->unique()->all())->pluck('name', 'cd');
        $menuCds = $rows->flatMap(fn ($row) => $this->menuCds($row->base_tb_yoyaku_koumoku_master_cd . ',' . $row->option_tb_yoyaku_koumoku_master_cd))->unique()->all();
        $menus = DB::table('tb_yoyaku_koumoku_master')->whereIn('cd', $menuCds)->orderBy('rank')->get(['cd', 'name']);

        return $rows->map(function ($row) use ($customers, $staff, $menus) {
            $customer = $row->tb_customer_cd ? $customers->get((int) $row->tb_customer_cd) : null;
            $own = $this->menuCds($row->base_tb_yoyaku_koumoku_master_cd . ',' . $row->option_tb_yoyaku_koumoku_master_cd);
            $free = [];
            foreach (explode('<>', (string) $row->free_koumoku) as $i => $value) {
                $free[$i + 1] = rawurldecode($value);
            }

            return [
                'time' => self::hourMinute((int) $row->start_time) . ' ～ ' . self::hourMinute((int) $row->end_time),
                'name' => (string) ($customer->name ?? $row->name),
                'kana' => (string) ($customer->name_kana ?? $row->name_kana),
                'tel' => (string) ($customer->tel ?? $row->tel),
                'mail' => (string) ($customer ? $customer->email1 : $row->mail),
                'free' => $free,
                'staff' => (string) ($staff[$row->tb_staff_cd] ?? ''),
                // ORDER BY rank de tb_yoyaku_koumoku_master
                'menu' => $menus->whereIn('cd', $own)->pluck('name')->implode(' / '),
            ];
        });
    }

    /** Segundos desde las 00:00 → 'HH:MM' (24 h o más si pasa de medianoche, como el legacy) */
    public static function hourMinute(int $seconds): string
    {
        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    /** '1,2' → [1, 2] (solo números) */
    private function menuCds(?string $list): array
    {
        return array_values(array_map('intval', array_filter(explode(',', (string) $list), fn ($cd) => is_numeric(trim($cd)))));
    }
}
