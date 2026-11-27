<?php

namespace App\Services\Ventas;

use App\Models\Customers\Customer;
use App\Models\Sales\Discount;
use App\Models\Sales\Sale;
use App\Models\Sales\ServiceMenu;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 売上データ (category1/karute_menu): alta de una cuenta (karute_new_register.php), su listado y borrado
 * (karute_delete_list.php, karute_delete_register.php), el 履歴 del formulario y el buscador de socios.
 */
class SaleService
{
    /**
     * Totales de la cuenta como totalPreview() del JS de karute_new_frm.php más el (int) de karute_new_register.php.
     *
     * @param  list<int>  $menuPrices  precio de cada menú elegido (con repeticiones)
     * @param  list<array{value: int, type: string, buai_flg: string}>  $discounts
     * @return array{subtotal: int, total: int, commission_base: int, discounts: list<int>, manual: int}
     */
    public function totals(array $menuPrices, array $discounts, int $manualValue, string $manualType): array
    {
        $subtotal = array_sum($menuPrices);
        $discountSum = 0.0;  // v del JS
        $buaiSum = 0.0;      // buai_v del JS (descuentos que se restan de la base de la comisión)
        $lines = [];

        foreach ($discounts as $discount) {
            // El JS acumula el % sin redondear; la fila guarda (int)(subTotal × valor / 100)
            $amount = $discount['type'] === Discount::TYPE_PERCENT ? $subtotal / 100 * $discount['value'] : (float) $discount['value'];
            $discountSum += $amount;
            if ($discount['buai_flg'] === Discount::BUAI_INCLUDED) {
                $buaiSum += $amount;
            }
            $lines[] = $discount['type'] === Discount::TYPE_PERCENT ? (int) ($subtotal * $discount['value'] / 100) : $discount['value'];
        }

        $manual = 0;
        if ($manualValue > 0) {
            $amount = $manualType === Discount::TYPE_PERCENT ? $subtotal / 100 * $manualValue : (float) $manualValue;
            $discountSum += $amount;
            $buaiSum += $amount; // その他の割引 siempre se resta de la base (2012.06.19)
            $manual = $manualType === Discount::TYPE_PERCENT ? (int) ($subtotal * $manualValue / 100) : $manualValue;
        }

        return [
            'subtotal' => $subtotal,
            'total' => (int) ($subtotal - $discountSum),
            'commission_base' => (int) ($subtotal - $buaiSum),
            'discounts' => $lines,
            'manual' => $manual,
        ];
    }

    /** Comisión de la スタッフ: (int)(base × tb_staff.kingaku_buai / 100) */
    public static function commission(int $base, float $rate): int
    {
        return (int) ($base * $rate / 100);
    }

