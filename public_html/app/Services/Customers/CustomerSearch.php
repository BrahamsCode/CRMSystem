<?php

namespace App\Services\Customers;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Buscador de clientes: port de `system/inc/search_set2.php` del legacy.
 *
 * Convierte los parámetros del formulario de búsqueda (los mismos `name` que el legacy:
 * `name`, `name_kana`, `tel`, `age_1`, `touroku_date_1`, `form<cd>`, `search_rireki_*`,
 * `search_kaikei_*`, `search_method1_type`…) en condiciones sobre `tb_customer`, con
 * query builder y bindings (el legacy concatenaba SQL).
 *
 * Uso típico:
 *   $search = app(CustomerSearch::class)->forStaff($staff->isMaster(), $staff->tb_shop_cd);
 *   $rows   = $search->query($request->all())->orderByDesc('cd')->paginate(10);
 *   // Para CSV / 配信 (el legacy guardaba `$_SESSION['wherestr_<uniqid>']`):
 *   session(["customer_search.$uniqid" => $search->describe($request->all())]);
 *   $query = $search->queryFromDescription(session("customer_search.$uniqid"));
 *
 * Contexto (inmutable, cada método devuelve una copia):
 *   - forStaff(): el legacy solo deja filtrar por varias tiendas (`tb_shop_cd[]`) a `rank = master`;
 *     al resto le añade siempre `tb_shop_cd = <su tienda>` fuera del grupo AND/OR.
 *     Por defecto el servicio actúa como master: el controlador DEBE llamar a forStaff().
 *   - forCategory(): las カスタマイズ検索 salen de `tb_search_cate.category` = carpeta de la
 *     pantalla (`category1` para 顧客管理, `category3` para las pantallas de 販売促進).
 *
 * Decisiones frente al legacy (anotadas también en el informe de S2):
 *   - Fechas `Y/m/d` inválidas se ignoran (el legacy hacía `mktime()` con basura).
 *   - Las epoch se calculan en Asia/Tokyo (la app corre en UTC; el legacy en Asia/Tokyo).
 *   - 「検索条件に一致しないもの」 (`search_method2_type = 2`): el legacy generaba
 *     `main AND conds AND cd NOT IN (SELECT cd … main AND conds)`, que siempre da vacío;
 *     aquí es `main AND cd NOT IN (SELECT cd … main AND conds)`, que es lo que pretendía.
 *   - `search_rireki_daysFromLastVisit`: una sola condición (el legacy añadía dos al grupo, y en
 *     OR la segunda, `last_raiten_date <> ''`, traía a casi todos).
 *   - Valores: se recortan espacios (半角/全角) como `get_form2()`, sin su `htmlspecialchars()`.
 *   - 年 (form_type) solo «desde»: comparación de texto (el legacy comparaba text con integer y fallaba).
 *   - `belongpost`: la columna no existe en `tb_customer`; se ignora (en el legacy rompía la consulta).
 *   - POS (`search_POS_kaikei_*`): fuera de alcance, ver TODO(S2) en compile().
 */
class CustomerSearch
{
    /** Zona horaria del legacy para las fechas epoch (touroku_date, raiten_time…) */
    public const TIMEZONE = 'Asia/Tokyo';

    /** Versión del formato de describe() */
    public const DESCRIPTION_VERSION = 1;

    /** Columnas con `LIKE '%valor%'` => etiqueta del resumen (search_display) */
    private const LIKE_FIELDS = [
        'name_kana' => 'カナ',
        'name' => '名前',
        'zip' => '郵便番号',
        'add' => '市区町村',
        'add_kana' => '市区町村（カナ）',
        'add_number' => '丁目・番地',
        'add_build' => 'マンション・ビル名',
        'add_build_kana' => 'マンション・ビル名（カナ）',
        'tel' => '電話番号',
        'fax' => 'ファックス番号',
        'kojin_sex' => '性別',
        'kojin_mobile' => '携帯電話番号',
        'kojin_mail' => '個人メールアドレス',
        'job' => '職業',
        'kinmusaki_name_kana' => '勤務先（カナ）',
        'kinmusaki_name' => '勤務先',
        'kinmusaki_gyoushu' => '勤務先業種',
        'kinmusaki_tel' => '勤務先電話番号',
        'kinmusaki_fax' => '勤務先ファックス番号',
        'houjin_setsuritsu' => '設立年月日',
        'houjin_shihon' => '資本金',
        'daihyousha_name_kana' => '代表者名前（カナ）',
        'daihyousha_name' => '代表者名前',
        'daihyousha_birth' => '代表者誕生日',
        'houjin_gyoushu' => '業種',
        'houjin_busho' => '部署',
        'tantou_name_kana' => '担当者名前（カナ）',
        'tantou_name' => '担当者名前',
        'tantou_tel' => '担当者電話番号',
        'tantou_mail' => '担当者メールアドレス',
        'kanri_id' => '管理番号',
    ];

    /** `LIKE` donde `_` se escapa con `#` (email1/email2 en el legacy) */
    private const LIKE_ESCAPED_FIELDS = [
        'email1' => 'メールアドレス1',
        'email2' => 'メールアドレス2',
    ];

    /** Igualdad de texto */
    private const EQUAL_FIELDS = [
        'type' => '個・法：',
        'pref' => '都道府県：',
        'kojin_bloodtype' => '血液型：',
        'touroku_kubun' => 'touroku',
        'terminal_no' => '端末機ID：',
    ];

    /** Igualdad sobre columnas bigint que el legacy comparaba con un literal de texto */
    private const EQUAL_CD_FIELDS = [
        'tb_customer_group_cd' => '顧客グループ',
        'tb_raiten_douki_master_cd' => '初回来店動機',
    ];

    /** Igualdad con `(int)` */
    private const INT_FIELDS = [
        'tb_staff_cd' => '指名担当者',
        'kingaku_rank_now' => '金額ランク（今回）',
        'kingaku_rank_pre' => '金額ランク（前回）',
        'raiten_rank_now' => '来店ランク（今回）',
        'raiten_rank_pre' => '来店ランク（前回）',
    ];

    /** Rangos `>= desde` / `coalesce(col,0) <= hasta` solo con dígitos */
    private const COUNT_RANGES = [
        'stamp_count' => ['スタンプ数 始', 'スタンプ 終'],
        'error_send_cnt' => ['不達配信数 始', '不達配信数 終'],
    ];

    /** Tipos de campo personalizado que buscan `text_data LIKE '%valor%'` */
    private const CUSTOM_LIKE_TYPES = ['テキスト', '半角英数テキスト', 'メールアドレス', 'テキストエリア', 'ラジオボタン', 'セレクト', 'テーブル'];

