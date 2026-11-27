<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Models\Staff;
use App\Services\Sales\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 売上データ作成 y 売上データ削除 (legacy: system/category1/karute_menu/karute_new.php, karute_new_frm.php,
 * karute_new_register.php, karute_delete_list.php, karute_delete_register.php).
 */
class SaleController extends Controller
{
    use SelectsLegacyShop;

    /** El formulario del legacy solo lista el personal de la tienda 1 (`shop_cd = 1` fijo en karute_new_frm.php) */
    private const FORM_STAFF_SHOP = 1;

    /** karute_delete_list.php fija kensu = 10 si no viene */
    private const LIST_KENSU = 10;

    public function __construct(private SaleService $sales) {}

    /** 売上データ作成: selector de tienda (master) y botón que abre el formulario en otra ventana */
    public function create(Request $request): View
    {
        $shopCd = $this->screenShopCd($request->query('shop'));

        return view('admin.ventas.sales.create', [
            'shopCd' => $shopCd,
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
        ]);
    }

    /** Formulario emergente de la venta (karute_new_frm.php) */
    public function form(Request $request): View
    {
        $shopCd = $this->screenShopCd($request->query('shop'));
        $history = $shopCd ? $this->sales->businessDayHistory($shopCd) : collect();

        return view('admin.ventas.sales.form', [
            'shopCd' => $shopCd,
            'saved' => $request->query('status') === 'saved',
            'today' => now('Asia/Tokyo')->format('Y/m/d'),
            'verifyToken' => (string) time(),
            'staffList' => Staff::query()->where('shop_cd', self::FORM_STAFF_SHOP)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get(),
            'menus' => $shopCd ? ServiceMenu::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get() : collect(),
            'discounts' => $shopCd ? Discount::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get() : collect(),
            'history' => $history,
            'historyTotal' => (int) $history->sum(fn ($row) => (int) $row->float_data),
        ]);
    }

    /** karute_new_register.php: graba y vuelve al formulario con status=saved (el legacy pasaba por 「登録中です」) */
    public function store(Request $request): RedirectResponse|Response
    {
        $kaiinCd = (string) $request->input('kaiin_cd');
        $staffCd = (string) $request->input('staff_cd');
        if (! ctype_digit($kaiinCd) || ! ctype_digit($staffCd)) {
            return response(__('ventas.sale_error', [], 'ja'));
        }

        $shopCd = (int) $this->screenShopCd($request->input('shop'));
        $staff = Staff::query()->find((int) $staffCd);
        $customerExists = Customer::query()->whereKey((int) $kaiinCd)->exists();
        $businessDay = trim((string) $request->input('update_date'));
        if (! $staff || ! $customerExists || ! $this->isLegacyDate($businessDay)) {
            return response(__('ventas.sale_error', [], 'ja'));
        }

        // ダブルクリックを防ぐ (add_record_verify): el mismo token no graba dos veces
        $token = (string) $request->input('add_record_verify');
        if (! $request->session()->has('ventas.add_record_verify') || $request->session()->get('ventas.add_record_verify') !== $token) {
            $request->session()->put('ventas.add_record_verify', $token);

            $groupNo = $this->sales->store(
                (int) $kaiinCd, $staff, $shopCd, $businessDay,
                (array) $request->input('menu_cd', []), (array) $request->input('waribiki_cd', []),
                (int) $request->input('otherWaribikiValue'), (string) $request->input('otherWaribikiType'),
                (int) $this->sessionShopCd(),
            );
            if ($groupNo === null) {
                $request->session()->forget('ventas.add_record_verify');

                return response(__('ventas.sale_negative', [], 'ja'));
            }
        }

        return redirect()->route('admin.ventas.sales.form', ['shop' => $shopCd, 'status' => 'saved']);
    }

    /** 売上データ削除 (karute_delete_list.php): búsqueda por 登録日時 y スタッフ, paginada */
    public function index(Request $request): View
    {
        $shopCd = $this->screenShopCd($request->input('shop'));
        $filters = [
            'date1' => (string) $request->input('date1', ''),
            'date2' => (string) $request->input('date2', ''),
            'staff' => (string) $request->input('staff', ''),
        ];
        $kensu = (int) $request->input('kensu') ?: self::LIST_KENSU;

        return view('admin.ventas.sales.index', [
            'shopCd' => $shopCd,
            'shopName' => $shopCd === -1 ? null : $this->shopName($shopCd),
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
            'staffOptions' => Staff::query()->where('shop_cd', (int) $shopCd)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->get(),
            'filters' => $filters,
            'paginator' => $shopCd
                ? $this->sales->search($shopCd, $filters['date1'], $filters['date2'], $filters['staff'], max(1, (int) $request->input('page', 1)), $kensu)
                : null,
        ]);
    }

    /** karute_delete_register.php (act=del): borra la cuenta (cd = cliente, group = nº de cuenta) y vuelve a la lista */
    public function destroy(Request $request): RedirectResponse
    {
        $customerCd = (int) $request->input('cd');
        $group = (string) $request->input('group');
        $shopCd = $this->screenShopCd($request->input('shop'));

        if ($customerCd && $group !== '' && $request->input('act') === 'del') {
            $this->sales->delete($customerCd, $group);
        }

        return redirect()->route('admin.ventas.sales.index', [
            'shop' => $shopCd, 'staff' => $request->input('staff'),
            'date1' => $request->input('date1'), 'date2' => $request->input('date2'),
        ]);
    }

    /** Tienda: master elige con ?shop=; el resto usa la de su sesión */
    private function screenShopCd(mixed $requestShop): ?int
    {
        if (! $this->isMasterUser()) {
            return $this->sessionShopCd();
        }

        return filled($requestShop) ? (int) $requestShop : null;
    }

    /** ckDate() del JS: YYYY/MM/DD y fecha real */
    private function isLegacyDate(string $date): bool
    {
        return (bool) preg_match('#^(\d{4})/(\d{2})/(\d{2})$#', $date, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}
