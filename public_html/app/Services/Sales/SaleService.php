<?php

namespace App\Services\Sales;

use App\Models\Customers\CustomerSub;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Models\Staff;
use App\Services\Customers\CustomerRegistrationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 売上データ (legacy: karute_new_register.php, karute_new_frm.php 履歴, karute_delete_list.php,
 * karute_delete_register.php). Una venta es un grupo de filas de `tb_customer_sub` con
 * `tb_search_cate_cd = -1` y el mismo `category_inner_group` (nº de cuenta del cliente: máximo + 1):
 *
 * | tb_search_form_cd | form_title   | rank            | float_data          | text_data | array_data | form_type | pic_flg  |
 * |------------------:|--------------|-----------------|---------------------|-----------|------------|-----------|----------|
 * | -1                | スタッフ情報 | comisión (円)   | % de comisión       | スタッフ名 | staff cd   | ''        |          |
 * | -2 (uno por menú) | メニュー情報 | null            | precio              | menú      | menú cd    | 技術/物販/その他 |   |
 * | -3 (por 割引)     | 割引情報     | null            | importe descontado  | 割引名    | 割引 cd    | 10％ / 500円 | buai_flg |
 * | -4                | 手動割引情報 | null            | importe descontado  | ''        | ''         | 10％ / 500円 | '2'    |
 * | -5                | 合計         | base comisión   | total cobrado       | ''        | ''         | ''        |          |
 *
 * En todas: `int_data` = tienda, `update_date` = 営業日 'Y/m/d' (el 登録日時 del formulario), `touroku_date` = epoch.
 */
class SaleService
{
    public const FORM_STAFF = -1;

    public const FORM_MENU = -2;

    public const FORM_DISCOUNT = -3;

    public const FORM_OTHER_DISCOUNT = -4;

    public const FORM_TOTAL = -5;

    /** form_title y form_type del legacy (datos de la BD compartida, no textos de pantalla) */
    private const TITLES = [
        self::FORM_STAFF => 'スタッフ情報',
        self::FORM_MENU => 'メニュー情報',
        self::FORM_DISCOUNT => '割引情報',
        self::FORM_OTHER_DISCOUNT => '手動割引情報',
        self::FORM_TOTAL => '合計',
    ];

    private const MENU_TYPES = ['1' => '技術', '2' => '物販'];

    private const OTHER_MENU_TYPE = 'その他';

    private const PERCENT = '％';

    private const YEN = '円';

    public function __construct(private CustomerRegistrationService $registration) {}

    /**
     * Importes de la venta como el legacy: el JS de karute_new_frm.php (totalPreview) calcula el total y la base de la
     * comisión en coma flotante y karute_new_register.php los trunca con (int); cada 割引 se guarda con
     * (int)(subtotal × valor / 100) o el valor en 円. La base de la comisión resta solo los 割引 con buai_flg = '1' y
     * la その他の割引. Aquí los precios y descuentos salen de la BD (el legacy confiaba en los campos ocultos).
     *
     * @param  Collection<int, ServiceMenu>  $menus
     * @param  Collection<int, Discount>  $discounts
     * @return array{sub_total:int, discounts:list<array{discount:Discount, amount:int, label:string}>, other:?array{amount:int, label:string}, total_float:float, total:int, commission_base:int}
     */
    public function calculate(Collection $menus, Collection $discounts, int $otherValue, string $otherType): array
    {
        $subTotal = (int) $menus->sum(fn (ServiceMenu $menu) => (int) $menu->kingaku);
        $discountSum = 0.0;  // v del JS
        $commissionDiscountSum = 0.0; // buai_v del JS
        $rows = [];

        foreach ($discounts as $discount) {
            $value = (int) $discount->value;
            $isPercent = (string) $discount->type === '1';
            $preview = $isPercent ? ($subTotal / 100) * $value : (float) $value;
            $discountSum += $preview;
            if ((string) $discount->buai_flg === '1') {
                $commissionDiscountSum += $preview;
            }
            $rows[] = [
                'discount' => $discount,
                'amount' => $isPercent ? (int) ($subTotal * $value / 100) : $value,
                'label' => $value.($isPercent ? self::PERCENT : self::YEN),
            ];
        }

        $other = null;
        if ($otherValue > 0) {
            $isPercent = $otherType === '1';
            $preview = $isPercent ? ($subTotal / 100) * $otherValue : (float) $otherValue;
            $discountSum += $preview;
            $commissionDiscountSum += $preview;
            $other = [
                'amount' => $isPercent ? (int) ($subTotal * $otherValue / 100) : $otherValue,
                'label' => $otherValue.($isPercent ? self::PERCENT : self::YEN),
            ];
        }

        $totalFloat = $subTotal - $discountSum;

        return [
            'sub_total' => $subTotal,
            'discounts' => $rows,
            'other' => $other,
            'total_float' => $totalFloat,
            'total' => (int) $totalFloat,
            'commission_base' => (int) ($subTotal - $commissionDiscountSum),
        ];
    }