    /** Parámetros fijos que lee el buscador (para describe()) */
    private const KNOWN_KEYS = [
        'id', 'tb_shop_cd', 'kojin_birth', 'kojin_birth2', 'mailmaga_flg', 'age_1', 'age_2',
        'touroku_date_1', 'touroku_date_2', 'update_date_1', 'update_date_2',
        'revocation_point_date_1', 'revocation_point_date_2', 'revocation_point_day_1', 'revocation_point_day_2',
        'stamp_count_1', 'stamp_count_2', 'error_send_cnt_1', 'error_send_cnt_2',
        'service_point_1', 'service_point_2', 'my_colle_use', 'felica_no',
        'distance_shop_cd', 'distance', 'interval_gps',
        'yoyaku_menu_cd', 'yoyaku_andor_flg', 'yoyaku_raiten_flg',
        'search_rireki_type_flg', 'search_rireki_start_year', 'search_rireki_start_month', 'search_rireki_start_day',
        'search_rireki_start_time', 'search_rireki_end_year', 'search_rireki_end_month', 'search_rireki_end_day',
        'search_rireki_end_time', 'search_rireki_day', 'search_rireki_dow', 'search_rireki_shop_cd',
        'search_rireki_times', 'search_rireki_times2', 'search_rireki_daysFromLastVisit',
        'search_rireki_averagePeriodTime1', 'search_rireki_averagePeriodTime2',
        'search_raiten_yosoku_1', 'search_raiten_yosoku_2',
        'search_kaikei_start_year', 'search_kaikei_start_month', 'search_kaikei_start_day', 'search_kaikei_start_time',
        'search_kaikei_end_year', 'search_kaikei_end_month', 'search_kaikei_end_day', 'search_kaikei_end_time',
        'search_kaikei_shop_cd', 'search_kaikei_resource', 'search_kaikei_kingaku1', 'search_kaikei_kingaku2',
        'search_method1_type', 'search_method2_type', 'disp_flg',
    ];

    /** Columnas de teléfono que mira byPhone() */
    private const PHONE_COLUMNS = ['tel', 'tel2', 'kojin_mobile', 'kinmusaki_tel', 'tantou_tel', 'fax'];

    private string $category = 'category1';

    private bool $isMaster = true;

    private ?int $staffShopCd = null;

    /** @var array<int, array<string, mixed>>|null caché de tb_search_form buscables */
    private ?array $customForms = null;

    /**
     * Rango de quien busca: master puede filtrar por `tb_shop_cd[]`; el resto queda siempre
     * restringido a su tienda (`$shopCd`; null => 0, sin resultados, como el legacy).
     */
    public function forStaff(bool $isMaster, ?int $shopCd = null): static
    {
        $copy = clone $this;
        $copy->isMaster = $isMaster;
        $copy->staffShopCd = $shopCd;

        return $copy;
    }

    /** Carpeta cuyas カスタマイズ検索 se usan (`tb_search_cate.category`): `category1`, `category3`… */
    public function forCategory(string $category): static
    {
        $copy = clone $this;
        $copy->category = $category;
        $copy->customForms = null;

        return $copy;
    }

    /**
     * `tb_customer` + condiciones base del legacy (`touroku_kubun = '2'` y `disp_flg` según el
     * parámetro `disp_flg`: 削除済 => '9', 退会済 => '0', resto o ausente => '1') + apply().
     */
    public function query(array $params): Builder
    {
        $query = DB::table('tb_customer');
        $this->applyBase($query, $params, 'tb_customer');

        return $this->apply($query, $params);
    }

    /**
     * Añade a una consulta sobre `tb_customer` (con o sin alias: `tb_customer as c`) las
     * condiciones del formulario, agrupadas con AND u OR (`search_method1_type`), negadas si
     * `search_method2_type = 2` y, si no es master, restringidas a la tienda del personal.
     * No añade las condiciones base (touroku_kubun/disp_flg): eso lo hace query().
     */
    public function apply(Builder|EloquentBuilder $query, array $params): Builder|EloquentBuilder
    {
        $base = $query instanceof EloquentBuilder ? $query->getQuery() : $query;
        $t = $this->qualifier($base);
        $conditions = $this->compile($params)['conditions'];
        $useOr = ($params['search_method1_type'] ?? null) == '2';

        // Grupo (c1 AND c2 …) o (c1 OR c2 …)
        $group = function (Builder $q, string $table) use ($conditions, $useOr): void {
            foreach ($conditions as $condition) {
                $q->where(fn (Builder $w) => $condition($w, $table), null, null, $useOr ? 'or' : 'and');
            }
        };

        if (($params['search_method2_type'] ?? null) == '2') {
            // 検索条件に一致しないもの
            $base->whereNotIn("$t.cd", function (Builder $sub) use ($group, $conditions, $params): void {
                $sub->select('cd')->from('tb_customer');
                $this->applyBase($sub, $params, 'tb_customer');
                if ($conditions) {
                    $sub->where(fn (Builder $q) => $group($q, 'tb_customer'));
                }
            });
        } elseif ($conditions) {
            $base->where(fn (Builder $q) => $group($q, $t));
        }

        if (! $this->isMaster) {
            $base->where("$t.tb_shop_cd", '=', (int) $this->staffShopCd);
        }

        return $query;
    }

    /**
     * Socios activos (touroku_kubun '2', disp_flg '1') cuyo teléfono coincide, comparando solo
     * dígitos, con `tel`, `tel2`, `kojin_mobile`, `kinmusaki_tel`, `tantou_tel` o `fax`.
     *
     * @return Collection<int, object{cd:int, id:string, name:?string, name_kana:?string, tb_shop_cd:?int}>
     */
    public function byPhone(string $phone): Collection
    {
        $digits = preg_replace('/\D/', '', mb_convert_kana($phone, 'n'));
        if ($digits === '') {
            return collect();
        }

        $query = DB::table('tb_customer')->select(['cd', 'id', 'name', 'name_kana', 'tb_shop_cd']);
        $this->applyBase($query, [], 'tb_customer');
        $query->where(function (Builder $q) use ($digits): void {
            foreach (self::PHONE_COLUMNS as $column) {
                $q->orWhereRaw("regexp_replace(coalesce({$this->wrap($q, 'tb_customer', $column)}, ''), '\\D', '', 'g') = ?", [$digits]);
            }
        });
        if (! $this->isMaster) {
            $query->where('tb_shop_cd', '=', (int) $this->staffShopCd);
        }

        return $query->orderBy('cd')->get();
    }

