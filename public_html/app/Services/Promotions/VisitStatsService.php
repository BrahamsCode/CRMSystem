<?php

namespace App\Services\Promotions;

use App\Models\Customers\Visit;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 来店履歴管理 (system/category3/visit/): conteos de `tb_raiten_rireki` para
 * visit_stats2.php (来店履歴一覧), day_visit_list.php (日別), store_visit_list.php (個別来店履歴) y sus CSV.
 *
 * - `raiten_time` es epoch en texto: solo se cuentan valores numéricos (el legacy hacía CAST y fallaba con basura).
 * - Fechas en Asia/Tokyo, como el legacy (PHP con la zona de Japón).
 * - Todas las consultas pasan por el modelo `Visit` de S2, con su scope `COALESCE(disp_flg,'') <> '9'`
 *   (visit_stats2 ya lo filtraba; day/store_visit_list contaban también los borrados: se corrige).
 * - Los conteos por franja salen en una sola consulta con `SUM(CASE …)` (el legacy hacía una por celda).
 */
class VisitStatsService
{
    /** 営業開始時間 ($bussiness_start_hour) */
    public const START_HOUR = 5;

    public const ZONE = 'Asia/Tokyo';

    /** Epoch de la visita (NULL si `raiten_time` no es numérico) */
    public const TIME_SQL = "(CASE WHEN tb_raiten_rireki.raiten_time ~ '^[0-9]+$' THEN tb_raiten_rireki.raiten_time::bigint END)";

    /** Colores de 職業別 ($shokugyo_color) */
    public const JOB_COLORS = [
        '会社員' => '#1829CE',
        '自営・自由業' => '#C60094',
        '公務員' => '#D60000',
        '学生' => '#FF9C00',
        '専業主婦' => '#219C00',
        'フリーター' => '#840000',
        '無職' => '#5FA8FF',
        'その他' => '#CCCCCC',
        '不明' => '#333333',
    ];

    /** Opciones de 期間 de visit_stats2.php */
    public const STATS_PERIODS = ['当日', '月間', '年間', '過去全て'];

    /** Opciones de 期間 de day_visit_list.php y store_visit_list.php */
    public const LIST_PERIODS = ['今日', '今月', '今年'];

    // ------------------------------------------------------------------
    // Fechas
    // ------------------------------------------------------------------

    /** strtotime("Y/m/d H:00:00") en Asia/Tokyo (desborda como mktime: día 32 = 1 del mes siguiente) */
    public static function at(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0): int
    {
        return CarbonImmutable::create($year, 1, 1, 0, 0, 0, self::ZONE)
            ->addMonths($month - 1)->addDays($day - 1)->addHours($hour)->addMinutes($minute)->addSeconds($second)->timestamp;
    }

