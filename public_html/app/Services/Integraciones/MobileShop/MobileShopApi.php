<?php

namespace App\Services\Integraciones\MobileShop;

use App\Services\Integraciones\LegacyJson;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

/**
 * Consultas de la API de la tienda móvil, con el mismo resultado que el legacy (api/*.php,
 * src/Point.php, src/Sales/SalesItem.php, src/MobileShop/CustomerForMobileShop.php).
 *
 * Tipos: lo que el legacy leía con pg_fetch_* sale como texto (LegacyJson::pgText); lo que leía
 * con Eloquent/PDO (DB::select) conserva los tipos de PDO, igual que aquí.
 * El SQL concatenado del legacy se sustituye por bindings y listas blancas de columnas.
 */
class MobileShopApi
{
    public const DEFAULT_LIMIT = 10;

    public function __construct(private readonly MobileShopDomains $domains)
    {
    }

    /** GET /api/shop */
    public function shops(): array
    {
        $rows = DB::select("SELECT cd, kaishamei, kaishamei_kana FROM tb_shop WHERE disp_flg = '1' ORDER BY cd");

        return LegacyJson::pgText(array_map(fn ($row) => (array) $row, $rows));
    }

    /**
     * GET /api/customer. Devuelve null si no hay ningún filtro (el legacy termina sin imprimir nada),
     * las filas, o '' si no hay resultados. Lanza InvalidFilterException donde el legacy daba error SQL.
     *
     * @throws InvalidFilterException
     */
    public function searchCustomers(array $filters): array|string|null
    {
        $query = DB::table('tb_customer')
            ->selectRaw('id, email1, zip, add as user_address, name, name_kana, kojin_mobile, kojin_birth, tb_shop_cd')
            ->where('disp_flg', '1')
            ->orderBy('cd');

        $columns = Schema::getColumnListing('tb_customer');
        $searched = false;

        foreach ($filters as $key => $value) {
            if (! $value || $value === '') {
                continue;
            }
            $searched = true;
            $value = is_scalar($value) ? (string) $value : json_encode($value);

            match (true) {
                $key === 'id', $key === 'kojin_mobile' => $query->where($key, $value),
                $key === 'tb_shop_cd' => is_numeric($value)
                    ? $query->where('tb_shop_cd', (int) $value)
                    : throw new InvalidFilterException($key),
                $key === 'user_address' => $query->where('add', 'like', "%{$value}%"),
                $key === 'kojin_birth' => $query->where('kojin_birth', 'like', "%{$value}"),
                in_array($key, $columns, true) => $query->whereRaw('"'.$key.'" LIKE ?', ["%{$value}%"]),
                default => throw new InvalidFilterException((string) $key),
            };
        }

        if (! $searched) {
            return null;
        }

        try {
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        } catch (QueryException) {
            // p. ej. LIKE sobre una columna numérica: el legacy respondía «Error.»
            throw new InvalidFilterException('query');
        }

        return $rows === [] ? '' : LegacyJson::pgText($rows);
    }

    /** GET /api/point (src/Point.php fetchList): historial de tb_point_log de un socio por su id público */
    public function pointLog(object $option): stdClass
    {
        [$page, $limit] = $this->paging($option);
        $offset = max(0, ($page - 1) * $limit);

        $core = 'SELECT * FROM tb_point_log WHERE tb_customer_cd = (SELECT cd FROM tb_customer WHERE id = :id)';
        $bindings = ['id' => $option->user_id];

        $res = new stdClass;
        $res->count = (int) DB::select('SELECT COUNT(*) FROM ('.$core.') AS FOO', $bindings)[0]->count;
        $res->data = DB::select("{$core} ORDER BY touroku_date DESC LIMIT {$limit} OFFSET {$offset}", $bindings);
        $res->limit = $limit;
        $res->page = $page;

        return $res;
    }