    /**
     * Forma serializable de la búsqueda (sustituye a `$_SESSION['wherestr_<uniqid>']` y
     * `$_SESSION['search_display_<uniqid>']` del legacy). Nunca contiene SQL.
     *
     * [
     *   'version'  => 1,
     *   'category' => 'category1',              // forCategory()
     *   'staff'    => ['is_master' => bool, 'shop_cd' => ?int],
     *   'params'   => [...],                    // solo parámetros que lee el buscador, sin vacíos
     *   'display'  => ['名前：山田', ...],      // resumen 検索条件 (search_display del legacy)
     * ]
     *
     * El legacy solo guardaba el filtro si llegaba `se_flg = 1`; si no, la sesión quedaba con
     * "todos los socios activos". Esa decisión es del controlador (guardar describe([]) o no).
     */
    public function describe(array $params): array
    {
        $params = $this->knownParams($params);

        return [
            'version' => self::DESCRIPTION_VERSION,
            'category' => $this->category,
            'staff' => ['is_master' => $this->isMaster, 'shop_cd' => $this->staffShopCd],
            'params' => $params,
            'display' => $this->compile($params)['display'],
        ];
    }

    /** Reconstruye la consulta desde describe() (CSV, 配信, encuestas…) */
    public function queryFromDescription(array $description): Builder
    {
        $search = $this->forCategory((string) ($description['category'] ?? 'category1'))
            ->forStaff(
                (bool) ($description['staff']['is_master'] ?? false),
                isset($description['staff']['shop_cd']) ? (int) $description['staff']['shop_cd'] : null,
            );

        return $search->query((array) ($description['params'] ?? []));
    }

    // ------------------------------------------------------------------
    // Construcción de condiciones
    // ------------------------------------------------------------------

    /** Condiciones base del legacy ($mainWhereStr) */
    private function applyBase(Builder $query, array $params, string $t): void
    {
        $dispFlg = match ($params['disp_flg'] ?? null) {
            '削除済' => '9',
            '退会済' => '0',
            default => '1',
        };
        $query->where("$t.touroku_kubun", '=', '2')->where("$t.disp_flg", '=', $dispFlg);
    }

