<?php

namespace App\Services\Customers\Reports;

use App\Models\Customers\CustomField;
use App\Models\Customers\ReportLink;
use App\Models\Staff;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 集計ツール (`system/category1/shukei/`): la lista de informes (shukei_list.php, `tb_shukei_link`) y sus ventanas:
 * - model1 (*_search.php incluido): respuestas de un campo de 追加情報 de opciones (`tb_customer_sub.text_data`)
 *   guardadas con cada visita, por 男女/年代/職業, con gráfico; y el filtro para enviar メール (model1_select.php).
 * - model2: el mismo cruce con un campo numérico (`float_data`): 顧客数, 延べ数, 合計/平均/最大/最小.
 * - point1 / stamp1: 会計 de las visitas con movimiento de puntos (`tb_point_log`) o sellos (`tb_stamp_log`).
 * - point2: puntos dados y usados por tienda y día / mes / año (`tb_point_log`).
 *
 * Los filtros son los del legacy; los valores van como parámetros (el legacy los pegaba al SQL).
 */
class CustomerReportService
{
    /** $typeArray del legacy (índice = cond) */
    public const TYPES = ['男女', '年代', '職業'];

    /** Campo de 追加情報 por defecto de cada informe («とりあえずベターで！！！！») */
    public const DEFAULT_FORM = ['model1' => 53, 'model2' => 120];

    /** Columna de agrupación por cond y su nombre para «不明» */
    private const BREAKDOWN = [
        '0' => ['field' => 'sex', 'disp' => '性別'],
        '1' => ['field' => 'age', 'disp' => '年齢'],
        '2' => ['field' => 'job', 'disp' => '職業'],
    ];

    // ------------------------------------------------------------------ comunes

    /** shukei_list.php: enlaces visibles por rank */
    public function links(): Collection
    {
        return ReportLink::query()->where('disp_flg', '1')->orderBy('rank')->get();
    }

    /** Tiendas de los selectores: disp_flg != '9' ORDER BY cd (un NULL no sale, como el legacy) */
    public function shops(): Collection
    {
        return DB::table('tb_shop')->where('disp_flg', '<>', '9')->orderBy('cd')->pluck('kaishamei', 'cd');
    }

    /** Año del primer dato (MIN de la columna epoch), o el actual */
    public function minYear(string $table, string $column): int
    {
        $min = (int) DB::table($table)->selectRaw("MIN(NULLIF({$column}::text, '')::bigint) AS m")->value('m');

        return $min > 0 ? (int) Carbon::createFromTimestamp($min, 'Asia/Tokyo')->format('Y') : (int) now('Asia/Tokyo')->format('Y');
    }

    /** Campo de 追加情報 del informe (?f=, por defecto el del legacy); null = 「カスタマイズデータがありません！！！」 */
    public function searchForm(string $report, mixed $f): ?CustomField
    {
        $cd = (string) $f === '' ? self::DEFAULT_FORM[$report] : (int) $f;

        return CustomField::withDeleted()->find($cd);
    }

    /** model1 solo admite campos de opciones: tipo de gráfico (pie / bar) o null (「カスタマイズデータから集計を出しません！！！」) */
    public static function graphType(CustomField $form): ?string
    {
        return match ((string) $form->form_type) {
            'セレクト', 'ラジオボタン' => 'pie',
            'チェックボックス' => 'bar',
            default => null,
        };
    }

    // ------------------------------------------------------------------ model1 / model2 / point1 / stamp1

