<?php

namespace App\Services\Promotions;

use App\Services\Customers\CustomerSearch;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Destinatarios elegidos en 発行条件選択 (legacy `$_SESSION['wherestr_<uniqid>']` y
 * `$_SESSION['search_display_<uniqid>']`, armados por system/inc/search_set2.php).
 *
 * En sesión se guarda la descripción de CustomerSearch (sin SQL); el SQL de `振分配信` se genera
 * en el servidor al registrar el envío (`whereSql`), como `WHERE cd IN (<consulta>)` sobre tb_customer,
 * que es lo que leen MailSender y el servidor de push.
 */
class MailAudience
{
    /**
     * Clave única de la búsqueda guardada, dueña S2 (acuerdo S0/S2/S3): `customer_search.<uniqid>` con la
     * forma de CustomerSearch::describe(). La edición de un programado guarda ahí `['raw' => …, 'display' => …]`
     * con un uniqid propio (no lo comparte con 顧客検索).
     */
    public const SESSION = 'customer_search.';

    public function __construct(private readonly CustomerSearch $search) {}

    /**
     * Guarda la búsqueda con un uniqid nuevo (varias pantallas abiertas a la vez, como el legacy).
     * Sin `se_flg = 1` el legacy dejaba "todos los socios activos": se guarda la búsqueda vacía.
     */
    public function store(array $params, bool $isMaster, ?int $shopCd, string $category = 'category1'): string
    {
        $search = $this->search->forCategory($category)->forStaff($isMaster, $shopCd);
        $description = $search->describe(($params['se_flg'] ?? null) == '1' ? $params : []);

        $uniqid = Str::random(16);
        session([self::SESSION.$uniqid => $description]);

        return $uniqid;
    }

    public function has(?string $uniqid): bool
    {
        return $uniqid !== null && $uniqid !== '' && is_array(session(self::SESSION.$uniqid));
    }

    public function forget(?string $uniqid): void
    {
        if ($uniqid) {
            session()->forget(self::SESSION.$uniqid);
        }
    }

    public function description(string $uniqid): array
    {
        return (array) session(self::SESSION.$uniqid, []);
    }

    /** Parámetros del buscador (para volver a mostrar el formulario con «抽出しなおす») */
    public function params(string $uniqid): array
    {
        return (array) ($this->description($uniqid)['params'] ?? []);
    }

    /** search_display del legacy: condiciones unidas con `<>` */
    public function display(string $uniqid): string
    {
        return implode('<>', (array) ($this->description($uniqid)['display'] ?? []));
    }

    /**
     * Recordatorio (reminder_new_confirm.php): el `振分配信` es una SELECT completa sobre tb_yoyaku, no un WHERE sobre
     * tb_customer. Las pantallas no pueden contarla ni sacar una muestra (en el legacy esas consultas fallaban y
     * mostraban 0 y el ejemplo fijo); la ejecuta MailSender al enviar.
     */
    public function isSelect(string $uniqid): bool
    {
        return (bool) preg_match('/^\s*SELECT\b/i', (string) ($this->description($uniqid)['raw'] ?? ''));
    }

    /** Clientes del filtro sin los 例外顧客 */
    public function query(string $uniqid, array $excludedCds = []): Builder
    {
        $description = $this->description($uniqid);
        $query = isset($description['raw'])
            // {POS_RESULT}: búsqueda en el POS (fuera de alcance) = ningún cliente
            ? DB::table('tb_customer')->whereRaw(str_replace('{POS_RESULT}', '-1', (string) preg_replace('/^\s*WHERE\s+/i', '', (string) $description['raw'])))
            : $this->search->queryFromDescription($description);
        if ($excludedCds !== []) {
            $query->whereNotIn('cd', $excludedCds);
        }

        return $query;
    }

