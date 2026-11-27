<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Services\Sales\KaruteMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Base de メニュー管理 y 割引管理 (legacy: karute_menu.php / karute_waribiki.php, sus *_register.php y la lista
 * AJAX ajax_karute_*_list.php). Las dos pantallas son la misma con otras columnas.
 */
abstract class KaruteMasterController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(protected KaruteMasterService $service) {}

    /** Clase del modelo (ServiceMenu o Discount) */
    abstract protected function modelClass(): string;

    /** Prefijo de vistas y rutas: 'menus' o 'discounts' */
    abstract protected function key(): string;

    /** Columnas propias validadas de la petición (además de tb_shop_cd y banderas) */
    abstract protected function values(Request $request): array;

    /** Pantalla: selector de tienda (master), formulario (alta o ?cd=) y contenedor de la lista AJAX */
    public function index(Request $request): View
    {
        $shopCd = $this->screenShopCd($request->query('shop'));
        $item = $this->service->findActive($this->modelClass(), $request->query('cd'));
        if ($item && ! $this->isMasterUser() && $item->tb_shop_cd !== $shopCd) {
            $item = null; // no master: solo los de su tienda
        }

        return view('admin.ventas.'.$this->key(), [
            'item' => $item,
            'shopCd' => $shopCd,
            'shopName' => $shopCd === -1 ? null : $this->shopName($shopCd),
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
        ]);
    }

    /** act=new */
    public function store(Request $request): RedirectResponse
    {
        $shopCd = $this->formShopCd($request);
        $this->service->save(new ($this->modelClass()), $this->values($request), $shopCd);

        return $this->backToIndex($shopCd);
    }

    /** act=up */
    public function update(Request $request, int $cd): RedirectResponse
    {
        $item = $this->ownedItem($cd);
        $shopCd = $this->formShopCd($request);
        $this->service->save($item, $this->values($request), $shopCd);

        return $this->backToIndex($shopCd);
    }

    /** act=del (formulario oculto form_<cd> de la lista) */
    public function destroy(Request $request, int $cd): RedirectResponse
    {
        $item = $this->ownedItem($cd);
        $this->service->delete($item, $request->input('rank'));

        return $this->backToIndex($this->formShopCd($request));
    }

    /** Lista (ajax_karute_*_list.php): ▲▼ con send_type rankup/rankdown y la tabla de la tienda */
    public function list(Request $request): View|string
    {
        $shopCd = $this->isMasterUser() ? $request->input('shop_cd') : $this->sessionShopCd();
        if (blank($shopCd)) {
            return ''; // el legacy hacía exit sin tienda
        }
        $shopCd = (int) $shopCd;

        $type = $request->input('send_type');
        if (in_array($type, ['rankup', 'rankdown'], true) && filled($request->input('send_targetCd')) && filled($request->input('send_vl'))) {
            $this->service->move($this->modelClass(), $shopCd, (int) $request->input('send_targetCd'), (int) $request->input('send_vl'), $type);
        }

        return view('admin.ventas.'.$this->key().'_list', [
            'items' => $this->service->list($this->modelClass(), $shopCd),
            'shopCd' => $shopCd,
            'isMaster' => $this->isMasterUser(),
        ]);
    }

    /** Tienda de la pantalla: ?shop= para master; la de la sesión si no */
    private function screenShopCd(mixed $requestShop): ?int
    {
        if (! $this->isMasterUser()) {
            return $this->sessionShopCd();
        }

        return filled($requestShop) ? (int) $requestShop : null;
    }

    /** tb_shop_cd del formulario (oculto); el no master siempre graba en su tienda */
    private function formShopCd(Request $request): int
    {
        return $this->isMasterUser() ? (int) $request->input('tb_shop_cd') : (int) $this->sessionShopCd();
    }

    private function ownedItem(int $cd): ServiceMenu|Discount
    {
        $item = $this->modelClass()::query()->findOrFail($cd);
        abort_if(! $this->isMasterUser() && $item->tb_shop_cd !== $this->sessionShopCd(), 404);

        return $item;
    }

    private function backToIndex(int $shopCd): RedirectResponse
    {
        return redirect()->route('admin.ventas.'.$this->key().'.index', ['shop' => $shopCd]);
    }
}