    /**
     * Aplica los filtros comunes de model1/model2/point1/stamp1 (*_search.php) y devuelve los textos y el
     * cruce elegido: ['field', 'typeDisp', 'title', 'titleB', 'rirekiTitle', 'addRirekiStr'].
     * Espera la consulta con los alias del legacy: tb0 = tb_raiten_rireki, tb1 = tb_customer, tb2 = la tabla
     * del dato (su touroku_date da la edad).
     */
    public function applyVisitFilters(Builder $query, array $in, Staff $staff, Collection $shops): array
    {
        $ageExpr = self::conditionExpr('age');
        $meta = ['field' => '', 'typeDisp' => '', 'title' => '', 'titleB' => '', 'rirekiTitle' => '', 'addRirekiStr' => ''];

        if ((string) ($in['f'] ?? '') !== '') {
            $meta['addRirekiStr'] .= '&f='.rawurlencode((string) $in['f']);
        }

        // 登録店舗
        if ($staff->isMaster()) {
            if ((string) ($in['touroku_shop_cd'] ?? '') !== '') {
                $query->where('tb1.tb_shop_cd', (int) $in['touroku_shop_cd']);
            }
        } else {
            $query->where('tb1.tb_shop_cd', (int) $staff->shop_cd);
        }

        // Filtro por un valor del cruce (enlaces 男女別/年代別/職業別 de cada bloque); basta con que exista la clave
        if (array_key_exists('sex', $in)) {
            $sex = (string) $in['sex'];
            $meta['title'] = $sex === '' ? '性別不明・' : $sex.'・';
            $query->whereRaw(self::conditionExpr('sex').' = ?', [$sex]);
        } elseif (array_key_exists('age', $in)) {
            $meta['title'] = (string) $in['age'] === '-1' ? '年齢不明・' : $in['age'].'年代・';
            $query->whereRaw($ageExpr.' = ?', [(int) $in['age']]);
        } elseif (array_key_exists('job', $in)) {
            $job = (string) $in['job'];
            $meta['title'] = $job === '' ? '職業不明・' : $job.'・';
            $query->whereRaw(self::conditionExpr('job').' = ?', [$job]);
        }

        // 集計タイプ
        $cond = (string) ($in['cond'] ?? '');
        if (isset(self::BREAKDOWN[$cond])) {
            $field = self::BREAKDOWN[$cond]['field'];
            $expr = self::conditionExpr($field);
            $query->selectRaw("{$expr} AS {$field}")->groupByRaw($expr)->orderBy($field);
            $meta['field'] = $field;
            $meta['typeDisp'] = self::BREAKDOWN[$cond]['disp'];
            $meta['titleB'] = self::TYPES[(int) $cond].'別';
        }

        // 来店日時 (tb0.raiten_time) y 来店店舗
        foreach (['start' => ['>=', 'から '], 'end' => ['<', 'まで ']] as $side => [$op, $suffix]) {
            $time = self::datetime($in, "rireki_{$side}_year", "rireki_{$side}_month", "rireki_{$side}_day", "rireki_{$side}_time");
            if ($time === null) {
                continue;
            }
            $query->whereRaw("NULLIF(tb0.raiten_time, '')::bigint {$op} ?", [$time->getTimestamp()]);
            foreach (['year', 'month', 'day', 'time'] as $part) {
                $meta['addRirekiStr'] .= "&rireki_{$side}_{$part}=".rawurlencode((string) ($in["rireki_{$side}_{$part}"] ?? ''));
            }
            $meta['rirekiTitle'] .= $time->format('Y年m月d日 H時').$suffix;
        }

        if ((string) ($in['shop_cd'] ?? '') !== '') {
            $query->where('tb0.shop_cd', (int) $in['shop_cd']);
            $meta['addRirekiStr'] .= '&shop_cd='.rawurlencode((string) $in['shop_cd']);
            $meta['rirekiTitle'] .= ($shops[(int) $in['shop_cd']] ?? '').'に';
        }

        return $meta;
    }

    /** Expresiones de $conditionArray del legacy (la edad, por década, a la fecha del dato de tb2) */
    public static function conditionExpr(string $field): string
    {
        return match ($field) {
            'sex' => "COALESCE(tb1.kojin_sex, '')",
            'age' => "COALESCE((EXTRACT(DECADE FROM AGE(TO_TIMESTAMP(NULLIF(tb2.touroku_date::text, '')::bigint), TO_TIMESTAMP(tb1.kojin_birth_date)))*10)::int, -1)",
            'job' => "COALESCE(tb1.job, '')",
        };
    }

