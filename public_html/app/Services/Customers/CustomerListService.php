<?php

namespace App\Services\Customers;

use App\Models\AdminMaster;
use App\Models\Staff;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 顧客一覧・検索 (legacy `system/category1/customer/customer_list.php`).
 *
 * La búsqueda se guarda en sesión con un `search_uniqid` por ventana, como el legacy
 * (`$_SESSION['wherestr_<uniqid>']` + `search_display_<uniqid>`), pero con
 * CustomerSearch::describe() en lugar del SQL.
 */
class CustomerListService
{
    /** Clave de sesión de la búsqueda guardada: customer_search.<uniqid> */
    public const SESSION = 'customer_search.';

    /**
     * Clave que lee 新規メール配信 de S3 (MailAudience) para 「この条件で配信する」: en el legacy ambas
     * pantallas compartían `$_SESSION['wherestr_<uniqid>']`, así que se guarda también ahí.
     */
    public const MAIL_AUDIENCE_SESSION = 'promocion.audience.';

    public function __construct(private readonly CustomerSearch $search) {}

    /** Buscador con el rango del personal (no-master: solo su tienda) y la カスタマイズ検索 de category1 */
    public function searchFor(Staff $staff): CustomerSearch
    {
        return $this->search->forCategory('category1')
            ->forStaff($staff->isMaster(), $staff->isMaster() ? null : (int) $staff->shop_cd);
    }

    /**
     * Guarda la búsqueda con un uniqid nuevo y borra la anterior (search_set2.php con act=up).
     * Sin `se_flg = 1` el legacy dejaba "todos los socios activos": se guarda la búsqueda vacía.
     */
    public function store(array $params, Staff $staff, ?string $previousUniqid = null): string
    {
        $this->forget($previousUniqid);

        $description = $this->searchFor($staff)->describe(($params['se_flg'] ?? null) == '1' ? $params : []);
        $uniqid = Str::random(16);
        session([
            self::SESSION.$uniqid => $description,
            self::MAIL_AUDIENCE_SESSION.$uniqid => $description,
        ]);

        return $uniqid;
    }

    public function description(?string $uniqid): ?array
    {
        if ($uniqid === null || $uniqid === '') {
            return null;
        }
        $description = session(self::SESSION.$uniqid);

        return is_array($description) ? $description : null;
    }

    public function forget(?string $uniqid): void
    {
        if ($uniqid) {
            session()->forget([self::SESSION.$uniqid, self::MAIL_AUDIENCE_SESSION.$uniqid]);
        }
    }

    /**
     * Consulta de clientes de una búsqueda guardada; si no hay, con los filtros dados (p. ej. los de la URL).
     * El rango de tienda es siempre el del personal actual, no el guardado.
     */
    public function query(Staff $staff, ?array $description, array $params = []): Builder
    {
        $search = $this->searchFor($staff);
        if ($description !== null) {
            $search = $search->forCategory((string) ($description['category'] ?? 'category1'));
            $params = (array) ($description['params'] ?? []);
        }

        return $search->query($params);
    }

    /**
     * 件数 y 内訳 por operador (mailmaga_email_type), como customer_list.php.
     *
     * @return array{total: int, pc: int, docomo: int, au: int, softbank: int, unknown: int}
     */
    public function counts(Builder $query): array
    {
        $row = (clone $query)->selectRaw("count(*) AS total,
            sum(case when mailmaga_email_type = 'パソコン' then 1 else 0 end) AS pc,
            sum(case when mailmaga_email_type = 'DoCoMo' then 1 else 0 end) AS docomo,
            sum(case when mailmaga_email_type = 'AU' then 1 else 0 end) AS au,
            sum(case when mailmaga_email_type = 'Softbank' then 1 else 0 end) AS softbank")->first();

        $counts = [
            'total' => (int) $row->total,
            'pc' => (int) $row->pc,
            'docomo' => (int) $row->docomo,
            'au' => (int) $row->au,
            'softbank' => (int) $row->softbank,
        ];
        $counts['unknown'] = $counts['total'] - $counts['pc'] - $counts['docomo'] - $counts['au'] - $counts['softbank'];

        return $counts;
    }

    /**
     * Clientes de la página (ORDER BY cd DESC) con 来店回数: tb_raiten_rireki no borrados y, si no es
     * master, solo de la tienda del personal.
     *
     * @return list<array{cd:int, id:?string, kanri_id:?string, name:?string, felica_no:?string, raiten_count:int}>
     */
    public function page(Builder $query, Staff $staff, int $page, int $kensu, bool $withRaitenCount): array
    {
        $query = (clone $query)->select(['tb_customer.cd', 'tb_customer.id', 'tb_customer.kanri_id', 'tb_customer.name', 'tb_customer.felica_no']);
        if ($withRaitenCount) {
            $query->selectSub(function (Builder $sub) use ($staff): void {
                $sub->from('tb_raiten_rireki as r')->selectRaw('count(*)')
                    ->whereColumn('r.user_cd', 'tb_customer.cd')
                    ->whereRaw("coalesce(r.disp_flg, '') <> '9'");
                if (! $staff->isMaster()) {
                    $sub->where('r.shop_cd', '=', (int) $staff->shop_cd);
                }
            }, 'raiten_count');
        }

        return $query->orderByDesc('tb_customer.cd')
            ->offset(max(0, ($page - 1) * $kensu))->limit($kensu)
            ->get()
            ->map(fn ($r) => [
                'cd' => (int) $r->cd,
                'id' => $r->id,
                'kanri_id' => $r->kanri_id,
                'name' => $r->name,
                'felica_no' => $r->felica_no,
                'raiten_count' => (int) ($r->raiten_count ?? 0),
            ])->all();
    }

    /** スマトピ: id del dominio (Smatopi::getDomainSmatopiId) si admin_master.smatopi_flg = 1 */
    public function domainSmatopiId(): ?string
    {
        $admin = AdminMaster::current();
        if (! $admin || (int) $admin->smatopi_flg !== 1) {
            return null;
        }
        $id = (string) ($admin->domain_smatopi_id ?? '');

        // TODO(S3): el legacy, sin domain_smatopi_id, lo pedía a b.g-api.net/api/smatopi/id y lo guardaba
        return $id !== '' ? $id : null;
    }

    /** Clientes del filtro con LINE vinculado y no bloqueado (Smatopi::adjustCustomerSqlForLineMessage) */
    public function lineLinkedCount(Builder $query): int
    {
        return (clone $query)
            ->whereRaw("COALESCE(tb_customer.line_id, '') <> ''")
            ->whereRaw('COALESCE(tb_customer.line_blocked_flg, 0) = 0')
            ->count();
    }

    /** tb_base_system_child 「来店履歴管理」 activo */
    public function raitenHistoryEnabled(): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('system_flg', '1')->exists();
    }

    /** tb_base_system_grandchild 「Aprint用CSV出力」 activo (DM用CSV y LINE配信) */
    public function aprintCsvEnabled(): bool
    {
        return DB::table('tb_base_system_grandchild')->where('system_name', 'Aprint用CSV出力')->where('system_flg', '1')->exists();
    }
}
