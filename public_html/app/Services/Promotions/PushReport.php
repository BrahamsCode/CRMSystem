<?php

namespace App\Services\Promotions;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de los informes de push (legacy category3/inc/push_list.php, push_shousai.php,
 * push/push_history.php y push_history_detail.php) sobre push_master y push_notifications.
 *
 * Todas cuentan sobre las entregas sin duplicados del legacy:
 *   SELECT MAX(type), MAX(type_master_cd), MAX(tb_customer_cd), MAX(open_cnt) FROM push_notifications
 *   GROUP BY type, type_master_cd, open_cnt, tb_customer_cd
 * 配信数 = filas, 閲覧のべ人数 = sum(open_cnt), 閲覧実人数 = count(open_cnt).
 */
class PushReport
{
    /** Orden del 過去配信内容一覧 (push_list.php $sortAry; `opne_rate` tal cual el legacy) */
    public const SORTS = [
        'send_date' => '配信日時',
        'opne_rate' => 'クリックカウント率',
        'total_send' => '総配信数',
        'total_count' => '閲覧のべ人数',
        'total_open' => '閲覧実人数',
    ];

    /** Franjas del 時間別集計 (push_shousai.php $calcBaseAry): clave => [etiqueta, desde (s), hasta (s) o null] */
    public const TIME_BUCKETS = [
        '+1 hour' => ['1時間以内', 0, 3600],
        '+3 hour' => ['3時間以内', 3601, 10800],
        '+6 hour' => ['6時間以内', 10801, 21600],
        '+12 hour' => ['12時間以内', 21601, 43200],
        '+24 hour' => ['24時間以内', 43201, 86400],
        '+1 week' => ['1週間以内', 86401, 604800],
        '+99 year' => ['その他', 604801, null],
    ];

    public const TIME_COLORS = [
        '+1 hour' => ['#46BFBD', '#5AD3D1'], '+3 hour' => ['palevioletred', 'pink'], '+6 hour' => ['#FDB45C', '#FFC870'],
        '+12 hour' => ['#949FB1', '#A8B3C5'], '+24 hour' => ['#4D5360', '#616774'], '+1 week' => ['#B48EAD', '#C69CBE'],
        '+99 year' => ['green', 'seagreen'],
    ];

    /** CSV de push_history_detail.php */
    public const CSV_NAME = 'プッシュ集計 - 詳細_';

    public const CSV_HEADER = ['顧客名', '電話番号', '登録店舗', '年齢', '性別', '職業', '会員登録日'];

    public const SEXES = ['man' => '男性', 'female' => '女性', 'etc' => 'その他'];

    public const SEX_COLORS = ['man' => ['#46BFBD', '#5AD3D1'], 'female' => ['#F7464A', '#FF5A5E'], 'etc' => ['#FDB45C', '#FFC870']];

    public const AGES = ['19' => '19歳以下', '20' => '20～29歳', '30' => '30～39歳', '40' => '40～49歳', '50' => '50～59歳', '60' => '60歳以上', '999' => '未設定'];

    public const AGE_COLORS = [
        '19' => ['#46BFBD', '#5AD3D1'], '20' => ['palevioletred', 'pink'], '30' => ['#FDB45C', '#FFC870'], '40' => ['#949FB1', '#A8B3C5'],
        '50' => ['#4D5360', '#616774'], '60' => ['#B48EAD', '#C69CBE'], '999' => ['green', 'green'],
    ];

    public const JOB_COLORS = [
        ['#FF0000', '#FF2222'], ['#00FF00', '#22FF22'], ['#0000FF', '#2222FF'], ['#FF00FF', '#FF22FF'], ['#FFFF00', '#FFFF22'], ['#00FFFF', '#22FFFF'],
        ['#FF6600', '#FF8822'], ['#FF0066', '#FF2288'], ['#6F6F60', '#8F8F82'], ['#06AFA6', '#28CFC8'], ['#6600FF', '#8822FF'], ['#0066FF', '#0066FF'],
        ['#F7464A', '#F7464A'], ['#FDB45C', '#FDB45C'], ['#949FB1', '#949FB1'], ['#4D5360', '#4D5360'], ['#B48EAD', '#B48EAD'],
        ['green', 'green'], ['palevioletred', 'palevioletred'], ['skyblue', 'skyblue'], ['dodgerblue', 'dodgerblue'], ['mediumseagreen', 'mediumseagreen'],
    ];