    public static function date(int $timestamp): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp($timestamp, self::ZONE);
    }

    /** Inicio del día de negocio (05:00) que contiene $timestamp: antes de las 05:00 es el de ayer */
    public static function businessDayStart(int $timestamp): int
    {
        $d = self::date($timestamp);
        $start = self::at($d->year, $d->month, $d->day, self::START_HOUR);

        return $timestamp >= $start ? $start : $start - 86400;
    }

    public static function daysInMonth(int $year, int $month): int
    {
        return CarbonImmutable::create($year, $month, 1, 0, 0, 0, self::ZONE)->daysInMonth;
    }

    // ------------------------------------------------------------------
    // Consultas base
    // ------------------------------------------------------------------

    /** Visitas vigentes, de una tienda (o de todas con null), entre dos epoch (incluidos) */
    public function visits(?int $shopCd, ?int $begin = null, ?int $end = null): Builder
    {
        return Visit::query()
            ->when($shopCd !== null, fn ($q) => $q->where('tb_raiten_rireki.shop_cd', $shopCd))
            ->when($begin !== null, fn ($q) => $q->whereRaw(self::TIME_SQL.' BETWEEN ? AND ?', [$begin, $end]));
    }

    /**
     * Conteo por franjas en una sola consulta.
     *
     * @param  list<array{0:int,1:int}>  $ranges  [inicio, fin] incluidos
     * @return list<int> un conteo por franja
     */
    public function countRanges(?int $shopCd, array $ranges): array
    {
        if ($ranges === []) {
            return [];
        }
        [$select, $bindings] = self::rangeColumns($ranges);
        $row = $this->visits($shopCd, min(array_column($ranges, 0)), max(array_column($ranges, 1)))
            ->toBase()->selectRaw($select, $bindings)->first();

        return array_map(fn ($i) => (int) ($row->{'c'.$i} ?? 0), array_keys($ranges));
    }

    /**
     * Conteo por franjas y por tienda (store_visit_list.php)
     *
     * @return array<int, list<int>> [shop_cd => conteos]
     */
    public function countRangesByShop(array $ranges): array
    {
        [$select, $bindings] = self::rangeColumns($ranges);
        $rows = $this->visits(null, min(array_column($ranges, 0)), max(array_column($ranges, 1)))
            ->toBase()->selectRaw('tb_raiten_rireki.shop_cd, '.$select, $bindings)
            ->groupBy('tb_raiten_rireki.shop_cd')->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->shop_cd] = array_map(fn ($i) => (int) ($row->{'c'.$i} ?? 0), array_keys($ranges));
        }

        return $result;
    }

    private static function rangeColumns(array $ranges): array
    {
        $columns = [];
        $bindings = [];
        foreach (array_values($ranges) as $i => [$begin, $end]) {
            $columns[] = 'SUM(CASE WHEN '.self::TIME_SQL." BETWEEN ? AND ? THEN 1 ELSE 0 END) AS c$i";
            array_push($bindings, $begin, $end);
        }

        return [implode(', ', $columns), $bindings];
    }

    /** 性別: otoko_total, onna_total, count, fumei_total (func_GetVisitCounts) */
    public function sexCounts(int $shopCd, int $begin, int $end): array
    {
        $row = $this->visits($shopCd, $begin, $end)->toBase()->selectRaw(
            "SUM(CASE WHEN customer_sex = '男性' THEN 1 ELSE 0 END) AS otoko_total,
             SUM(CASE WHEN customer_sex = '女性' THEN 1 ELSE 0 END) AS onna_total,
             COUNT(*) AS count"
        )->first();

        $result = ['otoko_total' => (int) $row->otoko_total, 'onna_total' => (int) $row->onna_total, 'count' => (int) $row->count];
        $result['fumei_total'] = $result['count'] - $result['otoko_total'] - $result['onna_total'];

        return $result;
    }

    /** 年代別 (customer_undef, customer_0, customer_20 … customer_70) */
    public function ageCounts(int $shopCd, int $begin, int $end): array
    {
        $row = $this->visits($shopCd, $begin, $end)->toBase()->selectRaw(
            'SUM(CASE WHEN COALESCE(customer_age, -1) = -1 THEN 1 ELSE 0 END) AS customer_undef,
             SUM(CASE WHEN customer_age BETWEEN 0 AND 19 THEN 1 ELSE 0 END) AS customer_0,
             SUM(CASE WHEN customer_age BETWEEN 20 AND 29 THEN 1 ELSE 0 END) AS customer_20,
             SUM(CASE WHEN customer_age BETWEEN 30 AND 39 THEN 1 ELSE 0 END) AS customer_30,
             SUM(CASE WHEN customer_age BETWEEN 40 AND 49 THEN 1 ELSE 0 END) AS customer_40,
             SUM(CASE WHEN customer_age BETWEEN 50 AND 59 THEN 1 ELSE 0 END) AS customer_50,
             SUM(CASE WHEN customer_age BETWEEN 60 AND 69 THEN 1 ELSE 0 END) AS customer_60,
             SUM(CASE WHEN customer_age > 69 THEN 1 ELSE 0 END) AS customer_70'
        )->first();

        return array_map('intval', (array) $row);
    }

    /** 職業別: [['label', count, color], …] ('' = 不明) */
    public function jobCounts(int $shopCd, int $begin, int $end): array
    {
        return $this->visits($shopCd, $begin, $end)->toBase()
            ->selectRaw("count(*) AS count, coalesce(customer_job, '') AS customer_job")
            ->groupByRaw("coalesce(customer_job, '')")->get()
            ->map(function ($row) {
                $job = $row->customer_job === '' ? '不明' : $row->customer_job;

                return [$job, (int) $row->count, self::JOB_COLORS[$job] ?? null];
            })->all();
    }

    /** Clientes con visitas en el rango: count, user_cd, user_id, name (+ kanri_id y última visita si se piden) */
    public function visitors(?int $shopCd, int $begin, int $end, bool $withLast = false)
    {
        $query = $this->visits($shopCd, $begin, $end)->toBase()
            ->join('tb_customer', 'tb_raiten_rireki.user_cd', '=', 'tb_customer.cd')
            ->selectRaw('count(tb_raiten_rireki.user_cd) AS count, tb_raiten_rireki.user_cd, tb_raiten_rireki.user_id, tb_customer.name')
            ->groupBy('tb_raiten_rireki.user_cd', 'tb_raiten_rireki.user_id', 'tb_customer.name');

        if ($withLast) {
            $query->selectRaw('max('.self::TIME_SQL.') AS last_raiten, tb_customer.kanri_id')
                ->groupBy('tb_customer.kanri_id')
                ->orderByDesc('last_raiten');
        } else {
            $query->orderBy('tb_raiten_rireki.user_cd');
        }

        return $query;
    }

    // ------------------------------------------------------------------
    // Tiendas
    // ------------------------------------------------------------------

    /** Todas las tiendas vigentes (orden cd) o solo la de la sesión */
    public function shops(bool $all, ?int $shopCd): Collection
    {
        return Shop::query()->when(! $all, fn ($q) => $q->where('cd', (int) $shopCd))->orderBy('cd')->get(['cd', 'kaishamei']);
    }

    // ------------------------------------------------------------------
    // visit_stats2.php
    // ------------------------------------------------------------------

    /**
     * Datos de 来店履歴一覧. $in = shop_cd, kikan (0 当日 / 1 月間 / 2 年間 / 3 過去全て), udate, disp, kensu, page.
     * Un staff que no es master solo ve su tienda (el legacy aceptaba cualquier shop_cd de la URL).
     */
    public function stats(array $in, bool $isMaster, ?int $sessionShop, ?int $now = null): array
    {
        $now ??= CarbonImmutable::now()->getTimestamp();
        $shopCd = $isMaster && (string) ($in['shop_cd'] ?? '') !== '' ? (int) $in['shop_cd'] : (int) $sessionShop;
        $kikan = in_array((string) ($in['kikan'] ?? ''), ['0', '1', '2', '3'], true) ? (int) $in['kikan'] : 0;
        $udate = is_numeric($in['udate'] ?? null) ? (int) $in['udate'] : null;
        $disp = (int) ($in['disp'] ?? 0);
        $kensu = max(1, (int) ($in['kensu'] ?? 0) ?: 10);
        $page = max(1, (int) ($in['page'] ?? 0) ?: 1);

        if ($udate === null && $kikan === 0) {
            // 当日 sin fecha: el día de negocio en curso y su lista de clientes (el legacy tomaba siempre hoy a las 05:00,
            // también de madrugada, cuando el día de negocio en curso es el de ayer)
            $selectedDay = self::businessDayStart($now);
            $disp = 2;
        } else {
            $selectedDay = $udate ?? $now;
        }

        $d = self::date($selectedDay);
        [$year, $month, $day] = [$d->year, $d->month, $d->day];
        $h = self::START_HOUR;

        $rows = [];       // [título, inicio, onclick]
        $ranges = [];
        $dispTitle = $titleStr = $modoruDisp = '';
        $startTime = $endTime = null;

        switch ($kikan) {
            case 0: // 時間別
                $dispTitle = sprintf('%04d年%02d月%02d日', $year, $month, $day);
                $begin = self::businessDayStart($selectedDay);
                $startTime = $begin;
                $endTime = $begin + 86399;
                $titleStr = '時間別表&nbsp;&nbsp;'.self::date($begin)->format('Y年m月d日');
                for ($i = $h; $i < $h + 24; $i++) {
                    $rows[] = [($i < 24 ? $i : $i - 24).'時', "ChangeKikan(1,0,$begin)"];
                    $ranges[] = [$begin, $begin + 3599];
                    $begin += 3600;
                }
                $modoruDisp = '月間表へ戻る';
                break;

            case 1: // 日別
                $dispTitle = sprintf('%04d年%02d月', $year, $month);
                $titleStr = '日別表&nbsp;&nbsp;'.$dispTitle;
                $lastDay = self::daysInMonth($year, $month);
                for ($i = 1; $i <= $lastDay; $i++) {
                    $begin = self::at($year, $month, $i, $h);
                    $rows[] = [$i.'日', "ChangeKikan(2,0,$begin)"];
                    $ranges[] = [$begin, $begin + 86399];
                }
                $startTime = self::at($year, $month, 1, $h);
                $endTime = self::at($year, $month, $lastDay, $h) + 86399;
                $modoruDisp = '年間表へ戻る';
                break;

            case 2: // 月別
                $dispTitle = sprintf('%04d年', $year);
                $titleStr = '月別表&nbsp;&nbsp;'.$dispTitle;
                for ($i = 1; $i <= 12; $i++) {
                    $begin = self::at($year, $i, 1, $h);
                    $end = self::at($year, $i, self::daysInMonth($year, $i), 23, 59, 59) + $h * 3600;
                    $rows[] = [$i.'月', "ChangeKikan(0,1,$begin)"];
                    $ranges[] = [$begin, $end];
                }
                $startTime = self::at($year, 1, 1, $h);
                $endTime = self::at($year + 1, 1, 1, $h) - 1;
                $modoruDisp = '過去全てへ戻る';
                break;

            case 3: // 年別
                $dispTitle = '過去';
                $minMax = $this->visits($shopCd)->toBase()
                    ->selectRaw('min('.self::TIME_SQL.') AS min, max('.self::TIME_SQL.') AS max')->first();
                if ($minMax->min === null) {
                    break; // データがない場合
                }
                $beginYear = self::date((int) $minMax->min)->year;
                if ((int) $minMax->min < self::at($beginYear, 1, 1, $h)) {
                    $beginYear--;
                }
                $endYear = self::date((int) $minMax->max)->year;
                if ((int) $minMax->max < self::at($endYear, 1, 1, $h)) {
                    $endYear--;
                }
                $titleStr = '年別表';
                for ($i = $beginYear; $i <= $endYear; $i++) {
                    $begin = self::at($i, 1, 1, $h);
                    $rows[] = [$i.'年', "ChangeKikan(0,2,$begin)"];
                    $ranges[] = [$begin, self::at($i + 1, 1, 1, $h) - 1];
                }
                $startTime = self::at($beginYear, 1, 1, $h);
                $endTime = self::at($endYear + 1, 1, 1, $h) - 1;
                break;
        }

        $counts = $this->countRanges($shopCd, $ranges);
        $data = [];
        foreach ($rows as $i => [$title, $onclick]) {
            $data[] = ['title' => $title, 'count' => $counts[$i], 'onclick' => $onclick];
        }
        $total = array_sum($counts);
        $max = $counts === [] ? 0 : max($counts);

        $graph = ['otoko_total' => 0, 'onna_total' => 0, 'count' => 0, 'fumei_total' => 0];
        $ages = array_fill_keys(['customer_undef', 'customer_0', 'customer_20', 'customer_30', 'customer_40', 'customer_50', 'customer_60', 'customer_70'], 0);
        $jobs = [];
        if ($startTime !== null) {
            // Las franjas cubren todo [startTime, endTime]: el total por sexo es la suma de func_SetGraphData
            $graph = $this->sexCounts($shopCd, $startTime, $endTime);
            $ages = $this->ageCounts($shopCd, $startTime, $endTime);
            $jobs = $this->jobCounts($shopCd, $startTime, $endTime);
        }
        $ratio = fn (int $n) => $graph['count'] > 0 && $total > 0 ? number_format($n * 100 / $total, 1) : 0;

        $result = [
            'shopCd' => $shopCd,
            'kikan' => $kikan,
            'udate' => $udate,
            'disp' => $disp,
            'kensu' => $kensu,
            'page' => $page,
            'selectedDay' => $selectedDay,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'dispTitle' => $dispTitle,
            'titleStr' => $titleStr,
            'data' => $data,
            'total' => $total,
            'widthMultiply' => $max > 0 ? 500 / $max : 0,
            'graph' => $graph,
            'ages' => $ages,
            'jobs' => $jobs,
            'ratios' => ['otoko' => $ratio($graph['otoko_total']), 'onna' => $ratio($graph['onna_total']), 'fumei' => $ratio($graph['fumei_total'])],
            'modoruDisp' => $modoruDisp,
            'modoruKikan' => $kikan + 1,
            'list' => null,
        ];

        if ($disp === 1 || $disp === 2) {
            $seconds = $disp === 1 ? 3600 : 86400;
            $query = $this->visitors($shopCd, $selectedDay, $selectedDay + $seconds - 1, true);
            $count = DB::query()->fromSub($query, 'tb')->count();
            $maxPage = (int) ceil($count / $kensu);
            $from = ($page - 1) * $kensu;
            $result['list'] = [
                'dispDate' => self::date($selectedDay)->format($disp === 1 ? 'Y/m/d H:i' : 'Y/m/d'),
                'rows' => $query->limit($kensu)->offset($from)->get(),
                'count' => $count,
                'from' => $from,
                'last' => min($from + $kensu, $count),
                'maxPage' => $maxPage,
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // day_visit_list.php
    // ------------------------------------------------------------------

    /**
     * Datos de 来店履歴一覧 (日別). $in = shop_cd, kikan (0 今日 / 1 今月 / 2 今年), date, mmm, ddd, hhh, kensu, page.
     * Sin shop_cd no calcula nada, como el legacy.
     */
    public function day(array $in, bool $isMaster, ?int $sessionShop, ?int $now = null): array
    {
        $now ??= CarbonImmutable::now()->getTimestamp();
        $kikan = in_array((string) ($in['kikan'] ?? ''), ['0', '1', '2'], true) ? (int) $in['kikan'] : 0;
        $result = ['kikan' => $kikan, 'shopCd' => null, 'selectedDay' => null, 'year' => null, 'kensu' => 10, 'page' => 1,
            'mmm' => '', 'ddd' => '', 'hhh' => '', 'table' => null, 'list' => null, 'hasVisits' => false];

        if ((string) ($in['shop_cd'] ?? '') === '') {
            return $result;
        }

        $shopCd = $isMaster ? (int) $in['shop_cd'] : (int) $sessionShop;
        $selectedDay = is_numeric($in['date'] ?? null) ? (int) $in['date'] : $now;
        $kensu = max(1, (int) ($in['kensu'] ?? 0) ?: 10);
        $page = max(1, (int) ($in['page'] ?? 0) ?: 1);
        $hhh = (string) ($in['hhh'] ?? '') !== '' ? (int) $in['hhh'] : null;
        $ddd = (int) ($in['ddd'] ?? 0);
        $mmm = (int) ($in['mmm'] ?? 0);
        $h = self::START_HOUR;

        $d = self::date($selectedDay);
        [$year, $month, $day] = [$d->year, $d->month, $d->day];

        // Hora del día de negocio: 0〜4 時 son la madrugada del día siguiente (el legacy buscaba la del mismo día)
        $hourStart = fn (int $y, int $m, int $dd, int $hour) => self::at($y, $m, $dd + ($hour < $h ? 1 : 0), $hour);

        $table = ['days' => [], 'months' => []];
        switch ($kikan) {
            case 0: // 今日
                $begin = self::at($year, $month, $day, $h);
                if ($selectedDay === $now && $now < $begin) {
                    $begin -= 86400; // de madrugada: el día de negocio de ayer
                    $d = self::date($begin);
                    [$year, $month, $day] = [$d->year, $d->month, $d->day];
                }
                $end = $begin + 86399;
                $days = [$day];
                $listRange = $hhh !== null ? [$hourStart($year, $month, $day, $hhh), $hourStart($year, $month, $day, $hhh) + 3599] : null;
                break;

            case 1: // 今月
                $begin = self::at($year, $month, 1, $h);
                $last = self::daysInMonth($year, $month);
                $end = self::at($year, $month, $last, 23, 59, 59) + $h * 3600;
                $days = range(1, $last);
                $listRange = $hhh !== null ? [$hourStart($year, $month, $ddd, $hhh), $hourStart($year, $month, $ddd, $hhh) + 3599] : null;
                break;

            default: // 今年
                $begin = self::at($year, 1, 1, $h);
                $end = self::at($year + 1, 1, 1, 4, 59, 59);
                $days = [];
                $listRange = $hhh !== null ? [self::at($year, $mmm, $ddd, $h), self::at($year, $mmm, $ddd, $h) + 86399] : null;
                break;
        }

        if ($kikan !== 2) {
            // Filas = días, columnas = 24 horas desde las 05:00
            $ranges = [];
            foreach ($days as $dd) {
                $start = self::at($year, $month, $dd, $h);
                for ($i = 0; $i < 24; $i++) {
                    $ranges[] = [$start + $i * 3600, $start + $i * 3600 + 3599];
                }
            }
            $counts = array_chunk($this->countRanges($shopCd, $ranges), 24);
            foreach ($days as $n => $dd) {
                $table['days'][] = ['day' => $dd, 'date' => self::date(self::at($year, $month, $dd, $h))->format('Y/m/d'), 'counts' => $counts[$n], 'total' => array_sum($counts[$n])];
            }
            $table['hourTotals'] = array_map(fn ($i) => array_sum(array_column(array_column($table['days'], 'counts'), $i)), range(0, 23));
        } else {
            // Filas = meses, columnas = 31 días (05:00 → 04:59:59 del día siguiente)
            $ranges = [];
            $index = [];
            for ($m = 1; $m <= 12; $m++) {
                $last = self::daysInMonth($year, $m);
                for ($i = 1; $i <= $last; $i++) {
                    $start = self::at($year, $m, $i, $h);
                    $index[$m][$i] = count($ranges);
                    $ranges[] = [$start, $start + 86399];
                }
            }
            $counts = $this->countRanges($shopCd, $ranges);
            $dayTotals = array_fill(1, 31, 0);
            for ($m = 1; $m <= 12; $m++) {
                $cells = [];
                for ($i = 1; $i <= 31; $i++) {
                    $cells[$i] = isset($index[$m][$i]) ? $counts[$index[$m][$i]] : null;
                    $dayTotals[$i] += (int) $cells[$i];
                }
                $table['months'][] = ['month' => $m, 'counts' => $cells, 'total' => array_sum(array_filter($cells))];
            }
            $table['dayTotals'] = $dayTotals;
        }
        $table['total'] = $kikan !== 2 ? array_sum($table['hourTotals']) : array_sum($table['dayTotals']);

        $hasVisits = $this->visits($shopCd, $begin, $end)->exists();

        $list = null;
        if ($listRange !== null) {
            $query = $this->visitors($shopCd, $listRange[0], $listRange[1]);
            $count = DB::query()->fromSub($query, 'tb')->count();
            $list = [
                'rows' => $query->forPage($page, $kensu)->get(),
                'count' => $count,
                'maxPage' => (int) ceil($count / $kensu),
            ];
        } else {
            $rows = $this->visitors($shopCd, $begin, $end)->get();
            $list = ['rows' => $rows, 'count' => $rows->count(), 'maxPage' => 0];
        }

        return [
            'kikan' => $kikan,
            'shopCd' => $shopCd,
            'selectedDay' => $selectedDay,
            'year' => $year,
            'kensu' => $kensu,
            'page' => $page,
            'mmm' => $in['mmm'] ?? '',
            'ddd' => $in['ddd'] ?? '',
            'hhh' => $in['hhh'] ?? '',
            'table' => $table,
            'list' => $list,
            'hasVisits' => $hasVisits,
        ];
    }

    // ------------------------------------------------------------------
    // store_visit_list.php
    // ------------------------------------------------------------------

    /**
     * Datos de 個別来店履歴: todas las tiendas, días naturales (00:00〜23:59), como el legacy.
     * kikan 0 = horas de hoy, 1 = días del mes, 2 = meses del año.
     */
    public function shopTable(int $kikan, ?int $selectedDay = null): array
    {
        $d = self::date($selectedDay ?? CarbonImmutable::now()->getTimestamp());
        [$year, $month, $day] = [$d->year, $d->month, $d->day];

        $columns = [];   // [título, inicio, fin]
        switch ($kikan) {
            case 0:
                $start = self::at($year, $month, $day);
                for ($i = 0; $i < 24; $i++) {
                    $columns[] = [$i.'時', $start + $i * 3600, $start + $i * 3600 + 3599];
                }
                [$begin, $end] = [$start, self::at($year, $month, $day, 23, 59, 59)];
                break;
            case 1:
                $last = self::daysInMonth($year, $month);
                for ($i = 1; $i <= $last; $i++) {
                    $columns[] = [$i.'日', self::at($year, $month, $i), self::at($year, $month, $i, 23, 59, 59)];
                }
                [$begin, $end] = [self::at($year, $month, 1), self::at($year, $month, $last, 23, 59, 59)];
                break;
            default:
                for ($i = 1; $i <= 12; $i++) {
                    $columns[] = [$i.'月', self::at($year, $i, 1), self::at($year, $i, self::daysInMonth($year, $i), 23, 59, 59)];
                }
                [$begin, $end] = [self::at($year, 1, 1), self::at($year, 12, 31, 23, 59, 59)];
                break;
        }

        $byShop = $this->countRangesByShop(array_map(fn ($c) => [$c[1], $c[2]], $columns));
        $rows = [];
        foreach ($this->shops(true, null) as $shop) {
            $counts = $byShop[(int) $shop->cd] ?? array_fill(0, count($columns), 0);
            $rows[] = ['cd' => (int) $shop->cd, 'name' => $shop->kaishamei, 'counts' => $counts, 'total' => array_sum($counts)];
        }
        $totals = array_map(fn ($i) => array_sum(array_column(array_column($rows, 'counts'), $i)), array_keys($columns));

        return [
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
            'total' => array_sum($totals),
            'begin' => $begin,
            'end' => $end,
        ];
    }
}