    /**
     * Guarda la cuenta (karute_new_register.php). Menús y descuentos se leen de la BD por su cd (el legacy
     * confiaba en los hidden del formulario); el % de comisión, de tb_staff.
     *
     * @param  array{shop: int, customer_cd: int, staff_cd: int, business_day: string, menu_cds: list<int>,
     *               discount_cds: list<int>, manual_value: int, manual_type: string}  $data
     * @return array{group: int, total: int, commission_base: int, commission: int}
     */
    public function store(array $data, int $sessionShop): array
    {
        $shop = $data['shop'];
        $staff = Staff::query()->findOrFail($data['staff_cd']);
        $menus = $this->pick(ServiceMenu::class, $shop, $data['menu_cds']);
        $discounts = $this->pick(Discount::class, $shop, $data['discount_cds']);

        $totals = $this->totals(
            $menus->map(fn (ServiceMenu $m) => (int) $m->kingaku)->all(),
            $discounts->map(fn (Discount $d) => ['value' => (int) $d->value, 'type' => (string) $d->type, 'buai_flg' => (string) $d->buai_flg])->all(),
            $data['manual_value'],
            $data['manual_type'],
        );
        if ($totals['total'] < 0) {
            // submitChk() del legacy (solo en el JS)
            throw new \DomainException('・合計の値がマイナスのため登録出来ません。');
        }
        $rate = (float) $staff->kingaku_buai;
        $commission = self::commission($totals['commission_base'], $rate);

        return DB::transaction(function () use ($data, $sessionShop, $shop, $staff, $menus, $discounts, $totals, $rate, $commission) {
            // Igual que el legacy: bloqueo de la tabla para que dos altas no tomen el mismo 請求番号
            DB::statement('LOCK TABLE tb_customer_sub IN SHARE ROW EXCLUSIVE MODE');

            $group = Sale::nextGroup($data['customer_cd']);
            $cd = self::createHatsuban($sessionShop);
            $common = [
                'tb_customer_cd' => $data['customer_cd'],
                'disp_flg' => '1',
                'category_inner_group' => (string) $group,
                'touroku_date' => (string) now()->timestamp,
                'update_date' => $data['business_day'],
                'int_data' => $shop,
            ];
            $rows = [];

            $rows[] = ['tb_search_form_cd' => Sale::FORM_STAFF, 'rank' => $commission, 'float_data' => $rate,
                'text_data' => (string) $staff->name, 'array_data' => (string) $staff->cd, 'form_type' => '', 'form_title' => 'スタッフ情報'];

            foreach ($menus as $menu) {
                $rows[] = ['tb_search_form_cd' => Sale::FORM_MENU, 'rank' => null, 'float_data' => (int) $menu->kingaku,
                    'text_data' => (string) $menu->name, 'array_data' => (string) $menu->cd,
                    'form_type' => Sale::MENU_TYPES[(string) $menu->type] ?? Sale::MENU_TYPE_OTHER, 'form_title' => 'メニュー情報'];
            }

            foreach ($discounts->values() as $i => $discount) {
                $rows[] = ['tb_search_form_cd' => Sale::FORM_DISCOUNT, 'rank' => null, 'float_data' => $totals['discounts'][$i],
                    'text_data' => (string) $discount->name, 'array_data' => (string) $discount->cd,
                    'form_type' => self::discountLabel((int) $discount->value, (string) $discount->type),
                    'form_title' => '割引情報', 'pic_flg' => (string) $discount->buai_flg];
            }

            if ($data['manual_value'] > 0) {
                $rows[] = ['tb_search_form_cd' => Sale::FORM_MANUAL_DISCOUNT, 'rank' => null, 'float_data' => $totals['manual'],
                    'text_data' => '', 'array_data' => '', 'form_type' => self::discountLabel($data['manual_value'], $data['manual_type']),
                    'form_title' => '手動割引情報', 'pic_flg' => '2'];
            }

            $rows[] = ['tb_search_form_cd' => Sale::FORM_TOTAL, 'rank' => $totals['commission_base'], 'float_data' => $totals['total'],
                'text_data' => '', 'array_data' => '', 'form_type' => '', 'form_title' => '合計'];

            foreach ($rows as $row) {
                Sale::query()->create(['cd' => $cd++] + $row + $common);
            }

            return ['group' => $group, 'total' => $totals['total'], 'commission_base' => $totals['commission_base'], 'commission' => $commission];
        });
    }

    /** '10％' (type 1) o '500円' (type 2), como form_type de las filas de descuento */
    public static function discountLabel(int $value, string $type): string
    {
        return $value.($type === Discount::TYPE_PERCENT ? '％' : '円');
    }

    /** createHatsuban($_SESSION['shop_cd']) de function.php: epoch + tienda (3 cifras) + aleatorio (6 cifras) */
    public static function createHatsuban(?int $shopCd): int
    {
        return (int) (now()->timestamp.sprintf('%03d', (int) $shopCd).sprintf('%06d', random_int(0, 999999)));
    }

    /**
     * Modelos elegidos en el orden y con las repeticiones del formulario, solo de la tienda y vigentes.
     *
     * @param  class-string<ServiceMenu|Discount>  $class
     * @param  list<int>  $cds
     */
    private function pick(string $class, int $shop, array $cds): Collection
    {
        $found = $class::query()->ofShop($shop)->where('disp_flg', '1')->whereIn('cd', $cds)->get()->keyBy('cd');

        return collect($cds)->map(fn ($cd) => $found->get($cd))->filter()->values();
    }

    /** 営業日 del 履歴: antes de las 5:00 cuenta como el día anterior (BUSINESS_START_TIME) */
    public static function businessDay(): string
    {
        $now = now();

        return ($now->hour < 5 ? $now->copy()->subDay() : $now)->format('Y/m/d');
    }

    /** 履歴 del formulario: filas 合計 de la tienda en el 営業日 de hoy, con el socio */
    public function history(int $shop): Collection
    {
        return Sale::query()
            ->form(Sale::FORM_TOTAL)
            ->join('tb_customer as c', 'c.cd', '=', 'tb_customer_sub.tb_customer_cd')
            ->where('tb_customer_sub.int_data', $shop)
            ->where('tb_customer_sub.update_date', self::businessDay())
            ->orderByDesc('tb_customer_sub.touroku_date')
            ->orderByDesc('tb_customer_sub.cd')
            ->get(['tb_customer_sub.*', 'c.id as customer_id', 'c.name as customer_name']);
    }

