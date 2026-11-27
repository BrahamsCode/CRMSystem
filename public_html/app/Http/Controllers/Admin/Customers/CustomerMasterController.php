<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerMasterRequest;
use App\Services\Customers\CustomerMasterService;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Base de las listas maestras de 顧客管理 con alta/edición en la misma pantalla, ▲ y 削除
 * (legacy: `<menu>_new.php` + `<menu>_frm.php` + `<menu>_register.php`). Vista: admin.clientes.masters.index.
 */
abstract class CustomerMasterController extends Controller
{
    public function __construct(protected CustomerMasterService $masters) {}

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Pantalla para la vista: 'group' | 'motive' | 'level' */
    abstract protected function screen(): string;

    /** Prefijo de los nombres de ruta (admin.clientes.groups…) */
    abstract protected function routeName(): string;

    /** createHatsuban($_SESSION['shop_cd']) (true) o contador `hatuban` (false) para el `cd` nuevo */
    protected function usesCreateHatsuban(): bool
    {
        return true;
    }

    /** Columnas que guarda el `*_register.php`, con get_form() */
    protected function values(CustomerMasterRequest $request): array
    {
        return ['name' => LegacyText::form($request->input('name'))];
    }

    /** Formulario (alta, o edición con `?cd=`) y lista */
    public function index(Request $request): View
    {
        $class = $this->modelClass();
        $item = filled($request->query('cd')) ? $class::query()->find((int) $request->query('cd')) : null;

        return view('admin.clientes.masters.index', [
            'screen' => $this->screen(),
            'routeName' => $this->routeName(),
            'siteName' => app(LegacyUi::class)->siteName(),
            'item' => $item,
            'items' => $this->masters->active($class),
        ]);
    }

    public function store(CustomerMasterRequest $request): RedirectResponse
    {
        $class = $this->modelClass();
        $shopCd = $this->usesCreateHatsuban() ? $this->shopCd($request) : null;
        $this->masters->save(new $class, $this->values($request), $shopCd);

        return redirect()->route($this->routeName());
    }

    public function update(CustomerMasterRequest $request, int $cd): RedirectResponse
    {
        $this->masters->save($this->find($cd), $this->values($request));

        return redirect()->route($this->routeName());
    }

    public function destroy(int $cd): RedirectResponse
    {
        $this->masters->delete($this->find($cd));

        return redirect()->route($this->routeName());
    }

    /** ▲ (kind=up) / ▼ (kind=down) */
    public function sort(Request $request, int $cd): RedirectResponse
    {
        $request->validate(['kind' => ['required', 'in:up,down']]);
        $this->masters->move($this->find($cd), $request->input('kind'));

        return redirect()->route($this->routeName());
    }

    private function find(int $cd): Model
    {
        return $this->modelClass()::query()->findOrFail($cd);
    }

    private function shopCd(Request $request): int
    {
        return (int) ($request->session()->get('shop_cd') ?? auth('admin')->user()?->shop_cd ?? 0);
    }
}