    /** Entregas sin duplicados (opcionalmente con touroku_date/open_date en el GROUP BY, como el 時間別集計) */
    public function deliveries(array $extra = []): Builder
    {
        $select = ['MAX(type) AS type', 'MAX(type_master_cd) AS type_master_cd', 'MAX(tb_customer_cd) AS tb_customer_cd', 'MAX(open_cnt) AS open_cnt'];
        foreach ($extra as $column) {
            $select[] = "MAX($column) AS $column";
        }

        return DB::table('push_notifications')->selectRaw(implode(', ', $select))
            ->groupBy(array_merge(['type', 'type_master_cd', 'open_cnt', 'tb_customer_cd'], $extra));
    }

    /** Contadores por push: [cd => {total, nobe, opened}] */
    public function counts(array $cds, string $type = 'push'): array
    {
        if ($cds === []) {
            return [];
        }

        return DB::query()->fromSub($this->deliveries(), 'res')
            ->where('type', $type)->whereIn('type_master_cd', $cds)
            ->groupBy('type_master_cd')
            ->selectRaw('type_master_cd, COALESCE(sum(open_cnt), 0) AS nobe, count(open_cnt) AS opened, count(*) AS total')
            ->get()->keyBy('type_master_cd')->all();
    }

    /**
     * 過去配信内容一覧 (push_list.php): push_master no borrados, filtros de fecha de registro, título y tipo.
     *
     * @param  array{from: ?int, to: ?int, texto: string, point_add_flg: bool, etc_flg: bool}  $filters
     */
    public function past(array $filters, string $sort, string $direction = 'DESC'): Builder
    {
        $query = DB::table('push_master')->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->when($filters['from'] !== null, fn ($q) => $q->where('touroku_date', '>=', $filters['from']))
            ->when($filters['to'] !== null, fn ($q) => $q->where('touroku_date', '<=', $filters['to']))
            ->when($filters['texto'] !== '', fn ($q) => $q->where('title', 'like', '%'.$filters['texto'].'%'))
            ->when($filters['point_add_flg'] && ! $filters['etc_flg'], fn ($q) => $q->where('post_type', '2'))
            ->when(! $filters['point_add_flg'] && $filters['etc_flg'], fn ($q) => $q->where('post_type', '!=', '2'));

        $direction = $direction === 'ASC' ? 'ASC' : 'DESC';
        $metric = match ($sort) {
            'opne_rate' => 'CASE WHEN count(*) = 0 THEN 0 ELSE 1.0 * count(open_cnt) / count(*) * 100 END',
            'total_send' => 'count(*)',
            'total_count' => 'COALESCE(sum(open_cnt), 0)',
            'total_open' => 'count(open_cnt)',
            default => null,
        };
        if ($metric === null) {
            return $query->orderBy('touroku_date', $direction);
        }
        $sub = DB::query()->fromSub($this->deliveries(), 'res')->selectRaw($metric)
            ->where('type', 'push')->whereColumn('type_master_cd', 'push_master.cd');

        return $query->orderByRaw('COALESCE(('.$sub->toRawSql().'), 0) '.$direction);
    }

    /** Edad en años a partir de `kojin_birth` (YYYY/MM/DD), como el CASE del legacy; null si no es una fecha */
    public static function ageSql(string $table = 'tb_customer'): string
    {
        $now = Carbon::now(LegacyTime::ZONE)->format('Ymd');

        return "CASE WHEN {$table}.kojin_birth !~ '^[0-9]{4}/[0-9]{2}/[0-9]{2}' THEN NULL"
            ." WHEN TO_TIMESTAMP({$table}.kojin_birth, 'YYYY/MM/DD') > '1900-01-01'"
            ." THEN floor(({$now} - replace(substr({$table}.kojin_birth, 1, 10), '/', '')::int) / 10000) ELSE NULL END";
    }

