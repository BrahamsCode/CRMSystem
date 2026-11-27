<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Services\Sales\ExpenseService;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 経費管理 (legacy: system/category1/karute_menu/keihi.php, keihi_register.php, keihi_delete.php).
 */
class ExpenseController extends Controller
{
    use SelectsLegacyShop;

    /** page_control.php: kensu por defecto 20 */
    private const KENSU = 20;

    public function __construct(private ExpenseService $expenses) {}

    /** Formulario (alta o ?cd=) y 経費一覧 paginado */
    public function index(Request $request): View
    {
        $onlyShop = $this->isMasterUser() ? null : $this->sessionShopCd();
        $expense = $this->expenses->find($request->query('cd'), $onlyShop);
        $kensu = (int) $request->query('kensu') ?: self::KENSU;

        return view('admin.ventas.expenses', [
            'expense' => $expense,
            'date' => $expense->update_date ?? now('Asia/Tokyo')->format('Y/m/d'),
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
            'sessionShopCd' => $this->sessionShopCd(),
            'sessionShopName' => $this->shopName($this->sessionShopCd()),
            'paginator' => $this->expenses->paginate($onlyShop, max(1, (int) $request->query('page', 1)), $kensu),
        ]);
    }

    /** act=new */
    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    /** act=up */
    public function update(Request $request, int $cd): RedirectResponse
    {
        $onlyShop = $this->isMasterUser() ? null : $this->sessionShopCd();
        abort_unless($this->expenses->find($cd, $onlyShop), 404);

        return $this->save($request, $cd);
    }

    /** keihi_delete.php (delete_flg=del) */
    public function destroy(Request $request, int $cd): RedirectResponse
    {
        $this->expenses->delete($cd, $this->isMasterUser() ? null : $this->sessionShopCd());

        return redirect()->route('admin.ventas.expenses.index', ['status' => 'saved']);
    }

    private function save(Request $request, ?int $cd): RedirectResponse
    {
        // submitChk() del legacy: tienda elegida y 使用金額 > 0
        $request->merge(['price' => (int) $request->input('price')]);
        $request->validate(['shop' => ['required'], 'price' => ['integer', 'min:1']], [
            'shop.required' => __('ventas.expense_shop_required', [], 'ja'),
            'price.min' => __('ventas.expense_price_required', [], 'ja'),
        ]);

        $shopCd = $this->isMasterUser() ? (int) $request->input('shop') : (int) $this->sessionShopCd();
        $this->expenses->save(
            $cd, $shopCd,
            LegacyText::db($request->input('date1')),
            LegacyText::db($request->input('mokuteki')),
            (float) $request->input('price'),
        );

        return redirect()->route('admin.ventas.expenses.index', ['status' => 'saved']);
    }
}