    /** Clave de cada bloque como el legacy: vacío o -1 → «<tipo>不明», edad → «30代» */
    private static function groupKey(array $meta, mixed $value): string
    {
        $value = (string) ($value ?? '');
        if ($value === '' || $value === '-1') {
            return $meta['typeDisp'].'不明';
        }

        return $meta['typeDisp'] === '年齢' ? $value.'代' : $value;
    }

    /** Valor del cruce en la fila (null sin cond) */
    private static function groupValue(object $row, array $meta): mixed
    {
        return $meta['field'] !== '' ? ($row->{$meta['field']} ?? null) : null;
    }

    /** FROM de model1/model2: visitas con su registro de 追加情報 (mismo grupo interno) */
    private function customFieldQuery(CustomField $form): Builder
    {
        return DB::table('tb_raiten_rireki as tb0')
            ->join('tb_customer as tb1', 'tb0.user_cd', '=', 'tb1.cd')
            ->join('tb_customer_sub as tb2', function ($join) {
                $join->on('tb0.user_cd', '=', 'tb2.tb_customer_cd')->on('tb0.tb_customer_sub_inner_group', '=', 'tb2.category_inner_group');
            })
            ->where('tb2.tb_search_form_cd', $form->cd)
            ->where('tb0.tb_search_cate_cd', $form->tb_search_cate_cd);
    }

    /**
     * model1.php: por bloque (valor del cruce o uno solo), cuántas veces se eligió cada opción (las de `titles`
     * primero, en su orden, aunque valgan 0) y el 述べ人数.
     *
     * @return array{meta: array, blocks: list<array{key: string, value: string, total: int, counts: array<string, int>}>}
     */
    public function model1(CustomField $form, array $in, Staff $staff, Collection $shops): array
    {
        $query = $this->customFieldQuery($form)->where('tb2.text_data', '<>', '');
        $meta = $this->applyVisitFilters($query, $in, $staff, $shops);
        $query->selectRaw('COUNT(*) AS count, tb2.text_data')->groupBy('tb2.text_data')->orderBy('tb2.text_data');

        $titles = array_fill_keys(explode('<>', (string) $form->titles), 0);
        $blocks = [];
        foreach ($query->get() as $row) {
            $key = self::groupKey($meta, self::groupValue($row, $meta));
            $blocks[$key] ??= ['key' => $key, 'value' => (string) self::groupValue($row, $meta), 'total' => 0, 'counts' => null];
            $blocks[$key]['total'] += (int) $row->count;
            foreach (explode('<>', (string) $row->text_data) as $option) {
                if ($option === '') {
                    continue;
                }
                $blocks[$key]['counts'] ??= $titles;
                $blocks[$key]['counts'][$option] = ($blocks[$key]['counts'][$option] ?? 0) + (int) $row->count;
            }
        }

        // Como el legacy: un bloque solo sale si tuvo alguna opción
        $blocks = array_values(array_filter($blocks, fn ($b) => $b['counts'] !== null));

        return ['meta' => $meta, 'blocks' => $blocks];
    }

    /**
     * model2.php: 顧客数, 顧客延べ数, 合計, 平均, 最大, 最小 del campo numérico (con su `unit`).
     *
     * @return array{meta: array, blocks: list<array{key: string, value: string, stats: array<string, string>}>}
     */
    public function model2(CustomField $form, array $in, Staff $staff, Collection $shops): array
    {
        $query = $this->customFieldQuery($form)->whereRaw('COALESCE(tb2.float_data, 0) <> 0');
        $meta = $this->applyVisitFilters($query, $in, $staff, $shops);
        $query->selectRaw('COUNT(*) AS count, COUNT(DISTINCT(tb1.cd)) AS member_count, AVG(tb2.float_data) AS avg, SUM(tb2.float_data) AS sum, MIN(tb2.float_data) AS min, MAX(tb2.float_data) AS max');

        $unit = (string) $form->unit;
        $blocks = [];
        foreach ($query->get() as $row) {
            $key = self::groupKey($meta, self::groupValue($row, $meta));
            $blocks[$key] = ['key' => $key, 'value' => (string) self::groupValue($row, $meta), 'stats' => [
                '顧客数' => number_format((int) $row->member_count),
                '顧客延べ数' => number_format((int) $row->count),
                '合計' => self::amount($row->sum).$unit,
                '平均' => self::amount($row->avg).$unit,
                '最大' => self::amount($row->max).$unit,
                '最小' => self::amount($row->min).$unit,
            ]];
        }

        return ['meta' => $meta, 'blocks' => array_values($blocks)];
    }