    /** Fecha de alta del cliente (texto epoch) como Ymd; vacío si no es un número */
    private static function customerDateSql(string $format): string
    {
        return "CASE WHEN tb_customer.touroku_date ~ '^[0-9]+$' THEN to_char(to_timestamp(tb_customer.touroku_date::bigint) AT TIME ZONE '".LegacyTime::ZONE."', '{$format}') ELSE '' END";
    }

    /** Columnas de cliente de las tablas de detalle (push_shousai.php) */
    private function customerColumns(): array
    {
        return [
            'tb_customer.login_id', 'tb_customer.name', 'tb_customer.tel', 'tb_customer.kojin_sex', 'tb_customer.job', 'tb_shop.kaishamei',
            DB::raw(self::customerDateSql('YYYYMMDD').' AS touroku_ymd'), DB::raw(self::ageSql().' AS age'),
        ];
    }

    /**
     * 過去プッシュ配信詳細 (push_shousai.php): gráficos por tiempo hasta la apertura, sexo, ocupación y edad,
     * y la lista de clientes de la porción elegida. $type: push o mailmaga (el push espejo de un correo).
     */
    public function detail(int $cd, string $type): array
    {
        $base = fn (array $extra = []) => DB::query()->fromSub($this->deliveries($extra)->where('type', $type)->where('type_master_cd', $cd), 'res')
            ->join('tb_customer', 'res.tb_customer_cd', '=', 'tb_customer.cd')
            ->join('tb_shop', 'tb_customer.tb_shop_cd', '=', 'tb_shop.cd')
            ->select(array_merge(['res.*'], $this->customerColumns()))
            ->orderBy('res.type_master_cd');

        $unread = $base()->whereNull('res.open_cnt')->get()->all();
        $time = [];
        foreach (self::TIME_BUCKETS as $key => [, $min, $max]) {
            $q = $base(['touroku_date', 'open_date'])->whereRaw('(res.open_date - res.touroku_date) >= ?', [$min]);
            if ($max !== null) {
                $q->whereRaw('(res.open_date - res.touroku_date) <= ?', [$max]);
            }
            $time[$key] = $q->get()->all();
        }

        $opened = DB::table('push_notifications')->select('tb_customer_cd')->where('type', $type)->where('type_master_cd', $cd)->whereNotNull('open_cnt');
        $sexCase = "CASE WHEN tb_customer.kojin_sex = '男性' THEN 'man' WHEN tb_customer.kojin_sex = '女性' THEN 'female' ELSE 'etc' END";
        $openedCustomers = DB::table('tb_customer')->join('tb_shop', 'tb_customer.tb_shop_cd', '=', 'tb_shop.cd')
            ->whereIn('tb_customer.cd', $opened)
            ->select(array_merge($this->customerColumns(), [DB::raw("$sexCase AS sex_index"),
                DB::raw("CASE WHEN COALESCE(tb_customer.job, '') = '' THEN 'その他' ELSE tb_customer.job END AS set_job")]))
            ->orderByDesc('tb_customer.cd')->get();

        $sexCounts = DB::table('tb_customer')->whereIn('cd', $opened)->selectRaw(str_replace('tb_customer.', '', $sexCase).' AS sex, COUNT(cd) AS cnt')
            ->groupBy('sex')->pluck('cnt', 'sex')->map(fn ($n) => (int) $n)->all();
        $jobCounts = DB::table('tb_customer')->whereIn('cd', $opened)->selectRaw("CASE WHEN COALESCE(job, '') = '' THEN 'その他' ELSE job END AS set_job, COUNT(cd) AS cnt")
            ->groupBy('set_job')->pluck('cnt', 'set_job')->map(fn ($n) => (int) $n)->all();

        $ages = [];
        $openedRows = $base(['open_date'])->whereNotNull('res.open_cnt')->get();
        foreach (array_keys(self::AGES) as $key) {
            $ages[$key] = $openedRows->filter(fn ($r) => match ((string) $key) {
                '19' => $r->age !== null && $r->age <= 19,
                '60' => $r->age !== null && $r->age >= 60,
                '999' => $r->age === null,
                default => $r->age !== null && $r->age >= (int) $key && $r->age < (int) $key + 10,
            })->values()->all();
        }

        return [
            'unread' => $unread,
            'time' => $time,
            'sexCounts' => $sexCounts,
            'sexRows' => $openedCustomers->groupBy('sex_index')->map->all()->all(),
            'jobs' => DB::table('tb_job')->where('disp_flg', '1')->orderBy('rank')->pluck('name')->all(),
            'jobCounts' => $jobCounts,
            'jobRows' => $openedCustomers->groupBy('set_job')->map->all()->all(),
            'ages' => $ages,
        ];
    }

