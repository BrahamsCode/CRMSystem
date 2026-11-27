<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\UpdateStaffWorkRequest;
use App\Services\Reservations\StaffWorkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * スタッフ施術管理 (legacy: category9/yoyaku/staff_works.php + ajax_menu.php). Ventana emergente sin menú.
 * Tras guardar: PRG con el flash `staffWorksSaved` (el legacy re-pintaba con act=reg y recargaba
 * la ventana de origen; fuera del popup recargaba en bucle: aquí no).
 */
class StaffWorkController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(private readonly StaffWorkService $works)
    {
    }

    public function edit(Request $request): View
    {
        $isMaster = $this->isMasterUser();
        $shopCd = $this->screenShopCd($request->query('shop'));

        return view('admin.reservas.staff_works', [
            'isMaster' => $isMaster,
            'shopCd' => $shopCd,
            'staffCd' => $request->query('staff'),
            'shops' => $isMaster ? $this->shopOptions() : collect(),
            'shopName' => $isMaster ? null : $this->shopName($shopCd),
            'staffWord' => $this->works->staffWord(),
            'saved' => (bool) $request->session()->get('staffWorksSaved'),
        ]);
    }

    public function update(UpdateStaffWorkRequest $request): RedirectResponse
    {
        $staffCd = (int) $request->validated('staff');

        $this->works->replace(
            $staffCd,
            (string) ($request->validated('reserve_flg') ?? ''),
            (array) ($request->validated('yoyaku_menu_cd') ?? []),
            (array) ($request->validated('shoyou_time') ?? []),
            (array) ($request->validated('mypage_flg') ?? []),
        );

        return redirect()
            ->route('admin.reservas.staff-works', ['shop' => $this->screenShopCd($request->input('shop')), 'staff' => $staffCd])
            ->with('staffWorksSaved', true);
    }

    /** ajax_menu.php: tabla de menús del empleado (fragmento HTML que carga #menu) */
    public function menu(Request $request): View
    {
        $shopCd = (int) $request->input('shop_cd');
        $staffCd = (int) $request->input('staff_cd');

        return view('admin.reservas.partials.staff_works_menu', [
            'reserveFlg' => $this->works->reserveFlg($staffCd),
            'menus' => $this->works->assignableMenus($shopCd),
            'works' => $this->works->worksByMenu($staffCd),
        ]);
    }

    /** Como el legacy: sin `shop` o si no es master, la tienda de la sesión */
    private function screenShopCd(mixed $requestShop): ?int
    {
        return $this->isMasterUser() && filled($requestShop) ? (int) $requestShop : $this->sessionShopCd();
    }
}