    /**
     * karute_delete_list.php: filas 合計 de la tienda con la スタッフ de la cuenta y el socio.
     * Filtros: 登録日時 (touroku_date epoch, date2 incluido) y スタッフ (array_data de la fila -1).
     */
    public function searchQuery(int $shop, ?string $date1, ?string $date2, ?string $staffCd): Builder
    {
        return Sale::query()
            ->form(Sale::FORM_TOTAL)
            ->join('tb_customer_sub as st', function ($join) {
                $join->on('st.tb_customer_cd', '=', 'tb_customer_sub.tb_customer_cd')
                    ->on('st.tb_search_cate_cd', '=', 'tb_customer_sub.tb_search_cate_cd')
                    ->on('st.category_inner_group', '=', 'tb_customer_sub.category_inner_group')
                    ->where('st.tb_search_form_cd', Sale::FORM_STAFF);
            })
            ->join('tb_customer as c', 'c.cd', '=', 'tb_customer_sub.tb_customer_cd')
            ->where('tb_customer_sub.int_data', $shop)
            ->when(self::epoch($date1), fn ($q, $from) => $q->whereRaw('tb_customer_sub.touroku_date::integer >= ?', [$from]))
            ->when(self::epoch($date2), fn ($q, $to) => $q->whereRaw('tb_customer_sub.touroku_date::integer < ?', [$to + 86400]))
            ->when(filled($staffCd), fn ($q) => $q->where('st.array_data', (string) $staffCd))
            ->select(['tb_customer_sub.*', 'st.array_data as staff_cd', 'st.text_data as staff_name', 'c.id as customer_id', 'c.name as customer_name'])
            ->orderByDesc('tb_customer_sub.touroku_date')
            ->orderByDesc('tb_customer_sub.cd');
    }

    /** strtotime() de una fecha del buscador (Asia/Tokyo); null si está vacía o no se entiende */
    private static function epoch(?string $date): ?int
    {
        if (blank($date)) {
            return null;
        }
        $time = strtotime((string) $date);

        return $time === false ? null : $time;
    }

    /**
     * karute_delete_register.php: borra todas las filas de la cuenta (disp_flg 9). El legacy además pisaba
     * update_date (el 営業日) con time(); aquí se conserva. `$shop` (no master): solo cuentas de su tienda.
     */
    public function deleteGroup(int $customerCd, string $group, ?int $shop): int
    {
        if ($shop !== null && ! Sale::query()->ofGroup($customerCd, $group)->where('int_data', $shop)->exists()) {
            return 0;
        }

        return Sale::query()->ofGroup($customerCd, $group)->update(['disp_flg' => Sale::DELETED_FLAG]);
    }

    /** スタッフ de la tienda (tb_staff disp_flg 1, ORDER BY rank, cd) */
    public function staffOf(?int $shop): Collection
    {
        if ($shop === null) {
            return collect();
        }

        return Staff::query()->where('shop_cd', $shop)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get();
    }

    /**
     * ajax_search_kaiin.php: socios (touroku_kubun 2, disp_flg 1) por palabras (espacio 半角/全角) en
     * ID, 管理番号, nombre, カナ, teléfono, correo, nacimiento y móvil; no master, solo los de la tienda. Máx. 200.
     */
    public function searchMembers(string $keyword, ?int $shop, bool $isMaster): Collection
    {
        $query = Customer::query()
            ->where('disp_flg', '1')
            ->where('touroku_kubun', Customer::MEMBER_KUBUN)
            ->when(! $isMaster, fn ($q) => $q->where('tb_shop_cd', (int) $shop));

        if ($keyword !== '') {
            foreach (explode(' ', str_replace('　', ' ', $keyword)) as $word) {
                $kana = mb_convert_kana($word, 'KVC');
                $ascii = mb_convert_kana($word, 'a');
                $query->where(function ($q) use ($word, $kana, $ascii) {
                    $q->where('name', 'like', "%{$word}%")
                        ->orWhere('name_kana', 'like', "%{$kana}%")
                        ->orWhere('id', 'like', "%{$word}%")
                        ->orWhere('tel', 'like', "%{$ascii}%")
                        ->orWhere('email1', 'like', "%{$ascii}%")
                        ->orWhere('kojin_birth', 'like', "%{$word}%")
                        ->orWhere('kanri_id', 'like', "%{$word}%")
                        ->orWhere('kojin_mobile', 'like', "%{$word}%");
                });
            }
        }

        return $query->orderByRaw("coalesce(last_raiten_date, '') DESC")->orderByDesc('cd')->limit(200)->get(['cd', 'id', 'name']);
    }
}