    /**
     * Graba la venta (karute_new_register.php). `$menuCds`/`$discountCds` pueden repetirse (el JS permitía elegir
     * dos veces el mismo). Devuelve el nº de cuenta (`category_inner_group`), o null sin grabar si el total es negativo
     * (el JS del legacy lo impedía: 「合計の値がマイナスのため登録出来ません。」).
     *
     * @param  list<int|string>  $menuCds
     * @param  list<int|string>  $discountCds
     */
    public function store(int $customerCd, Staff $staff, int $shopCd, string $businessDay, array $menuCds, array $discountCds,
        int $otherValue, string $otherType, int $sessionShopCd): ?int
    {
        $menus = $this->pick(ServiceMenu::query()->whereIn('cd', array_map('intval', $menuCds))->get(), $menuCds);
        $discounts = $this->pick(Discount::query()->whereIn('cd', array_map('intval', $discountCds))->get(), $discountCds);
        $amounts = $this->calculate($menus, $discounts, $otherValue, $otherType);
        if ($amounts['total_float'] < 0) {
            return null;
        }

        $commissionRate = (float) $staff->kingaku_buai;
        $commissionValue = (int) ($amounts['commission_base'] * $commissionRate / 100);

        return DB::transaction(function () use ($customerCd, $staff, $shopCd, $businessDay, $menus, $amounts, $commissionRate, $commissionValue, $sessionShopCd) {
            DB::statement('LOCK TABLE hatuban, tb_customer_sub IN SHARE ROW EXCLUSIVE MODE');

            $groupNo = (int) DB::table('tb_customer_sub')->where('tb_customer_cd', $customerCd)
                ->where('tb_search_cate_cd', CustomerSub::SALE_CATEGORY)
                ->orderByRaw('category_inner_group::INT DESC')->value('category_inner_group') + 1;

            // createHatsuban($_SESSION['shop_cd']) y luego +1 por fila
            $cd = $this->registration->uniqueCd($sessionShopCd);
            $common = [
                'tb_customer_cd' => $customerCd,
                'tb_search_cate_cd' => CustomerSub::SALE_CATEGORY,
                'disp_flg' => '1',
                'category_inner_group' => (string) $groupNo,
                'touroku_date' => (string) time(),
                'update_date' => $businessDay,
                'int_data' => $shopCd,
            ];
            // Closure con $cd por referencia (una arrow fn copiaría $cd y todas las filas tendrían el mismo)
            $row = function (int $form, array $values) use (&$cd, $common): array {
                return $common + $values + ['cd' => $cd++, 'tb_search_form_cd' => $form, 'form_title' => self::TITLES[$form]];
            };

            $rows = [$row(self::FORM_STAFF, [
                'rank' => $commissionValue, 'float_data' => $commissionRate, 'text_data' => (string) $staff->name,
                'array_data' => (string) $staff->cd, 'form_type' => '',
            ])];

            foreach ($menus as $menu) {
                $rows[] = $row(self::FORM_MENU, [
                    'rank' => null, 'float_data' => (float) $menu->kingaku, 'text_data' => (string) $menu->name,
                    'array_data' => (string) $menu->cd, 'form_type' => self::MENU_TYPES[(string) $menu->type] ?? self::OTHER_MENU_TYPE,
                ]);
            }

            foreach ($amounts['discounts'] as $discount) {
                $rows[] = $row(self::FORM_DISCOUNT, [
                    'rank' => null, 'float_data' => $discount['amount'], 'text_data' => (string) $discount['discount']->name,
                    'array_data' => (string) $discount['discount']->cd, 'form_type' => $discount['label'],
                    'pic_flg' => (string) $discount['discount']->buai_flg,
                ]);
            }

            if ($amounts['other']) {
                $rows[] = $row(self::FORM_OTHER_DISCOUNT, [
                    'rank' => null, 'float_data' => $amounts['other']['amount'], 'text_data' => '', 'array_data' => '',
                    'form_type' => $amounts['other']['label'], 'pic_flg' => '2',
                ]);
            }

            $rows[] = $row(self::FORM_TOTAL, [
                'rank' => $amounts['commission_base'], 'float_data' => $amounts['total'], 'text_data' => '',
                'array_data' => '', 'form_type' => '',
            ]);

            // Inserción directa: update_date es el 営業日 en texto, no el epoch que pondría LegacyTimestamps
            foreach ($rows as $values) {
                DB::table('tb_customer_sub')->insert($values);
            }

            return $groupNo;
        });
    }