    /** src/Customer/Customer.php findFromId: cd del socio por su id público (sin filtrar disp_flg) */
    public function customerCdById(mixed $id): ?int
    {
        $cd = DB::table('tb_customer')->where('id', (string) $id)->orderBy('cd')->value('cd');

        return $cd === null ? null : (int) $cd;
    }

    /** Saldo (system/class/Point.php fetchCurrentPoint): fila de tb_customer_point como texto, o false */
    public function currentPoint(int $customerCd): array|false
    {
        $row = DB::table('tb_customer_point')
            ->select('service_point', 'point_yukoukigen')
            ->where('tb_customer_cd', $customerCd)
            ->first();

        return $row ? LegacyJson::pgText((array) $row) : false;
    }

    /** GET /api/mobile_shop/customer/<id>: las 14 claves, todas null si el socio no existe */
    public function customerDetail(string $id): array
    {
        $keys = ['cd', 'id', 'email1', 'name', 'name_kana', 'zip', 'pref', 'add', 'add_build',
            'add_number', 'kojin_birth', 'kojin_mobile', 'touroku_date', 'update_date'];

        $cd = $this->customerCdById($id);
        $row = $cd === null ? null : DB::table('tb_customer')->where('cd', $cd)->first($keys);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $row?->{$key};
        }

