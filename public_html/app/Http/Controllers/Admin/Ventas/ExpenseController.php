<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\ExpenseRequest;
use App\Services\Mypage\LegacyPageControl;
use App\Services\Ventas\ExpenseService;
use App\Services\Ventas\VentasShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** 経費管理 (category1/karute_menu/keihi.php, keihi_register.php, keihi_delete.php) */
class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenses, private VentasShop $shop) {}

    /** keihi.php: formulario (alta o ?cd= edición) y 経費一覧 paginado (20 por página, page_control.php) */
    public function index(Request $request, LegacyPageControl $pager): View
    {
        $onlyShop = $this->onlyShop();
        $expense = filled($request->input('cd')) ? $this->expenses->find((int) $request->input('cd'), $onlyShop) : null;

        return view('admin.ventas.expenses.index', [
            'expense' => $expense,
            'editing' => filled($request->input('cd')),
            'date' => $expense?->update_date ?? now()->format('Y/m/d'),
            'isMaster' => $this->shop->isMaster(),
            'shops' => $this->shop->options(),
            'sessionShop' => $this->shop->sessionShop(),
            'sessionShopName' => $this->shop->name($this->shop->sessionShop()),
            'page' => $pager->paginate($this->expenses->listQuery($onlyShop), $request, 20),
        ]);
    }

    /** keihi_register.php (act=new|up) */
    public function store(ExpenseRequest $request): RedirectResponse
    {
        $editing = $request->input('act') === 'up';
        Gate::authorize($editing ? 'ventas.update' : 'ventas.create');

        $shopCd = $this->shop->resolveOrSession($request->input('shop'));
        abort_if($shopCd === null, 404);

        $expense = null;
        if ($editing) {
            $expense = $this->expenses->find((int) $request->input('cd'), $this->onlyShop());
            abort_if($expense === null, 404);
        }

        $this->expenses->save($expense, ['shop' => $shopCd] + $request->expense());

        return redirect()->route('admin.ventas.expenses.index', ['status' => 'saved']);
    }

    /** keihi_delete.php (delete_flg=del) */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->input('delete_flg') === 'del' && filled($request->input('cd'))) {
            $expense = $this->expenses->find((int) $request->input('cd'), $this->onlyShop());
            if ($expense) {
                $this->expenses->delete($expense);
            }
        }

        return redirect()->route('admin.ventas.expenses.index', ['status' => 'saved']);
    }

    /** keihi.php del legacy (menú lateral) → pantalla nueva */
    public function legacy(Request $request): RedirectResponse
    {
        return redirect()->route('admin.ventas.expenses.index', $request->query());
    }

    /** master ve los gastos de todas las tiendas; el resto, los de la suya */
    private function onlyShop(): ?int
    {
        return $this->shop->isMaster() ? null : $this->shop->sessionShop();
    }
}
