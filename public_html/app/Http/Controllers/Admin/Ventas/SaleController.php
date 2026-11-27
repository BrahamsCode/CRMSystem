<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\SaleStoreRequest;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Services\Mypage\LegacyPageControl;
use App\Services\Ventas\RankedMasterService;
use App\Services\Ventas\SaleService;
use App\Services\Ventas\VentasShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 売上データ作成 (karute_new.php, karute_new_frm.php, karute_new_register.php) y
 * 売上データ削除 (karute_delete_list.php, karute_delete_register.php).
 */
class SaleController extends Controller
{
    /** Clave de sesión del token anti doble envío (`$_SESSION['add_record_verify']`) */
    private const VERIFY_KEY = 'ventas.add_record_verify';

    public function __construct(private SaleService $sales, private VentasShop $shop) {}

    /** karute_new.php: selector de tienda (master) y botón que abre el formulario en otra ventana */
    public function create(Request $request): View
    {
        $shopCd = $this->shop->resolve($request->input('shop'));

        return view('admin.ventas.sales.create', [
            'shopCd' => $shopCd,
            'shops' => $this->shop->options(),
            'isMaster' => $this->shop->isMaster(),
        ]);
    }

    /** karute_new_frm.php: ventana emergente con el formulario, buscador de socios, スタッフ, menús y 履歴 */
    public function form(Request $request, RankedMasterService $masters): View
    {
        $shopCd = $this->shop->resolve($request->input('shop'));
        $history = $shopCd !== null ? $this->sales->history($shopCd) : collect();

        return view('admin.ventas.sales.form', [
            'shopCd' => $shopCd,
            'saved' => $request->input('status') === 'saved',
            // El legacy listaba siempre las スタッフ de la tienda 1 (shop_cd = 1 fijo); aquí, las de la tienda elegida
            'staff' => $this->sales->staffOf($shopCd),
            'menus' => $shopCd !== null ? $masters->list(ServiceMenu::class, $shopCd) : collect(),
            'discounts' => $shopCd !== null ? $masters->list(Discount::class, $shopCd) : collect(),
            'history' => $history,
            'historyTotal' => (int) $history->sum('float_data'),
        ]);
    }

    /** karute_new_register.php: guarda la cuenta y vuelve al formulario con status=saved */
    public function store(SaleStoreRequest $request): RedirectResponse|Response
    {
        $shopCd = $this->shop->resolve($request->input('shop'));
        if ($shopCd === null) {
            return response(SaleStoreRequest::ERROR, 422);
        }

        // Doble clic: si el token es el mismo que el último guardado, no se vuelve a registrar
        $token = (string) $request->input('add_record_verify');
        if (! $request->session()->has(self::VERIFY_KEY) || $request->session()->get(self::VERIFY_KEY) !== $token) {
            try {
                $this->sales->store(['shop' => $shopCd] + $request->sale(), $this->shop->sessionShop());
            } catch (\DomainException $e) {
                return response($e->getMessage(), 422);
            }
            $request->session()->put(self::VERIFY_KEY, $token);
        }

        return redirect()->route('admin.ventas.sales.form', ['shop' => $shopCd, 'status' => 'saved']);
    }

    /** karute_delete_list.php: buscador (登録日時, スタッフ) y listado paginado (10 por página) */
    public function index(Request $request, LegacyPageControl $pager): View
    {
        $shopCd = $this->shop->resolve($request->input('shop'));
        $filters = [
            'shop' => $shopCd,
            'date1' => (string) $request->input('date1'),
            'date2' => (string) $request->input('date2'),
            'staff' => (string) $request->input('staff'),
        ];

        $page = $shopCd !== null
            ? $pager->paginate($this->sales->searchQuery($shopCd, $filters['date1'], $filters['date2'], $filters['staff']), $request, 10)
            : null;

        return view('admin.ventas.sales.index', [
            'shopCd' => $shopCd,
            'shopName' => $this->shop->name($shopCd),
            'shops' => $this->shop->options(),
            'isMaster' => $this->shop->isMaster(),
            'staff' => $this->sales->staffOf($shopCd),
            'filters' => $filters,
            'page' => $page,
        ]);
    }

    /** karute_delete_register.php (act=del): borra la cuenta y vuelve al listado con los mismos filtros */
    public function destroy(Request $request): RedirectResponse
    {
        $customerCd = (int) $request->input('cd');
        $group = (string) $request->input('group');

        if ($customerCd > 0 && $group !== '') {
            $this->sales->deleteGroup($customerCd, $group, $this->shop->isMaster() ? null : $this->shop->sessionShop());
        }

        return redirect()->route('admin.ventas.sales.index', [
            'shop' => $request->input('shop'),
            'staff' => $request->input('staff'),
            'date1' => $request->input('date1'),
            'date2' => $request->input('date2'),
        ]);
    }

    /** karute_new.php del legacy (menú lateral) → pantalla nueva */
    public function legacyCreate(Request $request): RedirectResponse
    {
        return redirect()->route('admin.ventas.sales.create', $request->query());
    }

    /** karute_new_frm.php del legacy → formulario nuevo */
    public function legacyForm(Request $request): RedirectResponse
    {
        return redirect()->route('admin.ventas.sales.form', $request->query());
    }

    /** karute_delete_list.php del legacy (menú lateral) → listado nuevo */
    public function legacyIndex(Request $request): RedirectResponse
    {
        return redirect()->route('admin.ventas.sales.index', $request->query());
    }
}
