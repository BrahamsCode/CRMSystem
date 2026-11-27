<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\UpdateFreeReservationRequest;
use App\Services\Reservations\FreeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * フリー予約設定 (legacy: category9/yoyaku/free_yoyaku.php, free_yoyaku_register.php y
 * ajax_free_yoyaku_rank.php). Ventana emergente sin menú; el enlace solo aparece en la navegación
 * si `admin_master.free_yoyaku_flg = '1'` (parcial yoyaku_navi), la pantalla no lo comprueba (como el legacy).
 */
class FreeReservationController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(private readonly FreeReservationService $free)
    {
    }

    public function edit(Request $request): View
    {
        $isMaster = $this->isMasterUser();
        $shopCd = $this->screenShopCd($request->query('shop'));

        return view('admin.reservas.free_yoyaku', [
            'isMaster' => $isMaster,
            'shopCd' => $shopCd,
            'shops' => $isMaster ? $this->shopOptions() : collect(),
            'shopName' => match (true) {
                $shopCd === null => null,
                $shopCd === -1 => '全店舗用',
                default => $this->shopName($shopCd),
            },
            'settings' => $shopCd === null ? [] : $this->free->settings($shopCd),
        ]);
    }

    public function update(UpdateFreeReservationRequest $request): RedirectResponse
    {
        $shopCd = $this->screenShopCd($request->validated('free_shop_cd'));

        if ($shopCd !== null) {
            $this->free->save($shopCd, $request->validated());
        }

        return redirect()->route('admin.reservas.free', ['shop' => $shopCd]);
    }

    /** ajax_free_yoyaku_rank.php: ▲▼ opcional y tabla de 優先順位 (fragmento HTML que carga .tableDisp) */
    public function ranks(Request $request): View|string
    {
        $shopCd = $this->screenShopCd($request->input('shop_cd'));
        if (! $shopCd) {
            return '';
        }

        $type = (string) $request->input('send_type');
        if (
            filled($request->input('send_targetCd'))
            && filled($request->input('send_vl'))
            && in_array($type, ['rankup', 'rankdown'], true)
        ) {
            $prevCd = $request->input('send_prevCd');
            $this->free->move($shopCd, $type, (int) $request->input('send_targetCd'), filled($prevCd) ? (int) $prevCd : null);
        }

        return view('admin.reservas.partials.free_yoyaku_rank', [
            'shopCd' => $shopCd,
            'ranking' => $this->free->ranking($shopCd),
        ]);
    }

    /**
     * Tienda: master elige con `shop`; el resto, siempre la de su sesión (el legacy aceptaba cualquier
     * `free_shop_cd`/`shop_cd` en el registro y en el AJAX: aquí no).
     */
    private function screenShopCd(mixed $requestShop): ?int
    {
        if ($this->isMasterUser()) {
            return filled($requestShop) ? (int) $requestShop : null;
        }

        return $this->sessionShopCd();
    }
}
