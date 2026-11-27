<?php

namespace App\Services\Terminales;

use App\Models\Customers\Customer;
use App\Models\Shop;
use App\Models\Terminales\FelicaCard;
use App\Services\Customers\CustomerFelicaService;
use App\Support\LegacyText;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ICカード管理 / カード管理 (system/category7/felica/card_list.php, ajax_search_kaiin.php y el
 * borrado de tarjetas de ajax_del.php).
 *
 * `tb_customer.felica_no` se cambia siempre con CustomerFelicaService (S2): attach() / detach().
 * Los UPDATE de `tb_customer_card` van por el query builder base, sin tocar `update_date`,
 * como el SQL del legacy.
 */
class FelicaCardService
{
    /** Clave de sesión de las condiciones de búsqueda ($_SESSION['card'] del legacy) */
    public const SESSION_KEY = 'terminales.felica_card_search';

    /** Campos del buscador que se recuerdan en la sesión */
    public const SEARCH_KEYS = ['search_id', 'search_name', 'search_name_kana', 'sch_joint_flg', 'andor'];

    /** Máximo de socios en el buscador del colorbox (LIMIT 200 de ajax_search_kaiin.php) */
    public const MEMBER_LIMIT = 200;

    public function __construct(
        private CustomerFelicaService $customerFelica,
        private LegacyListPaginator $paginator,
    ) {}

    /** Tienda de la sesión ($_SESSION['shop_cd']) */
    public function sessionShop(): ?int
    {
        $cd = session('shop_cd');

        return filled($cd) ? (int) $cd : null;
    }

    /**
     * Condiciones de búsqueda: «検索条件クリア» (sesClear=card) las borra; si no, lo que llegue en la
     * petición se guarda en la sesión (el legacy guardaba todo $_REQUEST en $_SESSION['card']).
     */
    public function conditions(Request $request): array
    {
        if ($request->input('sesClear') === 'card') {
            session()->forget(self::SESSION_KEY);

            return [];
        }

        $saved = (array) session(self::SESSION_KEY, []);
        foreach (self::SEARCH_KEYS as $key) {
            if ($request->has($key)) {
                $saved[$key] = LegacyText::trim((string) $request->input($key, ''));
            }
        }
        session([self::SESSION_KEY => $saved]);

        return $saved;
    }

    /**
     * Listado de カード管理: flg <> '9', tienda (si la sesión no es la tienda 1), solo los tipos
     * VISIBLE_TYPES y las condiciones del socio; orden update_date desc.
     */
    public function search(array $conditions, Request $request, string $path)
    {
        $query = $this->visible()
            ->whereIn('typ_exp', FelicaCard::VISIBLE_TYPES)
            ->select('tb_customer_card.*')
            ->selectSub(
                DB::table('tb_shop')->select('kaishamei')
                    ->whereColumn('tb_shop.cd', 'tb_customer_card.tb_shop_cd')
                    ->where('tb_shop.disp_flg', '<>', '9')
                    ->limit(1),
                'kaishamei'
            )
            ->with('customer:cd,id,name')
            ->orderByDesc('update_date')
            ->orderByDesc('cd');

        $customerFilters = $this->customerFilters($conditions);
        if ($customerFilters !== []) {
            $or = ($conditions['andor'] ?? '') === '1';
            $query->whereIn('tb_customer_cd', function (QueryBuilder $sub) use ($customerFilters, $or) {
                $sub->select('cd')->from('tb_customer')->where(function (QueryBuilder $where) use ($customerFilters, $or) {
                    foreach ($customerFilters as $filter) {
                        $or ? $where->orWhere($filter) : $where->where($filter);
                    }
                });
            });
        }

        return $this->paginator->paginate($query, $request, $path);
    }

    /**
     * Condiciones sobre tb_customer, como el bucle de $fldType del legacy:
     * 会員ID / 会員名 / かな con LIKE si vienen; 会員情報入力 (sch_joint_flg) sobre `name`:
     * 未 (y por defecto) = sin nombre, 済 = con nombre, 全 = sin condición.
     *
     * @return list<\Closure>
     */
    private function customerFilters(array $conditions): array
    {
        $filters = [];
        foreach (['search_id' => 'id', 'search_name' => 'name', 'search_name_kana' => 'name_kana'] as $key => $column) {
            $value = (string) ($conditions[$key] ?? '');
            if ($value !== '') {
                $filters[] = fn (QueryBuilder $q) => $q->whereRaw("{$column}::text like ?", ['%' . $value . '%']);
            }
        }

        $joint = (string) ($conditions['sch_joint_flg'] ?? '');
        if ($joint === '済') {
            $filters[] = fn (QueryBuilder $q) => $q->whereRaw("COALESCE(name, '') <> ''");
        } elseif ($joint !== '全') {
            $filters[] = fn (QueryBuilder $q) => $q->whereRaw("COALESCE(name, '') = ''");
        }

        return $filters;
    }

    /** ¿Puede elegir la tienda de cada tarjeta? ($_SESSION['rank'] == "master") */
    public function canChooseShop(): bool
    {
        return (bool) auth('admin')->user()?->isMaster();
    }

    /** Tiendas del <select> de cada fila (todas, o solo la de la sesión si no es la 1) */
    public function shopOptions(): Collection
    {
        $shopCd = $this->sessionShop();

        return Shop::query()
            ->where('disp_flg', '<>', '9')
            ->when($shopCd && $shopCd !== 1, fn (Builder $q) => $q->where('cd', $shopCd))
            ->orderBy('cd')
            ->get(['cd', 'kaishamei']);
    }

