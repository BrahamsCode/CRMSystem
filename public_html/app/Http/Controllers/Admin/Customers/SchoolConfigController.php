<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\SchoolConfigService;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 学年設定 y クラス設定 (legacy `system/category1/config/schoolyear_{config,register,delete}.php` y
 * `schoolclass_*.php`, sin enlace en el menú). Las dos pantallas son la misma con otra tabla: `kind`
 * llega como valor por defecto de la ruta ('years' | 'classes').
 */
class SchoolConfigController extends Controller
{
    public function __construct(private SchoolConfigService $configs) {}

    /** Formulario (alta, o edición con ?cd=) y lista */
    public function index(Request $request): View
    {
        $kind = $this->kind($request);
        $cd = $request->query('cd');

        return view('admin.clientes.config.school', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'title' => $this->configs->title($kind),
            'routePrefix' => $this->configs->routePrefix($kind),
            'item' => is_scalar($cd) && ctype_digit((string) $cd) ? $this->configs->find($kind, (int) $cd) : null,
            'items' => $this->configs->list($kind),
        ]);
    }

    /** act=new */
    public function store(Request $request): View|RedirectResponse
    {
        return $this->save($request, $this->kind($request), null);
    }

    /** act=up */
    public function update(Request $request, int $cd): View|RedirectResponse
    {
        $kind = $this->kind($request);
        $item = $this->configs->find($kind, $cd);
        abort_if($item === null, 404);

        return $this->save($request, $kind, $item);
    }

    /** 削除 (disp_flg = '9') */
    public function destroy(Request $request, int $cd): View
    {
        $kind = $this->kind($request);
        $this->configs->delete($kind, $cd);

        return $this->processing($kind, '削除', '削除中です。');
    }

    /** 'years' | 'classes', valor por defecto de la ruta */
    private function kind(Request $request): string
    {
        return (string) $request->route('kind');
    }

    private function save(Request $request, string $kind, $item): View|RedirectResponse
    {
        $name = LegacyText::input($request->input('cate_name'));
        // submitChk() del legacy (solo JS): sin nombre no se registra
        if ($name === '') {
            return to_route($this->configs->routePrefix($kind) . '.index', $item ? ['cd' => $item->cd] : []);
        }

        $this->configs->save($kind, $item, $name);

        return $this->processing($kind, '登録', '登録中です。');
    }

    /** 「登録中です。」/「削除中です。」 con meta Refresh de 2 s a la lista, como *_register.php / *_delete.php */
    private function processing(string $kind, string $action, string $message): View
    {
        $title = $this->configs->title($kind);

        return view('admin.clientes.settings.processing', [
            'title' => $title . $action . ' ' . app(LegacyUi::class)->siteName(),
            'heading' => '■' . $title . $action,
            'message' => $message,
            'redirectUrl' => route($this->configs->routePrefix($kind) . '.index'),
        ]);
    }
}
