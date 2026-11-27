<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Services\Ventas\RankedMasterService;
use App\Services\Ventas\VentasShop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Base de メニュー管理 (karute_menu.php) y 割引管理 (karute_waribiki.php): formulario de alta/edición por tienda,
 * listado por AJAX con ▲▼ (ajax_karute_*_list.php) y borrado (act=del del *_register.php).
 */
abstract class RankedMasterController extends Controller
{
    public function __construct(protected RankedMasterService $service, protected VentasShop $shop) {}

    /** @return class-string<ServiceMenu|Discount> */
    abstract protected function modelClass(): string;

    /** Carpeta de vistas (admin.ventas.<x>) y prefijo de rutas (admin.ventas.<x>.*) */
    abstract protected function key(): string;

    /** Pantalla: selector de tienda (master), formulario y contenedor del listado */
    public function index(Request $request): View
    {
        $shopCd = $this->shop->resolve($request->input('shop'));
        $item = null;

        if (filled($request->input('cd'))) {
            $item = $this->modelClass()::query()->where('disp_flg', '1')->find((int) $request->input('cd'));
            if ($item && ! $this->shop->canAccess((int) $item->tb_shop_cd)) {
                $item = null;
            }
        }

        return view("admin.ventas.{$this->key()}.index", [
            'shopCd' => $shopCd,
            'shopName' => $this->shop->name($shopCd),
            'shops' => $this->shop->options(),
            'isMaster' => $this->shop->isMaster(),
            'item' => $item,
        ]);
    }

    /** act=new|up del *_register.php: vuelve a la pantalla de la tienda */
    protected function save(Request $request, array $values): RedirectResponse
    {
        $editing = $request->input('act') === 'up';
        Gate::authorize($editing ? 'ventas.update' : 'ventas.create');

        $shopCd = $this->shop->isMaster() ? (int) $request->input('tb_shop_cd') : $this->shop->sessionShop();
        abort_if(! $shopCd, 404);

        $cd = null;
        if ($editing) {
            $existing = $this->modelClass()::query()->findOrFail((int) $request->input('cd'));
            abort_unless($this->shop->canAccess((int) $existing->tb_shop_cd), 403);
            $cd = (int) $existing->cd;
        }

        $this->service->save($this->modelClass(), $cd, $shopCd, $values);

        return redirect()->route("admin.ventas.{$this->key()}.index", ['shop' => $shopCd]);
    }

    /** act=del: borrado lógico y corrimiento de rank */
    public function destroy(Request $request): RedirectResponse
    {
        $item = $this->modelClass()::query()->find((int) $request->input('cd'));

        if ($item && $this->shop->canAccess((int) $item->tb_shop_cd)) {
            $this->service->delete($item);
        }

        return redirect()->route("admin.ventas.{$this->key()}.index", ['shop' => $item?->tb_shop_cd ?? $request->input('tb_shop_cd')]);
    }

    /**
     * ajax_karute_*_list.php (POST shop_cd, send_type, send_targetCd): ▲▼ y la tabla. Sin tienda, respuesta vacía.
     * El caso send_type=del del legacy (sin uso desde la pantalla y que corría el rank de todas las tiendas) no se migra:
     * el borrado va por destroy().
     */
    public function list(Request $request): Response
    {
        $shopCd = $this->shop->isMaster() ? (int) $request->input('shop_cd') : (int) $this->shop->sessionShop();
        if ($shopCd === 0) {
            return response('');
        }

        $type = (string) $request->input('send_type');
        if (in_array($type, ['rankup', 'rankdown'], true) && filled($request->input('send_targetCd')) && Gate::allows('ventas.update')) {
            $this->service->move($this->modelClass(), $shopCd, (int) $request->input('send_targetCd'), $type);
        }

        return response()->view("admin.ventas.{$this->key()}.list", [
            'shopCd' => $shopCd,
            'items' => $this->service->list($this->modelClass(), $shopCd),
            'isMaster' => $this->shop->isMaster(),
        ]);
    }

    /** URL del legacy (menú lateral, marcadores) → pantalla nueva con la misma query */
    public function legacy(Request $request): RedirectResponse
    {
        return redirect()->route("admin.ventas.{$this->key()}.index", $request->query());
    }
}