    /**
     * プッシュ集計 (push_history.php): todos los push con fecha, día de la semana, hora y contadores
     * (配信数, 閲覧のべ人数, 閲覧実人数, 閲覧率 con un decimal), del más reciente al más antiguo.
     */
    public function history(): Builder
    {
        $zone = LegacyTime::ZONE;
        $stats = DB::query()->fromSub($this->deliveries(), 'res')->where('type', 'push')->groupBy('type_master_cd')
            ->selectRaw('type_master_cd, COALESCE(sum(open_cnt), 0) AS nobe, COALESCE(count(open_cnt), 0) AS opened, count(*) AS total');
        $at = "(to_timestamp(push_master.touroku_date) AT TIME ZONE '{$zone}')";

        return DB::table('push_master')->leftJoinSub($stats, 'group_data', 'group_data.type_master_cd', '=', 'push_master.cd')
            ->select('push_master.*')
            ->selectRaw("TRIM(LEADING '0' FROM to_char($at, 'MM')) || '月' || TRIM(LEADING '0' FROM to_char($at, 'DD')) || '日' AS push_date")
            ->selectRaw("(ARRAY['日','月','火','水','木','金','土'])[EXTRACT(DOW FROM $at)::int + 1] AS push_date_week")
            ->selectRaw("to_char($at, 'HH24:MI') AS push_date_time")
            ->selectRaw('COALESCE(group_data.total, 0) AS haishin, COALESCE(group_data.nobe, 0) AS nobe, COALESCE(group_data.opened, 0) AS opened')
            ->selectRaw('CASE WHEN COALESCE(group_data.total, 0) > 0 AND COALESCE(group_data.opened, 0) > 0'
                .' THEN ROUND((group_data.opened::float / group_data.total::float * 100.0)::numeric, 1) ELSE 0.0 END AS rate')
            ->orderByDesc('push_master.touroku_date');
    }

    /** 会員件数: clientes que hoy cumplen el `wherestr` guardado (SQL generado por el sistema); 0 si falla */
    public function audienceCount(?string $wherestr): int
    {
        $where = trim((string) preg_replace('/^\s*WHERE\s+/i', '', (string) $wherestr));
        if ($where === '') {
            return (int) DB::table('tb_customer')->count();
        }
        try {
            return (int) DB::transaction(fn () => DB::table('tb_customer')->whereRaw(str_replace('{POS_RESULT}', '-1', $where))->count());
        } catch (\Throwable) {
            return 0;
        }
    }

    /** プッシュ集計 - 詳細 (push_history_detail.php): destinatarios del push (o solo los que lo abrieron) */
    public function historyRecipients(int $cd, bool $openedOnly): Builder
    {
        return DB::table('tb_customer')
            ->joinSub($this->deliveries(), 'res', 'res.tb_customer_cd', '=', 'tb_customer.cd')
            ->join('tb_shop', 'tb_customer.tb_shop_cd', '=', 'tb_shop.cd')
            ->where('res.type', 'push')->where('res.type_master_cd', $cd)
            ->when($openedOnly, fn ($q) => $q->where('res.open_cnt', '>', 0))
            ->select('tb_customer.name', 'tb_customer.tel', 'tb_customer.kojin_sex', 'tb_customer.job', 'tb_shop.kaishamei AS shop_name')
            ->selectRaw(self::ageSql().' AS age')
            ->selectRaw(self::customerDateSql('YYYY"年"MM"月"DD"日"').' AS touroku_ymd')
            ->orderByRaw("CASE WHEN tb_customer.touroku_date ~ '^[0-9]+$' THEN tb_customer.touroku_date::bigint END DESC NULLS LAST");
    }
}