    /**
     * `振分配信`: WHERE sobre tb_customer con los valores ya escapados por PDO y, como mail_send_frm.php,
     * los 例外顧客 añadidos al final como ` AND cd NOT IN (…)` (la edición los vuelve a quitar con ese texto).
     */
    public function whereSql(string $uniqid, array $excludedCds = []): string
    {
        $description = $this->description($uniqid);
        $sql = isset($description['raw'])
            ? (string) $description['raw']
            : 'WHERE cd IN ('.$this->query($uniqid)->select('cd')->toRawSql().')';

        return $excludedCds === [] ? $sql : $sql.' AND cd NOT IN ('.implode(',', array_map('intval', $excludedCds)).')';
    }

    /**
     * Edición de un envío programado (mail_future_frm.php): la búsqueda es el `振分配信` guardado sin los
     * 例外顧客. Es SQL que generó el propio sistema (nunca texto del usuario), como hacía el legacy.
     */
    public function storeRaw(string $whereSql, ?string $excludedCsv, string $display): string
    {
        if ((string) $excludedCsv !== '') {
            $whereSql = str_ireplace(' AND cd NOT IN ('.$excludedCsv.')', '', $whereSql);
        }
        $uniqid = Str::random(16);
        session([self::SESSION.$uniqid => ['raw' => $whereSql, 'display' => $display === '' ? [] : explode('<>', $display)]]);

        return $uniqid;
    }

    /**
     * Conteo por tipo de dirección (mailmaga_email_type), como el resultado de 発行条件選択.
     *
     * @return array{total: int, pc: int, docomo: int, au: int, softbank: int, other: int}
     */
    public function counts(string $uniqid, array $excludedCds = []): array
    {
        if ($this->isSelect($uniqid)) {
            return ['total' => 0, 'pc' => 0, 'docomo' => 0, 'au' => 0, 'softbank' => 0, 'other' => 0];
        }
        $row = $this->query($uniqid, $excludedCds)->selectRaw("count(*) AS total,
            sum(case when mailmaga_email_type = 'パソコン' then 1 else 0 end) AS pc,
            sum(case when mailmaga_email_type = 'DoCoMo' then 1 else 0 end) AS docomo,
            sum(case when mailmaga_email_type = 'AU' then 1 else 0 end) AS au,
            sum(case when mailmaga_email_type = 'Softbank' then 1 else 0 end) AS softbank")->first();

        $counts = ['total' => (int) $row->total, 'pc' => (int) $row->pc, 'docomo' => (int) $row->docomo, 'au' => (int) $row->au, 'softbank' => (int) $row->softbank];
        $counts['other'] = $counts['total'] - $counts['pc'] - $counts['docomo'] - $counts['au'] - $counts['softbank'];

        return $counts;
    }

    /** Un cliente al azar del filtro para la vista previa; null si no hay ninguno */
    public function sample(string $uniqid, array $excludedCds = []): ?array
    {
        if ($this->isSelect($uniqid)) {
            return null;
        }
        $row = $this->query($uniqid, $excludedCds)->inRandomOrder()->first();

        return $row ? (array) $row : null;
    }

    /** `1,2,x` → [1, 2] */
    public static function cds(?string $csv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $csv))));
    }

    /** Lista paginada de clientes (popup de 例外顧客 y 会員一覧) */
    public function page(string $uniqid, int $page, int $perPage = 15): array
    {
        $query = $this->query($uniqid)->orderByDesc('touroku_date')->orderBy('cd');
        $total = (clone $query)->count();
        $maxPage = (int) ceil($total / max(1, $perPage));
        $page = max(1, min($page, max(1, $maxPage)));

        return [
            'rows' => $query->forPage($page, $perPage)->get(),
            'page' => $page,
            'maxPage' => $maxPage,
            'total' => $total,
        ];
    }

    /** Clientes por cd (resumen de 例外顧客) */
    public function customers(array $cds)
    {
        return $cds === [] ? collect() : DB::table('tb_customer')->whereIn('cd', $cds)->get(['cd', 'id', 'name', 'email1']);
    }
}