    /**
     * Traduce los parámetros a condiciones (closures `fn (Builder $q, string $tabla)` que
     * añaden UNA condición con AND) y al resumen 検索条件.
     *
     * @return array{conditions: list<Closure(Builder, string): void>, display: list<string>}
     */
    private function compile(array $params): array
    {
        $conditions = [];
        $display = [];
        $add = function (Closure $condition, ?string $text = null) use (&$conditions, &$display): void {
            $conditions[] = $condition;
            if ($text !== null) {
                $display[] = $text;
            }
        };

        $dates = [];

        // 1) Campos de tb_customer (bucle foreach $_REQUEST del legacy, mismo orden)
        foreach ($params as $key => $raw) {
            if (! is_string($key) || ! preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                continue;
            }
            if ($raw === null || $raw === '' || $raw === []) {
                continue;
            }
            $val = is_array($raw) ? $raw : $this->clean($raw);
            if ($val === '') {
                continue;
            }
            $scalar = is_string($val);

            if ($key === 'id' && $scalar) {
                $clean = mb_convert_kana(str_replace(["\r", "\n", "'", ' ', '　'], '', $val), 'a');
                $ids = array_values(array_filter(explode(',', $clean)));
                $ids = $ids ?: ['-1'];
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.id", $ids), '会員ID：'.implode(',', $ids));
            } elseif ($key === 'tb_shop_cd' && $this->isMaster) {
                $shops = array_map('intval', array_values((array) $val));
                if ($shops) {
                    $add(fn (Builder $q, string $t) => $q->whereIn("$t.tb_shop_cd", $shops), '店舗：'.implode(',', (array) $val));
                }
            } elseif (isset(self::EQUAL_FIELDS[$key]) && $scalar) {
                $add(fn (Builder $q, string $t) => $q->where("$t.$key", '=', $val), self::EQUAL_FIELDS[$key].$val);
            } elseif (isset(self::LIKE_FIELDS[$key]) && $scalar) {
                $add(fn (Builder $q, string $t) => $q->where("$t.$key", 'like', '%'.$val.'%'), self::LIKE_FIELDS[$key].'：'.$val);
            } elseif (isset(self::LIKE_ESCAPED_FIELDS[$key]) && $scalar) {
                // En PostgreSQL `_` es comodín: el legacy lo escapaba con '#'
                if (str_contains($val, '_')) {
                    $pattern = '%'.str_replace('_', '#_', $val).'%';
                    $add(fn (Builder $q, string $t) => $q->whereRaw("{$this->wrap($q, $t, $key)} like ? escape '#'", [$pattern]), self::LIKE_ESCAPED_FIELDS[$key].'：'.$val);
                } else {
                    $add(fn (Builder $q, string $t) => $q->where("$t.$key", 'like', '%'.$val.'%'), self::LIKE_ESCAPED_FIELDS[$key].'：'.$val);
                }
            } elseif ($key === 'kojin_birth' && $scalar) {
                $add(
                    fn (Builder $q, string $t) => $q->whereRaw("split_part({$this->wrap($q, $t, 'kojin_birth')}, '/', 2) = ?", [$val]),
                    $this->clean($params['kojin_birth2'] ?? '') !== '' ? $val.'月'.$this->clean($params['kojin_birth2']).'日生まれ' : $val.'月生まれ',
                );
            } elseif ($key === 'kojin_birth2' && $scalar) {
                $add(
                    fn (Builder $q, string $t) => $q->whereRaw("split_part({$this->wrap($q, $t, 'kojin_birth')}, '/', 3) = ?", [$val]),
                    $this->clean($params['kojin_birth'] ?? '') === '' ? $val.'日生まれ' : null,
                );
            } elseif (isset(self::EQUAL_CD_FIELDS[$key]) && $scalar) {
                // bigint comparado con texto en el legacy: un valor no numérico no encuentra nada
                $cd = preg_match('/^-?\d+$/', $val) ? (int) $val : null;
                $add(
                    fn (Builder $q, string $t) => $cd === null ? $q->whereRaw('1 = 0') : $q->where("$t.$key", '=', $cd),
                    self::EQUAL_CD_FIELDS[$key].'：'.$val,
                );
            } elseif (isset(self::INT_FIELDS[$key]) && $scalar) {
                $label = self::INT_FIELDS[$key].'：'.match ($key) {
                    'kingaku_rank_now', 'kingaku_rank_pre' => $this->rankName('tb_kingaku_rank_master', (int) $val),
                    'raiten_rank_now', 'raiten_rank_pre' => $this->rankName('tb_raiten_rank_master', (int) $val),
                    default => $val,
                };
                $add(fn (Builder $q, string $t) => $q->where("$t.$key", '=', (int) $val), $label);
            } elseif (in_array($key, ['touroku_date_1', 'touroku_date_2', 'update_date_1', 'update_date_2'], true) && $scalar) {
                $end = str_ends_with($key, '_2');
                $epoch = $this->ymdToEpoch($val, $end);
                if ($epoch !== null) {
                    $dates[$key] = $epoch;
                    $display[] = (str_starts_with($key, 'touroku') ? '登録日' : '情報変更日').($end ? ' 終：' : ' 始：').$val;
                }
            } elseif ($key === 'mailmaga_flg' && $scalar) {
                $flg = ['配信あり' => '1', '配信なし' => '0', '不達配信' => '9'][$val] ?? null;
                if ($flg !== null) {
                    $conditions[] = fn (Builder $q, string $t) => $q->where("$t.mailmaga_flg", '=', $flg);
                }
                $display[] = 'メルマガ区分：'.$val;
            } elseif (($key === 'age_1' || $key === 'age_2') && $scalar) {
                $op = $key === 'age_1' ? '>=' : '<=';
                $add(
                    fn (Builder $q, string $t) => $q->whereRaw("date_part('year', age(to_timestamp({$this->wrap($q, $t, 'kojin_birth_date')}))) $op ?", [(int) $val]),
                    ($key === 'age_1' ? '年齢 始：' : '年齢 終：').$val,
                );
            } elseif (($key === 'revocation_point_date_1' || $key === 'revocation_point_date_2') && $scalar) {
                $end = $key === 'revocation_point_date_2';
                $epoch = $this->ymdToEpoch($val, $end);
                if ($epoch !== null) {
                    $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $sub) => $sub
                        ->select('tb_customer_cd')->from('tb_point_log')
                        ->where('action_flg', '=', '7')
                        ->whereRaw("coalesce(disp_flg, '') <> '9'")
                        ->where('touroku_date', $end ? '<=' : '>=', $epoch)
                    ), ($end ? 'ポイント失効日 終：' : 'ポイント失効日 始：').$val);
                }
            } elseif (($key === 'revocation_point_day_1' || $key === 'revocation_point_day_2') && $scalar) {
                $op = $key === 'revocation_point_day_1' ? '>=' : '<=';
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $sub) => $sub
                    ->select('tb_customer_cd')->from('tb_point_log')
                    ->where('action_flg', '=', '7')
                    ->whereRaw("coalesce(disp_flg, '') <> '9'")
                    ->groupBy('tb_customer_cd')
                    ->havingRaw("sum(shouhi_point) $op ?", [(int) $val])
                ), ($op === '>=' ? '失効ポイント 始：' : '失効ポイント 終：').$val);
            } elseif ($scalar && preg_match('/^(stamp_count|error_send_cnt)_([12])$/', $key, $m) && preg_match('/^[0-9]+$/', $val)) {
                $column = $m[1];
                if ($m[2] === '1') {
                    $add(fn (Builder $q, string $t) => $q->where("$t.$column", '>=', (int) $val), self::COUNT_RANGES[$column][0].'：'.$val);
                } else {
                    $add(fn (Builder $q, string $t) => $q->whereRaw("coalesce({$this->wrap($q, $t, $column)}, 0) <= ?", [(int) $val]), self::COUNT_RANGES[$column][1].'：'.$val);
                }
            } elseif ($key === 'service_point_1' && $scalar && preg_match('/^[0-9]+$/', $val)) {
                // Desde 2012 la ポイント está en tb_customer_point
                $to = $this->clean($params['service_point_2'] ?? '');
                $hasTo = preg_match('/^[0-9]+$/', $to) === 1;
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", function (Builder $sub) use ($val, $to, $hasTo): void {
                    $sub->select('tb_customer_cd')->from('tb_customer_point');
                    $hasTo
                        ? $sub->whereBetween('service_point', [(int) $val, (int) $to])
                        : $sub->where('service_point', '>=', (int) $val);
                }), '不達配信数 始：'.$val); // (sic) etiqueta del legacy
            } elseif ($key === 'service_point_2' && $scalar && preg_match('/^[0-9]+$/', $val)
                && ! preg_match('/^[0-9]+$/', $this->clean($params['service_point_1'] ?? ''))) {
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $sub) => $sub
                    ->select('tb_customer_cd')->from('tb_customer_point')->where('service_point', '<=', (int) $val)
                ), '不達配信数 終：'.$val); // (sic)
            } elseif ($key === 'my_colle_use' && $scalar) {
                if ($val === '有') {
                    $conditions[] = fn (Builder $q, string $t) => $q->whereRaw("octet_length({$this->wrap($q, $t, 'registration_id')}) > 20");
                } elseif ($val === '無') {
                    $conditions[] = fn (Builder $q, string $t) => $q->whereRaw("(octet_length({$this->wrap($q, $t, 'registration_id')}) <= 20 or {$this->wrap($q, $t, 'registration_id')} is null)");
                }
                $display[] = 'マイコレ使用'.$val;
            } elseif ($key === 'felica_no' && $scalar) {
                if ($val === '有') {
                    $conditions[] = fn (Builder $q, string $t) => $q->where("$t.felica_no", '<>', '');
                } elseif ($val === '無') {
                    $conditions[] = fn (Builder $q, string $t) => $q->where(fn (Builder $w) => $w->where("$t.felica_no", '=', '')->orWhereNull("$t.felica_no"));
                }
                $display[] = '端末登録：'.$val;
            }
            // belongpost: no existe en tb_customer (en el legacy rompía la consulta) → se ignora
        }

        // 2) 登録日 (touroku_date::integer) y 情報変更日 (update_date como texto, igual que el legacy)
        foreach (['touroku_date' => true, 'update_date' => false] as $column => $numeric) {
            $from = $dates[$column.'_1'] ?? null;
            $to = $dates[$column.'_2'] ?? null;
            if ($from === null && $to === null) {
                continue;
            }
            $conditions[] = function (Builder $q, string $t) use ($column, $numeric, $from, $to): void {
                $expr = $numeric ? "nullif({$this->wrap($q, $t, $column)}, '')::bigint" : $this->wrap($q, $t, $column);
                $cast = fn (int $epoch) => $numeric ? $epoch : (string) $epoch;
                if ($from !== null && $to !== null) {
                    $q->whereRaw("$expr between ? and ?", [$cast($from), $cast($to)]);
                } elseif ($from !== null) {
                    $q->whereRaw("$expr > ?", [$cast($from)]);
                } else {
                    $q->whereRaw("$expr < ?", [$cast($to)]);
                }
            };
        }

        // 3) 位置情報 (gps_history a menos de `distance` metros de la tienda en las últimas `interval_gps` horas)
        $gpsShop = $params['distance_shop_cd'] ?? null;
        $distance = $params['distance'] ?? null;
        $interval = $params['interval_gps'] ?? null;
        if ($gpsShop && $distance && $interval && is_numeric($interval)) {
            $conditions[] = fn (Builder $q, string $t) => $q->whereIn("$t.login_id", fn (Builder $sub) => $sub
                ->select('g.login_id')->distinct()
                ->from('gps_history as g')->crossJoin('tb_shop as s')
                ->where('s.cd', '=', (int) $gpsShop)
                ->whereRaw("to_timestamp(g.touroku_date) >= now() - (?::text || ' hour')::interval", [(string) $interval])
                ->whereRaw('6372795.477598 * acos(cos(radians(s.latitude)) * cos(radians(g.lat)) * cos(radians(g.lng) - radians(s.longitude)) + sin(radians(s.latitude)) * sin(radians(g.lat))) <= ?', [(int) $distance])
            );
        }

        // 4) カスタマイズ検索 (tb_customer_sub)
        $this->compileCustom($params, $add);

        // 5) 来店履歴
        $this->compileRireki($params, $add);

        // 6) 会計 (tb_point_log / tb_stamp_log)
        $this->compileKaikei($params, $add);

        // TODO(S2): POS会計金額 (`search_POS_kaikei_*`). El legacy ejecuta la consulta en el servidor POS de la
        // tienda (tb_order_main/tb_order_sub, tb_c2_catalog_cd) y sustituye `{POS_RESULT}` al listar. POS está
        // fuera de alcance (CLAUDE.md): cuando exista PosGateway, añadir aquí `cd IN/NOT IN (<cds del POS>)`.

        return ['conditions' => $conditions, 'display' => $display];
    }

    /** カスタマイズ検索: tb_search_cate / tb_search_form de la categoría → subconsultas sobre tb_customer_sub */
    private function compileCustom(array $params, Closure $add): void
    {
        $hasCustomParams = isset($params['yoyaku_menu_cd']) || array_filter(
            array_keys($params),
            fn ($k) => is_string($k) && preg_match('/^form\d+/', $k),
        );
        if (! $hasCustomParams) {
            return;
        }

        foreach ($this->customForms() as $form) {
            $cd = (int) $form['cd'];
            $type = (string) $form['form_type'];
            $name = (string) $form['name'];
            $p = fn (string $suffix = '') => $this->clean($params['form'.$cd.$suffix] ?? '');
            $digits = fn (?string $v) => $v !== null && preg_match('/^[0-9]+$/', $v) === 1;

            $where = null; // Closure(Builder $sub) sobre tb_customer_sub
            $text = null;

            if (in_array($type, self::CUSTOM_LIKE_TYPES, true) && $p() !== '') {
                $value = $p();
                $where = fn (Builder $s) => $s->where('text_data', 'like', '%'.$value.'%');
                $text = $name.'：'.$value;
            } elseif ($type === '数値テキスト') {
                [$from, $to] = [$p('_from'), $p('_to')];
                if ($digits($from) && $digits($to)) {
                    $where = fn (Builder $s) => $s->whereBetween('float_data', [(float) $from, (float) $to]);
                } elseif ($digits($from) && $to === '') {
                    $where = fn (Builder $s) => $s->where('float_data', '>=', (float) $from);
                }
                $text = $name.'：'.$from.'～'.$to;
            } elseif ($type === '年') {
                [$from, $to] = [$p('_from'), $p('_to')];
                if ($digits($from) && $digits($to)) {
                    $where = fn (Builder $s) => $s->whereBetween('text_data', [$from, $to]);
                } elseif ($digits($from) && $to === '') {
                    $where = fn (Builder $s) => $s->where('text_data', '>=', $from);
                }
                $text = $name.'：'.$from.'～'.$to;
            } elseif ($type === '年月' || $type === '年月日') {
                $parts = $type === '年月' ? 2 : 3;
                [$fromRaw, $toRaw] = [$p('_from'), $p('_to')];
                $from = explode('/', $fromRaw);
                $to = explode('/', $toRaw);
                $okFrom = $this->allDigits($from, $parts);
                $okTo = $this->allDigits($to, $parts);
                $noTo = $this->noDigits($to, $parts);
                if ($okFrom && $okTo) {
                    $pad = fn (array $d) => implode('/', array_map(
                        fn ($v, $i) => $i === 0 ? $v : str_pad($v, 2, '0', STR_PAD_LEFT),
                        array_slice($d, 0, $parts),
                        array_keys(array_slice($d, 0, $parts)),
                    ));
                    [$f, $tt] = [$pad($from), $pad($to)];
                    $where = fn (Builder $s) => $s->whereBetween('text_data', [$f, $tt]);
                    $text = $name.'：'.$f.'～'.$tt;
                } elseif ($okFrom && $noTo) {
                    // El legacy usa aquí el valor tal cual (sin rellenar con ceros)
                    $where = fn (Builder $s) => $s->where('text_data', '>=', $fromRaw);
                    $text = $name.'：'.$fromRaw.'～'.$toRaw;
                }
            } elseif ($type === '起算日') {
                [$from, $to] = [$p('_from'), $p('_to')];
                if ($digits($from) && $digits($to)) {
                    [$fromBirth, $toBirth] = [$this->calcBirth((int) $from), $this->calcBirth((int) $to)];
                    $where = fn (Builder $s) => $s->whereBetween('text_data', [$toBirth, $fromBirth]);
                } elseif ($digits($from) && $to === '') {
                    $fromBirth = $this->calcBirth((int) $from);
                    $where = fn (Builder $s) => $s->where('text_data', '<=', $fromBirth);
                }
                $text = $name.'：'.$from.'～'.$to;
            } elseif ($type === '月日') {
                [$mmm, $ddd] = [$this->clean($params['form'.$cd.'_mmm'] ?? ''), $this->clean($params['form'.$cd.'_ddd'] ?? '')];
                if ($mmm !== '' && $ddd !== '') {
                    $where = fn (Builder $s) => $s->where('text_data', '=', $mmm.'/'.$ddd);
                    $text = $name.'：'.$mmm.'月'.$ddd.'日';
                } elseif ($mmm !== '') {
                    $where = fn (Builder $s) => $s->whereRaw("split_part(text_data, '/', 1) = ?", [$mmm]);
                    $text = $name.'：'.$mmm.'月';
                } elseif ($ddd !== '') {
                    $where = fn (Builder $s) => $s->whereRaw("split_part(text_data, '/', 2) = ?", [$ddd]);
                    $text = $name.'：'.$ddd.'日';
                }
            }

            if ($where !== null) {
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $sub) => $sub
                    ->select('tb_customer_cd')->from('tb_customer_sub')
                    ->where($where)
                    ->where('disp_flg', '=', '1')
                    ->where('tb_search_form_cd', '=', $cd)
                ), $text);
            }

            if ($type === 'チェックボックス') {
                // text_data = 'a<>b<>…'; and => @> (todas), or => && (alguna)
                $operator = ($params['form'.$cd.'_andor_flg'] ?? null) === 'or' ? '&&' : '@>';
                $values = [];
                for ($row = 0; $row < (int) $form['flds_row']; $row++) {
                    $v = $params['form'.$cd.'_'.$row] ?? null;
                    if (is_scalar($v) && $v) { // truthiness de PHP, como el legacy
                        $values[] = (string) $v;
                    }
                }
                if ($values) {
                    $placeholders = implode(', ', array_fill(0, count($values), '?'));
                    $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $sub) => $sub
                        ->select('tb_customer_cd')->distinct()->from('tb_customer_sub')
                        ->where('tb_search_form_cd', '=', $cd)
                        ->whereRaw("string_to_array(text_data, '<>') $operator array[$placeholders]::text[]", $values)
                    ), $name.'：'.implode($operator === '&&' ? ' or ' : ' and ', array_map(fn ($v) => "'$v'", $values)));
                }
            }

            if ($type === 'メニュー項目' && is_array($params['yoyaku_menu_cd'] ?? null) && $params['yoyaku_menu_cd']) {
                $this->compileMenuItem($params, $cd, $name, $add);
            }
        }
    }

    /** メニュー項目: menús reservados en los 来店履歴 (todos o el último) */
    private function compileMenuItem(array $params, int $formCd, string $name, Closure $add): void
    {
        $menuCds = array_map('intval', array_keys($params['yoyaku_menu_cd']));
        $menuValues = array_map('strval', $menuCds);
        $operator = ($params['yoyaku_andor_flg'] ?? null) === 'or' ? '&&' : '@>';
        $latest = ($params['yoyaku_raiten_flg'] ?? null) === '直近';
        $menuNames = DB::table('tb_yoyaku_koumoku_master')->whereIn('cd', $menuCds)->pluck('name', 'cd');
        $placeholders = implode(', ', array_fill(0, count($menuValues), '?'));

        $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", function (Builder $sub) use ($formCd, $operator, $latest, $menuValues, $placeholders): void {
            $sub->select('tb1.user_cd')->distinct()->from('tb_raiten_rireki as tb1');
            if ($latest) {
                $sub->joinSub(
                    DB::table('tb_raiten_rireki')
                        ->selectRaw('user_cd, max(raiten_time::int) as last')
                        ->whereRaw("coalesce(disp_flg, '') <> '9'")
                        ->groupBy('user_cd'),
                    'tb2',
                    fn ($join) => $join->on('tb1.user_cd', '=', 'tb2.user_cd')->whereRaw('tb1.raiten_time::int = tb2.last'),
                );
            }
            $sub->join('tb_customer_sub as tb3', function ($join): void {
                $join->on('tb1.user_cd', '=', 'tb3.tb_customer_cd')
                    ->on('tb1.tb_search_cate_cd', '=', 'tb3.tb_search_cate_cd')
                    ->on('tb1.tb_customer_sub_inner_group', '=', 'tb3.category_inner_group');
            })
                ->whereRaw("coalesce(tb1.disp_flg, '') <> '9'")
                ->where('tb3.tb_search_form_cd', '=', $formCd)
                ->where('tb3.disp_flg', '=', '1')
                ->whereRaw("string_to_array(tb3.text_data, '<>') $operator array[$placeholders]::text[]", $menuValues);
        }), $name.'：'.implode('、', array_map(fn ($cd) => (string) ($menuNames[$cd] ?? ''), $menuCds))
            .'（'.($operator === '&&' ? 'OR' : 'AND').'・'.($latest ? '直近' : '全').'来店履歴）');
    }

    /** 来店履歴 (tb_raiten_rireki) + 最終来店日から + 平均来店周期 + 予測来店日 */
    private function compileRireki(array $params, Closure $add): void
    {
        $type = (string) ($params['search_rireki_type_flg'] ?? '');
        if (in_array($type, ['1', '2', '3', '4', '5', '6', '7'], true)) {
            $words = '';
            $range = null; // [inicioSQL|int, finSQL|int]
            if ((int) $type <= 5) {
                $interval = [1 => '7 DAYS', 2 => '1 MONTH', 3 => '3 MONTHS', 4 => '6 MONTHS', 5 => '1 YEAR'][(int) $type];
                // Mismas expresiones que el legacy (sin datos del usuario)
                $range = [
                    "extract(epoch from timestamp 'today' - interval '$interval')::integer",
                    "extract(epoch from timestamp 'now')::integer",
                ];
                $words .= '過去'.[1 => '一週間', 2 => '一カ月', 3 => '3カ月', 4 => '半年', 5 => '一年'][(int) $type];
            } elseif ($type === '6') {
                $start = $this->strToEpoch($params, 'search_rireki_start');
                $end = $this->strToEpoch($params, 'search_rireki_end');
                $range = ($start !== null && $end !== null) ? [$start, $end] : false;
                $words .= ($params['search_rireki_start_year'] ?? '').'/'.($params['search_rireki_start_month'] ?? '').'/'.($params['search_rireki_start_day'] ?? '')
                    .'～'.($params['search_rireki_end_year'] ?? '').'/'.($params['search_rireki_end_month'] ?? '').'/'.($params['search_rireki_end_day'] ?? '').'の期間';
            } else {
                $words .= '過去全て';
            }

            $day = $this->clean($params['search_rireki_day'] ?? '');
            if ($day !== '') {
                $words .= '('.$day.'日指定で)';
            }
            $dow = $this->clean($params['search_rireki_dow'] ?? '');
            if ($dow !== '') {
                $words .= '('.([1 => '日', 2 => '月', 3 => '火', 4 => '水', 5 => '木', 6 => '金', 7 => '土'][(int) $dow] ?? '').'曜日指定で)';
            }
            $shop = $this->clean($params['search_rireki_shop_cd'] ?? '');
            if ($shop !== '') {
                $words .= '「'.$this->shopName((int) $shop).'」に来店';
            }

            $times = mb_convert_kana($this->clean($params['search_rireki_times'] ?? ''), 'n');
            $times2 = mb_convert_kana($this->clean($params['search_rireki_times2'] ?? ''), 'n');
            $words .= '・来店回数が';
            $having = [];
            $in = true;
            if (preg_match('/^[0-9]+$/', $times) && (int) $times > 0) {
                $having[] = ['>=', (int) $times];
                $words .= (int) $times.'回以上';
                if (preg_match('/^[0-9]+$/', $times2)) {
                    $having[] = ['<=', (int) $times2];
                    $words .= (int) $times2.'回未満';
                }
            } elseif (preg_match('/^[0-9]+$/', $times2)) {
                $in = false;
                $having[] = ['>', (int) $times2];
                $words .= (int) $times2.'回未満';
            }

            // TODO(S2): con 期間指定 y una fecha que strtotime() no entiende, el legacy generaba SQL roto; aquí se ignora el filtro.
            if ($having && $range !== false) {
                $add(function (Builder $q, string $t) use ($range, $day, $dow, $shop, $having, $in): void {
                    $sub = function (Builder $s) use ($range, $day, $dow, $shop, $having): void {
                        $s->select('user_cd')->from('tb_raiten_rireki')->whereRaw("coalesce(disp_flg, '') <> '9'");
                        if ($range !== null) {
                            [$from, $to] = $range;
                            is_int($from)
                                ? $s->whereRaw('raiten_time::integer > ? and raiten_time::integer <= ?', [$from, $to])
                                : $s->whereRaw("raiten_time::integer > $from and raiten_time::integer <= $to");
                        }
                        if ($day !== '') {
                            $s->whereRaw("to_char(to_timestamp(raiten_time::integer), 'DD') = ?", [$day]);
                        }
                        if ($dow !== '') {
                            $s->whereRaw("to_char(to_timestamp(raiten_time::integer), 'D') = ?", [$dow]);
                        }
                        if ($shop !== '') {
                            $s->where('shop_cd', '=', (int) $shop);
                        }
                        $s->groupBy('user_cd');
                        foreach ($having as [$op, $n]) {
                            $s->havingRaw("count(*) $op ?", [$n]);
                        }
                    };
                    $in ? $q->whereIn("$t.cd", $sub) : $q->whereNotIn("$t.cd", $sub);
                }, $words);
            }
        }

        // 最終来店日から○○日
        $days = $this->clean($params['search_rireki_daysFromLastVisit'] ?? '');
        if (preg_match('/^[0-9]+$/', $days)) {
            $add(
                fn (Builder $q, string $t) => $q->whereRaw("now()::date - nullif({$this->wrap($q, $t, 'last_raiten_date')}, '')::date = ?", [(int) $days]),
                '最終来店日から'.(int) $days.'日',
            );
        }

        // 平均来店周期
        $avg1 = $this->clean($params['search_rireki_averagePeriodTime1'] ?? '');
        $avg2 = $this->clean($params['search_rireki_averagePeriodTime2'] ?? '');
        $d1 = preg_match('/^[0-9]+$/', $avg1) === 1;
        $d2 = preg_match('/^[0-9]+$/', $avg2) === 1;
        if ($d1 && $d2) {
            $add(fn (Builder $q, string $t) => $q->whereBetween("$t.shuki_heikin", [(int) $avg1, (int) $avg2]), '平均来店周期 '.(int) $avg1.'日以上～'.(int) $avg2.'日以下');
        } elseif ($d1) {
            $add(fn (Builder $q, string $t) => $q->where("$t.shuki_heikin", '>=', (int) $avg1), '平均来店周期 '.(int) $avg1.'日以上');
        } elseif ($d2) {
            $add(fn (Builder $q, string $t) => $q->where("$t.shuki_heikin", '<=', (int) $avg2), '平均来店周期 '.(int) $avg2.'日以下');
        }

        // 予測来店日 = last_raiten_date + shuki_heikin días
        $y1 = $this->validYmd($this->clean($params['search_raiten_yosoku_1'] ?? ''));
        $y2 = $this->validYmd($this->clean($params['search_raiten_yosoku_2'] ?? ''));
        if ($y1 !== null || $y2 !== null) {
            $add(function (Builder $q, string $t) use ($y1, $y2): void {
                $expr = "(nullif({$this->wrap($q, $t, 'last_raiten_date')}, '')::date + ({$this->wrap($q, $t, 'shuki_heikin')}::text || ' days')::interval)";
                if ($y1 !== null && $y2 !== null) {
                    $q->whereRaw("$expr between ?::timestamp and ?::timestamp", [$y1, $y2]);
                } elseif ($y1 !== null) {
                    $q->whereRaw("$expr >= ?::timestamp", [$y1]);
                } else {
                    $q->whereRaw("$expr <= ?::timestamp", [$y2]);
                }
                $q->whereNotNull("$t.shuki_heikin");
            }, match (true) {
                $y1 !== null && $y2 !== null => '予測来店日 '.$y1.' ～ '.$y2,
                $y1 !== null => '予測来店日 '.$y1.' 以降 ',
                default => '予測来店日 '.$y2.' まで',
            });
        }
    }

    /** 会計: promedio de `kaikei` en tb_point_log (o tb_stamp_log si `search_kaikei_resource = スタンプ会計処理`) */
    private function compileKaikei(array $params, Closure $add): void
    {
        $k1 = $this->clean($params['search_kaikei_kingaku1'] ?? '');
        $k2 = $this->clean($params['search_kaikei_kingaku2'] ?? '');
        $d1 = preg_match('/^[0-9]+$/', $k1) === 1;
        $d2 = preg_match('/^[0-9]+$/', $k2) === 1;
        if (! $d1 && ! $d2) {
            return;
        }

        $tz = new DateTimeZone(self::TIMEZONE);
        $words = '';
        $start = $end = null;
        if ($this->checkdate($params, 'search_kaikei_start')) {
            $hour = $this->clean($params['search_kaikei_start_time'] ?? '');
            $start = $this->strToEpochRaw($params['search_kaikei_start_year'].'/'.$params['search_kaikei_start_month'].'/'.$params['search_kaikei_start_day'].' '.($hour === '' ? '00:00:00' : $hour.':00:00'));
            if ($start !== null) {
                $words .= (new DateTimeImmutable('@'.$start))->setTimezone($tz)->format('Y年m月d日').($hour !== '' ? ' '.$hour.'時' : '').'から ';
            }
        }
        if ($this->checkdate($params, 'search_kaikei_end')) {
            $hour = $this->clean($params['search_kaikei_end_time'] ?? '');
            $end = $this->strToEpochRaw($params['search_kaikei_end_year'].'/'.$params['search_kaikei_end_month'].'/'.$params['search_kaikei_end_day'].' '.(($hour === '' || $hour === '24') ? '23:59:59' : $hour.':00:00'));
            if ($end !== null) {
                $words .= (new DateTimeImmutable('@'.$end))->setTimezone($tz)->format('Y年m月d日').($hour !== '' ? ' '.$hour.'時' : '').'まで ';
            }
        }
        $shop = $this->clean($params['search_kaikei_shop_cd'] ?? '');
        if ($shop !== '') {
            $words .= $this->shopName((int) $shop).'で';
        }
        if ($start !== null || $end !== null || $shop !== '') {
            $words .= '処理した ';
        }
        $words .= '平均会計金額が';

        $log = ($params['search_kaikei_resource'] ?? null) === 'スタンプ会計処理' ? 'stamp' : 'point';

        $sub = function (Builder $s, string $having, array $bindings) use ($log, $start, $end, $shop): void {
            $s->select('tb2.tb_customer_cd');
            if ($start !== null || $end !== null) {
                $s->from('tb_raiten_rireki as tb1')->join("tb_{$log}_log as tb2", "tb1.tb_{$log}_log_cd", '=', 'tb2.cd');
                if ($start !== null) {
                    $s->whereRaw('tb1.raiten_time::integer >= ?', [$start]);
                }
                if ($end !== null) {
                    $s->whereRaw('tb1.raiten_time::integer <= ?', [$end]);
                }
            } else {
                $s->from("tb_{$log}_log as tb2");
            }
            if ($shop !== '') {
                $s->where('tb2.tb_shop_cd', '=', (int) $shop);
            }
            $s->groupBy('tb2.tb_customer_cd')->havingRaw($having, $bindings);
        };

        if ($d1) {
            if ($d2) {
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $s) => $sub($s, 'avg(tb2.kaikei) between ? and ?', [(int) $k1, (int) $k2])),
                    $words.number_format((int) $k1).'円以上～'.number_format((int) $k2).'円以下');
            } else {
                $add(fn (Builder $q, string $t) => $q->whereIn("$t.cd", fn (Builder $s) => $sub($s, 'avg(tb2.kaikei) >= ?', [(int) $k1])),
                    $words.number_format((int) $k1).'円以上');
            }
        } else {
            $add(fn (Builder $q, string $t) => $q->whereNotIn("$t.cd", fn (Builder $s) => $sub($s, 'avg(tb2.kaikei) > ?', [(int) $k2])),
                $words.number_format((int) $k2).'円以下');
        }
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Campos tb_search_form buscables de la categoría, en el orden del legacy */
    private function customForms(): array
    {
        return $this->customForms ??= DB::table('tb_search_form as f')
            ->join('tb_search_cate as c', 'c.cd', '=', 'f.tb_search_cate_cd')
            ->where('c.category', '=', $this->category)
            ->where('c.disp_flg', '<>', '9')
            ->where('c.serach_flg', '=', '1')
            ->where('c.hyouji_flg', '=', '1')
            ->where('f.disp_flg', '<>', '9')
            ->where('f.serach_flg', '=', '1')
            ->orderBy('c.rank')->orderBy('c.cd')->orderBy('f.rank')
            ->get(['f.cd', 'f.name', 'f.form_type', 'f.flds_row'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** Solo los parámetros que lee el buscador (sin vacíos) */
    private function knownParams(array $params): array
    {
        $known = array_flip(array_merge(
            self::KNOWN_KEYS,
            array_keys(self::LIKE_FIELDS),
            array_keys(self::LIKE_ESCAPED_FIELDS),
            array_keys(self::EQUAL_FIELDS),
            array_keys(self::EQUAL_CD_FIELDS),
            array_keys(self::INT_FIELDS),
        ));

        return array_filter(
            $params,
            fn ($value, $key) => is_string($key)
                && (isset($known[$key]) || preg_match('/^form\d+(_[A-Za-z0-9_]+)?$/', $key))
                && $value !== null && $value !== '' && $value !== [],
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** Alias o nombre de tabla de `tb_customer` en la consulta */
    private function qualifier(Builder $query): string
    {
        $from = $query->from;
        if (is_string($from) && preg_match('/\s+as\s+([A-Za-z0-9_]+)\s*$/i', $from, $m)) {
            return $m[1];
        }

        return is_string($from) ? $from : 'tb_customer';
    }

    private function wrap(Builder $query, string $table, string $column): string
    {
        return $query->getGrammar()->wrap($table.'.'.$column);
    }

    /** Recorta espacios 半角/全角 al inicio y al final (get_form2() del legacy) */
    private function clean(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return (string) preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', (string) $value);
    }

    /** 'Y/m/d' → epoch Asia/Tokyo a las 00:00:00 o 23:59:59 (mktime del legacy); null si no es una fecha */
    private function ymdToEpoch(string $value, bool $endOfDay): ?int
    {
        $parts = explode('/', $value);
        if (count($parts) !== 3 || ! $this->allDigits($parts, 3)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', $parts);

        // setDate() desborda como mktime() (2024/02/30 → 2024/03/01)
        return (new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE)))
            ->setDate($y, $m, $d)
            ->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0)
            ->getTimestamp();
    }

    /** 'Y/m/d' válido (checkdate) o null */
    private function validYmd(string $value): ?string
    {
        $parts = array_pad(explode('/', $value), 3, null);

        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]) ? $value : null;
    }

    /** strtotime("<prefix>_year/<prefix>_month/<prefix>_day <prefix>_time") en Asia/Tokyo */
    private function strToEpoch(array $params, string $prefix): ?int
    {
        return $this->strToEpochRaw(
            ($params[$prefix.'_year'] ?? '').'/'.($params[$prefix.'_month'] ?? '').'/'.($params[$prefix.'_day'] ?? '').' '.($params[$prefix.'_time'] ?? ''),
        );
    }

    private function strToEpochRaw(string $value): ?int
    {
        try {
            return (new DateTimeImmutable(trim($value), new DateTimeZone(self::TIMEZONE)))->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    private function checkdate(array $params, string $prefix): bool
    {
        return checkdate(
            (int) ($params[$prefix.'_month'] ?? 0),
            (int) ($params[$prefix.'_day'] ?? 0),
            (int) ($params[$prefix.'_year'] ?? 0),
        );
    }

    /** calc_birth() del legacy: fecha de nacimiento 'Y/m/d' para una edad (hoy - edad - 1 años) */
    private function calcBirth(int $age): string
    {
        $today = new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));

        return ((int) $today->format('Y') - $age - 1).'/'.$today->format('m').'/'.$today->format('d');
    }

    private function allDigits(array $parts, int $count): bool
    {
        for ($i = 0; $i < $count; $i++) {
            if (! preg_match('/^[0-9]+$/', (string) ($parts[$i] ?? ''))) {
                return false;
            }
        }

        return true;
    }

    private function noDigits(array $parts, int $count): bool
    {
        for ($i = 0; $i < $count; $i++) {
            if (preg_match('/^[0-9]+$/', (string) ($parts[$i] ?? ''))) {
                return false;
            }
        }

        return true;
    }

    private function shopName(int $cd): string
    {
        return (string) DB::table('tb_shop')->where('cd', $cd)->value('kaishamei');
    }

    private function rankName(string $table, int $cd): string
    {
        return (string) DB::table($table)->where('cd', $cd)->where('disp_flg', '1')->value('name');
    }
}