    /**
     * «更新する»: por cada fila con tienda y cd, cambia la tienda y, si se eligió socio, se la asigna.
     *
     * @param  list<array{cd:int, shop_cd:int, kaiin_cd:?int}>  $rows
     * @return list<string> mensajes de las filas que no se pudieron asignar
     */
    public function updateRows(array $rows): array
    {
        $errors = [];
        // Solo tiendas del <select> (el legacy aceptaba cualquier tb_shop_cd del formulario)
        $allowedShops = $this->shopOptions()->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
        foreach ($rows as $row) {
            $card = $this->visible()->find($row['cd']);
            if (! $card || ! in_array($row['shop_cd'], $allowedShops, true)) {
                continue;
            }

            try {
                DB::transaction(function () use ($card, $row) {
                    $this->cardQuery($card)->update(['tb_shop_cd' => $row['shop_cd']]);
                    $card->tb_shop_cd = $row['shop_cd'];

                    if ($row['kaiin_cd']) {
                        $customer = Customer::withDeleted()->find($row['kaiin_cd']);
                        if ($customer) {
                            $this->assign($card, $customer);
                        }
                    }
                });
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Asigna la tarjeta al socio. Como el legacy: quita el IDm a quien lo tuviera
     * («重複元データ消す») y se lo pone al socio nuevo; además `tb_customer_card.tb_customer_cd`.
     * Si el socio tenía otra tarjeta, se le libera antes (attach() de S2 exige que no tenga).
     *
     * @throws DomainException si S2 rechaza la asignación (tarjeta marcada en tb_user_card para otro)
     */
    public function assign(FelicaCard $card, Customer $customer): void
    {
        DB::transaction(function () use ($card, $customer) {
            $idm = (string) $card->cid;

            if ($idm !== '') {
                $holders = Customer::withDeleted()->where('felica_no', $idm)->where('cd', '<>', $customer->cd)->get();
                foreach ($holders as $holder) {
                    $this->customerFelica->detach($holder);
                }

                $current = (string) Customer::withDeleted()->whereKey($customer->cd)->value('felica_no');
                if ($current !== $idm) {
                    if ($current !== '') {
                        $this->customerFelica->detach($customer);
                    }
                    $this->customerFelica->attach($customer, $idm);
                }
            }

            $this->cardQuery($card)->update(['tb_customer_cd' => $customer->cd]);
            $card->tb_customer_cd = $customer->cd;
        });
    }

    /**
     * Quita la tarjeta a su socio: `tb_customer_card.tb_customer_cd = null` y detach() del socio
     * (vacía `felica_no`, como UID関連付け解除). Lo llama la ficha de cliente de S2.
     *
     * @return bool false si la tarjeta no existe
     */
    public function unassign(FelicaCard|int $card): bool
    {
        $card = $card instanceof FelicaCard ? $card : FelicaCard::find($card);
        if (! $card) {
            return false;
        }

        DB::transaction(function () use ($card) {
            $customer = $card->tb_customer_cd ? Customer::withDeleted()->find($card->tb_customer_cd) : null;
            $this->cardQuery($card)->update(['tb_customer_cd' => null]);
            if ($customer) {
                $this->customerFelica->detach($customer);
            }
        });
        $card->tb_customer_cd = null;

        return true;
    }

    /**
     * Borra la tarjeta (físico, como ajax_del.php: «登録時ややこしいので») y vacía el felica_no
     * de su socio con detach(). false si no existe o no es de la tienda de la sesión.
     */
    public function delete(int $cd): bool
    {
        $card = $this->visible()->find($cd);
        if (! $card) {
            return false;
        }

        DB::transaction(function () use ($card) {
            $customer = $card->tb_customer_cd ? Customer::withDeleted()->find($card->tb_customer_cd) : null;
            if ($customer) {
                $this->customerFelica->detach($customer);
            }
            $this->cardQuery($card)->delete();
        });

        return true;
    }

    /**
     * Buscador de socios del colorbox (ajax_search_kaiin.php): socios 本登録 de la tienda, con
     * nombre e ID parciales y, si se pide, sin tarjeta. null si no llega tienda (el legacy no buscaba).
     */
    public function searchMembers(?int $shopCd, string $name, string $id, bool $withoutCard): ?Collection
    {
        if (! $shopCd) {
            return null;
        }

        return Customer::query()
            ->members()
            ->where('tb_shop_cd', $shopCd)
            ->where('name', 'like', '%' . $name . '%')
            ->where('id', 'like', '%' . $id . '%')
            ->when($withoutCard, fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('felica_no', '')->orWhereNull('felica_no')))
            ->select('tb_customer.*')
            ->selectSub(
                DB::table('tb_shop')->select('kaishamei')
                    ->whereColumn('tb_shop.cd', 'tb_customer.tb_shop_cd')
                    ->where('tb_shop.disp_flg', '<>', '9')
                    ->limit(1),
                'kaishamei'
            )
            ->orderBy('cd')
            ->limit(self::MEMBER_LIMIT)
            ->get();
    }

    /** Tarjetas que ve la sesión: flg <> '9' y, salvo la tienda 1, solo las de su tienda */
    private function visible(): Builder
    {
        $shopCd = $this->sessionShop();

        return FelicaCard::query()
            ->where('tb_customer_card.flg', '<>', FelicaCard::FLG_HIDDEN)
            ->when($shopCd && $shopCd !== 1, fn (Builder $q) => $q->where('tb_customer_card.tb_shop_cd', $shopCd));
    }

    /** UPDATE/DELETE de una tarjeta sin tocar update_date (como el SQL del legacy) */
    private function cardQuery(FelicaCard $card): QueryBuilder
    {
        return DB::table('tb_customer_card')->where('cd', $card->cd);
    }
}