        return $result;
    }

    /**
     * GET /api/mobile_shop/customer (CustomerForMobileShop::fetchList): socios de todos los dominios.
     * false si no hay filtros, como el legacy.
     */
    public function searchDomainCustomers(?object $option): stdClass|false
    {
        $res = new stdClass;
        $res->count = 0;
        $res->data = [];

        foreach ($this->domains->list() as $key => $domain) {
            if (! $domain) {
                continue;
            }

            $conditions = ["touroku_kubun = '2'"];
            $bindings = [];
            $searched = false;

            if (isset($option->disp_flg) && $option->disp_flg != '') {
                $conditions[] = 'disp_flg = ?';
                $bindings[] = (string) (int) $option->disp_flg;
                $searched = true;
            }
            if (isset($option->user_id) && $option->user_id != '') {
                $conditions[] = $this->orEquals("'{$key}' || lpad(id, 9, '0')", (string) $option->user_id, $bindings);
                $searched = true;
            }
            if (isset($option->id) && $option->id != '') {
                $conditions[] = $this->orEquals('id', (string) $option->id, $bindings);
                $searched = true;
            }
            if (isset($option->kojin_mobile) && $option->kojin_mobile != '') {
                $conditions[] = 'kojin_mobile = ?';
                $bindings[] = (string) $option->kojin_mobile;
                $searched = true;
            }
            if (! $searched) {
                return false;
            }

            $core = "SELECT '{$key}' AS mycolle_cateogry, id, '{$key}' || lpad(id, 9, '0') AS user_id, email1, zip,"
                .' CONCAT(pref, add, add_build, add_number) AS user_address, name, name_kana, kojin_mobile,'
                .' kojin_birth, tb_shop_cd, disp_flg FROM tb_customer WHERE '.implode(' AND ', $conditions);

            $connection = $this->domains->connection($domain);
            $res->count += (int) $connection->select('SELECT COUNT(*) FROM ('.$core.') AS FOO', $bindings)[0]->count;
            $rows = $connection->select("{$core} ORDER BY id DESC", $bindings);
            if ($rows) {
                $res->data = array_merge($res->data, LegacyJson::pgText(array_map(fn ($row) => (array) $row, $rows)));
            }
        }

        return $res;
    }

    /** GET /api/mobile_shop/sales_items/<cd> (SalesItem::find): la fila con tipos de PDO, o null */
    public function salesItem(int $cd): ?array
    {
        $row = DB::table('sales_items')->where('cd', $cd)->first();

        return $row ? (array) $row : null;
    }

    /** GET /api/mobile_shop/sales_items (SalesItem::getList): null si no hay resultados, como el legacy */
    public function salesItems(array $option): ?stdClass
    {
        $option = (object) $option;
        [$page, $limit] = $this->paging($option);
        $offset = max(0, ($page - 1) * $limit);

        $conditions = ['disp_flg = 1'];
        $bindings = [];

        foreach (['name', 'discription'] as $column) {
            if (isset($option->{$column}) && $option->{$column} != '') {
                $words = explode(' ', str_replace('　', ' ', (string) $option->{$column}));
                $parts = [];
                foreach ($words as $word) {
                    $parts[] = "{$column} ILIKE ?";
                    $bindings[] = "%{$word}%";
                }
                $conditions[] = '('.implode(' AND ', $parts).')';
            }
        }
        if (isset($option->available_shop_cds) && $option->available_shop_cds != '') {
            $raw = is_array($option->available_shop_cds) ? implode(',', $option->available_shop_cds) : (string) $option->available_shop_cds;
            $cds = explode(',', str_replace(['"', '{', '}', ' '], '', $raw));
            $conditions[] = '('.implode(' OR ', array_fill(0, count($cds), '? = ANY(available_shop_cds)')).')';
            array_push($bindings, ...$cds);
        }
        foreach ([
            'is_exchangeable_of_point' => 'is_exchangeable_of_point = ?',
            'number_of_points_exchanged_start' => 'number_of_points_exchanged >= ?',
            'number_of_points_exchanged_end' => 'number_of_points_exchanged <= ?',
            'hyouji_flg' => 'hyouji_flg = ?',
        ] as $key => $condition) {
            if (isset($option->{$key})) {
                $conditions[] = $condition;
                $bindings[] = (int) $option->{$key};
            }
        }

        $core = 'SELECT * FROM sales_items WHERE '.implode(' AND ', $conditions);
        $rows = DB::select("{$core} ORDER BY cd DESC LIMIT {$limit} OFFSET {$offset}", $bindings);
        if (! $rows) {
            return null;
        }

        $res = new stdClass;
        $res->count = (int) DB::select('SELECT COUNT(*) FROM ('.$core.') AS FOO', $bindings)[0]->count;
        $res->data = $rows;
        $res->limit = $limit;
        $res->page = $page;

        return $res;
    }

    /**
     * POST /api/mobile_shop/passcode (src/MobileShop/Config.php): último passcode de la tienda en
     * contents_master. El legacy compara con `==` (por eso '04321' coincide con '4321'); se mantiene.
     */
    public function passcodeMatches(mixed $shopCd, mixed $passcode): bool
    {
        if (! $shopCd || ! $passcode || ! is_scalar($shopCd) || ! is_scalar($passcode)) {
            return false;
        }

        $stored = DB::table('contents_master')
            ->where('category', 'mobileshop')
            ->where('field', 'mobileshop_auth_passcode')
            ->whereRaw('int_data::text = ?', [(string) $shopCd])
            ->orderByDesc('update_date')
            ->value('text_data');

        return $stored && $passcode == $stored;
    }

    /** page y limit como src/Point.php y SalesItem::getList (por defecto 1 y 10) */
    private function paging(object $option): array
    {
        $int = fn (string $key) => isset($option->{$key}) && is_scalar($option->{$key}) ? (int) filter_var($option->{$key}) : 0;

        return [
            $int('page') > 1 ? $int('page') : 1,
            $int('limit') > 0 ? $int('limit') : self::DEFAULT_LIMIT,
        ];
    }

    /** ( col = ? OR col = ? … ) por cada palabra separada por espacio normal o ancho */
    private function orEquals(string $column, string $text, array &$bindings): string
    {
        $parts = [];
        foreach (explode(' ', str_replace('　', ' ', $text)) as $value) {
            $parts[] = "{$column} = ?";
            $bindings[] = $value;
        }

        return '('.implode(' OR ', $parts).')';
    }
}