    /**
     * point1.php / stamp1.php: 会計 (合計/平均/最大/最小 en 円) de las visitas con movimiento de puntos o sellos.
     * El legacy calculaba también 差引会計 y 消費ポイント/スタンプ pero no los mostraba (comentados); en stamp1
     * pedía `sashihiki_kaikei`, que `tb_stamp_log` no tiene, y la consulta fallaba: aquí solo se calcula 会計.
     *
     * @param  'point'|'stamp'  $kind
     * @return array{meta: array, blocks: list<array{key: string, value: string, members: string, visits: string, kaikei: array<string, string>}>}
     */
    public function kaikei(string $kind, array $in, Staff $staff, Collection $shops): array
    {
        $table = $kind === 'stamp' ? 'tb_stamp_log' : 'tb_point_log';
        $query = DB::table('tb_raiten_rireki as tb0')
            ->join('tb_customer as tb1', 'tb0.user_cd', '=', 'tb1.cd')
            ->join("{$table} as tb2", "tb0.tb_{$kind}_log_cd", '=', 'tb2.cd')
            ->whereRaw('COALESCE(tb2.kaikei, 0) <> 0')
            ->whereRaw("COALESCE(tb0.disp_flg, '') <> '9'")
            ->whereRaw("COALESCE(tb2.disp_flg, '') <> '9'");
        $meta = $this->applyVisitFilters($query, $in, $staff, $shops);
        $query->selectRaw('COUNT(*) AS count, COUNT(DISTINCT(tb1.cd)) AS member_count, AVG(tb2.kaikei) AS kaikei_avg, SUM(tb2.kaikei) AS kaikei_sum, MIN(tb2.kaikei) AS kaikei_min, MAX(tb2.kaikei) AS kaikei_max');

        $blocks = [];
        foreach ($query->get() as $row) {
            $key = self::groupKey($meta, self::groupValue($row, $meta));
            $blocks[$key] = [
                'key' => $key,
                'value' => (string) self::groupValue($row, $meta),
                'members' => number_format((int) $row->member_count).'人',
                'visits' => number_format((int) $row->count).'人',
                'kaikei' => [
                    '合計' => self::amount($row->kaikei_sum).'円',
                    '平均' => self::amount($row->kaikei_avg).'円',
                    '最大' => self::amount($row->kaikei_max).'円',
                    '最小' => self::amount($row->kaikei_min).'円',
                ],
            ];
        }

        return ['meta' => $meta, 'blocks' => array_values($blocks)];
    }

    /** number_format con 2 decimales solo si el valor tiene parte decimal (como el legacy) */
    private static function amount(mixed $value): string
    {
        $value = (float) ($value ?? 0);

        return $value - (int) $value > 0 ? number_format($value, 2) : number_format($value);
    }

    // ------------------------------------------------------------------ model1_select.php

    /**
     * WHERE de 振分配信 para la メール del bloque o de la opción elegida en model1 (el legacy recibía el SQL del
     * navegador y lo validaba contra la sesión; aquí llega la condición como `field=…&value=…[&option=…]` y el
     * SQL se arma en el servidor). Mismo resultado: los socios activos con alguna visita que cumpla el filtro.
     */
    public function mailWhere(CustomField $form, array $in, string $condition, Staff $staff): string
    {
        parse_str($condition, $target);

        $query = $this->customFieldQuery($form)->where('tb2.text_data', '<>', '');
        // Sin cond: el cruce no agrupa ni selecciona nada
        $this->applyVisitFilters($query, array_diff_key($in, ['cond' => true]), $staff, collect());

        $field = (string) ($target['field'] ?? '');
        if (in_array($field, ['sex', 'job'], true)) {
            $query->whereRaw(self::conditionExpr($field).' = ?', [(string) ($target['value'] ?? '')]);
        } elseif ($field === 'age') {
            $query->whereRaw(self::conditionExpr('age').' = ?', [(int) ($target['value'] ?? 0)]);
        }
        if (array_key_exists('option', $target)) {
            $query->where('tb2.text_data', 'like', '%'.$target['option'].'%');
        }

        $sub = $query->selectRaw('distinct(tb1.cd)')->toRawSql();

        return "WHERE disp_flg = '1' AND touroku_kubun = '2' AND cd IN ( {$sub} )";
    }

