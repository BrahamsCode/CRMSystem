<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\FacilityRequest;
use App\Models\Reservations\Facility;
use App\Services\Reservations\ReservationInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 設備管理 (legacy: category9/yoyaku/setsubi_new.php y setsubi_register.php). Ventana emergente sin navegación.
 */
class FacilityController extends Controller
{
    use SelectsLegacyShop;

    /** Formulario (alta, o edición con `?cd=`) y lista de la tienda; `?shop=` elige tienda (master) */
    public function index(Request $request): View
    {
        $facility = null;
        if (filled($request->query('cd'))) {
            // El legacy cortaba con 「不正アクセス！！！」 si el cd no existía
            $facility = Facility::query()->where('disp_flg', Facility::ACTIVE_FLAG)->findOrFail((int) $request->query('cd'));
        }
        $shopCd = $this->resolveShopCd($facility?->tb_shop_cd, $request->query('shop'));

        return view('admin.reservas.facilities', [
            'facility' => $facility,
            'shopCd' => $shopCd,
            'shopName' => $this->shopName($shopCd),
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
            'facilities' => $shopCd
                ? Facility::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', Facility::ACTIVE_FLAG)->orderBy('cd')->get()
                : collect(),
        ]);
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        return $this->save($request, new Facility(['disp_flg' => Facility::ACTIVE_FLAG]));
    }

    public function update(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        return $this->save($request, $facility);
    }

    public function destroy(Request $request, Facility $facility): RedirectResponse
    {
        $facility->delete();

        return redirect()->route('admin.reservas.facilities.index', ['shop' => $request->input('shop_cd', $facility->tb_shop_cd)]);
    }

    private function save(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->fill([
            'tb_shop_cd' => (int) $request->validated('shop_cd'),
            // form2Db() de setsubi_register.php (半角カナ → 全角 y recorte); texto plano
            'name' => ReservationInput::form2Db($request->validated('name')),
            'quantity' => (int) $request->validated('quantity'),
        ])->save();

        return redirect()->route('admin.reservas.facilities.index', ['shop' => $facility->tb_shop_cd]);
    }
}