    /**
     * 履歴 de karute_new_frm.php: ventas (fila 合計) de la tienda en el 営業日 actual (antes de las 5:00 cuenta el día
     * anterior). El legacy no miraba disp_flg y mostraba también las borradas: aquí se excluyen.
     */
    public function businessDayHistory(int $shopCd, ?Carbon $now = null): Collection
    {
        $now ??= Carbon::now('Asia/Tokyo');
        $businessDay = ($now->hour < 5 ? $now->copy()->subDay() : $now)->format('Y/m/d');

        return DB::table('tb_customer_sub as tb1')
            ->join('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
            ->where('tb1.tb_search_form_cd', self::FORM_TOTAL)
            ->where('tb1.int_data', $shopCd)
            ->where('tb1.update_date', $businessDay)
            ->whereRaw("COALESCE(tb1.disp_flg, '') <> '9'")
            ->orderByDesc('tb1.touroku_date')->orderByDesc('tb1.cd')
            ->get(['tb1.*', 'tb2.id', 'tb2.name']);
    }

    /**
     * Lista de karute_delete_list.php: ventas (fila 合計) no borradas de la tienda, con la スタッフ de su fila -1,
     * filtradas por 登録日時 (touroku_date, días completos) y スタッフ.
     */
    public function search(int $shopCd, ?string $date1, ?string $date2, ?string $staffCd, int $page, int $kensu): LengthAwarePaginator
    {
        $query = DB::table('tb_customer_sub as tb1')
            ->join('tb_customer_sub as tb2', function ($join) {
                $join->on('tb1.tb_customer_cd', '=', 'tb2.tb_customer_cd')
                    ->on('tb1.tb_search_cate_cd', '=', 'tb2.tb_search_cate_cd')
                    ->on('tb1.category_inner_group', '=', 'tb2.category_inner_group')
                    ->where('tb2.tb_search_form_cd', self::FORM_STAFF);
            })
            ->join('tb_customer as tb3', 'tb1.tb_customer_cd', '=', 'tb3.cd')
            ->where('tb1.disp_flg', '!=', '9')
            ->where('tb1.tb_search_form_cd', self::FORM_TOTAL)
            ->where('tb1.int_data', $shopCd)
            ->when(filled($staffCd), fn ($q) => $q->where('tb2.array_data', (string) $staffCd))
            ->when($this->dayStart($date1), fn ($q, $from) => $q->whereRaw('tb1.touroku_date::integer >= ?', [$from]))
            ->when($this->dayStart($date2), fn ($q, $to) => $q->whereRaw('tb1.touroku_date::integer < ?', [$to + 86400]))
            ->orderByDesc('tb1.touroku_date')->orderByDesc('tb1.cd')
            ->select(['tb1.*', 'tb2.array_data as staff_cd', 'tb2.text_data as staff_name', 'tb3.id', 'tb3.name']);

        $total = (clone $query)->reorder()->count();
        $maxPage = $kensu ? (int) ceil($total / $kensu) : 0;
        $page = max(1, min($page, max(1, $maxPage)));

        return new LengthAwarePaginator($query->forPage($page, $kensu)->get(), $total, $kensu, $page);
    }

    /** karute_delete_register.php: todas las filas de la cuenta pasan a disp_flg 9 (update_date = epoch) */
    public function delete(int $customerCd, string $group): int
    {
        return DB::table('tb_customer_sub')
            ->where('tb_search_cate_cd', CustomerSub::SALE_CATEGORY)
            ->where('tb_customer_cd', $customerCd)
            ->where('category_inner_group', $group)
            ->update(['disp_flg' => '9', 'update_date' => (string) time()]);
    }

    /** strtotime('Y/m/d') del legacy en Asia/Tokyo; null si está vacío o no es una fecha */
    private function dayStart(?string $date): ?int
    {
        if (blank($date)) {
            return null;
        }
        try {
            return Carbon::parse(str_replace('/', '-', trim($date)), 'Asia/Tokyo')->startOfDay()->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Repite los modelos según los cd pedidos (en el orden pedido), descartando los que no existen */
    private function pick(Collection $models, array $cds): Collection
    {
        $byCd = $models->keyBy('cd');

        return collect($cds)->map(fn ($cd) => $byCd->get((int) $cd))->filter()->values();
    }
}