    // ------------------------------------------------------------------ point2

    /**
     * point2.php: por tienda (tb_point_log.tb_shop_cd) y día del mes / mes del año / año, 顧客数, 延べ数 y
     * puntos dados (jidou + sounyu) y usados (shouhi): 合計/最大/最小, con la fila de totales.
     * kikan_type 1 (por defecto el mes actual), 2 (el año actual), 3 (desde el primer año de tb_point_log).
     *
     * @return array{rirekiTitle: string, rirekiType: string, th: string, unit: string, min: int, max: int, shops: array}
     */
    public function point2(array $in, Staff $staff, Collection $shops, int $minYear): array
    {
        $kikan = (string) ($in['kikan_type'] ?? '');
        $result = ['rirekiTitle' => '', 'rirekiType' => '', 'th' => '', 'unit' => '', 'min' => 0, 'max' => -1, 'shops' => []];
        $now = now('Asia/Tokyo');

        $query = DB::table('tb_point_log as tb0')
            ->join('tb_customer as tb1', 'tb0.tb_customer_cd', '=', 'tb1.cd')
            ->leftJoin('tb_shop as tb2', 'tb0.tb_shop_cd', '=', 'tb2.cd')
            ->whereRaw("COALESCE(tb0.disp_flg, '') <> '9'");

        // 集計店舗
        if ($staff->isMaster()) {
            if ((string) ($in['touroku_shop_cd'] ?? '') !== '') {
                $query->where('tb0.tb_shop_cd', (int) $in['touroku_shop_cd']);
                $result['rirekiTitle'] .= ($shops[(int) $in['touroku_shop_cd']] ?? '').'の ';
            }
        } else {
            $query->where('tb0.tb_shop_cd', (int) $staff->shop_cd);
        }

        switch ($kikan) {
            case '1':
                $start = Carbon::create((int) (($in['type_m_year'] ?? '') ?: $now->year), (int) (($in['type_m_month'] ?? '') ?: $now->month), 1, 0, 0, 0, 'Asia/Tokyo');
                $end = $start->copy()->addMonth();
                $result = array_merge($result, ['rirekiType' => '日別表示', 'unit' => '日', 'min' => 1, 'max' => $start->daysInMonth, 'format' => 'YYYY/MM/DD']);
                $result['rirekiTitle'] .= $start->format('Y年m月').' ';
                break;
            case '2':
                $start = Carbon::create((int) (($in['type_y_year'] ?? '') ?: $now->year), 1, 1, 0, 0, 0, 'Asia/Tokyo');
                $end = $start->copy()->addYear();
                $result = array_merge($result, ['rirekiType' => '月別表示', 'unit' => '月', 'min' => 1, 'max' => 12, 'format' => 'YYYY/MM']);
                $result['rirekiTitle'] .= $start->format('Y年').' ';
                break;
            case '3':
                $result = array_merge($result, ['rirekiType' => '年度別表示', 'unit' => '年', 'min' => $minYear, 'max' => (int) $now->format('Y'), 'format' => 'YYYY']);
                $result['rirekiTitle'] .= '年度別 ';
                break;
            default:
                // Sin kikan_type no se calcula nada (「集計の方法と期間を指定してください。」)
                return $result;
        }

        if (isset($start)) {
            // El legacy restaba 1 segundo al final y además comparaba con «<» (perdía el último segundo): aquí «< fin»
            $query->where('tb0.touroku_date', '>=', $start->getTimestamp())->where('tb0.touroku_date', '<', $end->getTimestamp());
        }

        $hiduke = "TO_CHAR(TO_TIMESTAMP(tb0.touroku_date) AT TIME ZONE 'Asia/Tokyo', '{$result['format']}')";
        $rows = $query->selectRaw("tb2.kaishamei, {$hiduke} AS hiduke, COUNT(*) AS count, COUNT(DISTINCT(tb1.cd)) AS member_count,
                SUM(COALESCE(tb0.shouhi_point,0)) AS shouhi_point_sum, MIN(COALESCE(tb0.shouhi_point,0)) AS shouhi_point_min, MAX(COALESCE(tb0.shouhi_point,0)) AS shouhi_point_max,
                SUM(COALESCE(tb0.jidou_point,0) + COALESCE(tb0.sounyu_point,0)) AS total_point_sum,
                MIN(COALESCE(tb0.jidou_point,0) + COALESCE(tb0.sounyu_point,0)) AS total_point_min,
                MAX(COALESCE(tb0.jidou_point,0) + COALESCE(tb0.sounyu_point,0)) AS total_point_max")
            ->groupBy('tb2.kaishamei')->groupByRaw('hiduke')
            ->get();

        foreach ($rows as $row) {
            $name = (string) $row->kaishamei !== '' ? (string) $row->kaishamei : '不明';
            $parts = explode('/', (string) $row->hiduke);
            [$result['th'], $period] = match ($kikan) {
                '1' => [$parts[0].'年'.($parts[1] ?? '').'月', $parts[2] ?? ''],
                '2' => [$parts[0].'年', $parts[1] ?? ''],
                default => ['', (string) $row->hiduke],
            };

            $shop = $result['shops'][$name] ?? ['periods' => [], 'total' => [
                '顧客数' => 0, '顧客延べ数' => 0,
                '付与合計' => 0, '付与最大' => (int) $row->total_point_max, '付与最小' => (int) $row->total_point_min,
                '還元合計' => 0, '還元最大' => (int) $row->shouhi_point_max, '還元最小' => (int) $row->shouhi_point_min,
            ]];

            $shop['periods'][$period] = [
                'members' => (int) $row->member_count, 'visits' => (int) $row->count,
                'given' => [(int) $row->total_point_sum, (int) $row->total_point_max, (int) $row->total_point_min],
                'used' => [(int) $row->shouhi_point_sum, (int) $row->shouhi_point_max, (int) $row->shouhi_point_min],
            ];

            $t = &$shop['total'];
            $t['顧客数'] += (int) $row->member_count;
            $t['顧客延べ数'] += (int) $row->count;
            $t['付与合計'] += (int) $row->total_point_sum;
            $t['付与最大'] = max($t['付与最大'], (int) $row->total_point_max);
            $t['付与最小'] = min($t['付与最小'], (int) $row->total_point_min);
            $t['還元合計'] += (int) $row->shouhi_point_sum;
            $t['還元最大'] = max($t['還元最大'], (int) $row->shouhi_point_max);
            $t['還元最小'] = min($t['還元最小'], (int) $row->shouhi_point_min);
            unset($t);

            $result['shops'][$name] = $shop;
        }

        return $result;
    }

    // ------------------------------------------------------------------ fechas

    /**
     * strtotime('Y/m/d H:00') del legacy: sin año, mes o día no hay fecha; la hora es opcional.
     * Carbon::create desborda como strtotime (31 de febrero → marzo).
     */
    private static function datetime(array $in, string $y, string $m, string $d, string $h): ?Carbon
    {
        foreach ([$y, $m, $d] as $key) {
            if (! ctype_digit((string) ($in[$key] ?? ''))) {
                return null;
            }
        }
        $hour = ctype_digit((string) ($in[$h] ?? '')) ? (int) $in[$h] : 0;

        return Carbon::create((int) $in[$y], (int) $in[$m], (int) $in[$d], $hour, 0, 0, 'Asia/Tokyo');
    }
}
