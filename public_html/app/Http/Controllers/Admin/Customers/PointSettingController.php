<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\PointSetting;
use App\Services\Customers\PointSettingImageStore;
use App\Services\Customers\PointSettingService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ポイント管理 (legacy: category1/customer/point_setting.php, _frm.php, _register.php y ajax_apliFileUpload.php).
 * Solo con isExtShopDomain() (Gate clientes.ext-shop.enabled en las rutas), como el menú.
 */
class PointSettingController extends Controller
{
    public function __construct(
        private readonly PointSettingService $settings,
        private readonly PointSettingImageStore $images,
    ) {}

    /** Formulario (alta, o edición con `?cd=`), imágenes 『ポイントについて』 y lista */
    public function index(Request $request): View
    {
        $item = filled($request->query('cd')) ? PointSetting::query()->find((int) $request->query('cd')) : null;

        return view('admin.clientes.point-settings.index', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'item' => $item,
            'items' => $this->settings->active(),
            'frames' => $this->images->frames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->settings->save(new PointSetting, $this->validated($request));

        return redirect()->route('admin.clientes.point-settings');
    }

    public function update(Request $request, int $cd): RedirectResponse
    {
        $this->settings->save($this->find($cd), $this->validated($request));

        return redirect()->route('admin.clientes.point-settings');
    }

    public function destroy(int $cd): RedirectResponse
    {
        $this->settings->delete($this->find($cd));

        return redirect()->route('admin.clientes.point-settings');
    }

    /** ▲ (kind=up) / ▼ (kind=down) */
    public function sort(Request $request, int $cd): RedirectResponse
    {
        $request->validate(['kind' => ['required', 'in:up,down']]);
        $this->settings->move($this->find($cd), $request->input('kind'));

        return redirect()->route('admin.clientes.point-settings');
    }

    /**
     * POST a la URL legacy point_setting.php: mypage_config (bloque del _frm.php) y act = new | up | del
     * (point_setting_register.php). Sin act, vuelve a la pantalla.
     */
    public function legacyRegister(Request $request): RedirectResponse
    {
        if (is_array($request->input('mypage_config'))) {
            $this->images->mergeMypageConfig($request->input('mypage_config'));
        }

        $cd = (int) $request->input('cd');
        match ((string) $request->input('act')) {
            'new' => $this->settings->save(new PointSetting, $this->validated($request)),
            'up' => $this->settings->save($this->find($cd), $this->validated($request)),
            'del' => $this->settings->delete($this->find($cd)),
            default => null,
        };

        return redirect()->route('admin.clientes.point-settings');
    }

    /** category1/customer/ajax_apliFileUpload.php: siempre JSON */
    public function apliFileUpload(Request $request): JsonResponse
    {
        $file = $request->file('file');

        return response()->json($this->images->handle(
            (string) $request->input('type'),
            $file instanceof \Illuminate\Http\UploadedFile && $file->isValid() ? $file : null,
            $request->except('file'),
        ));
    }

    /** Temporal recién subido (`./uploads/temp/<imageName>` del JS) */
    public function tempImage(string $file): StreamedResponse
    {
        $path = $this->images->tempPath($file);
        abort_if($path === null || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    /**
     * El legacy solo comprobaba en JS que ポイント名 no estuviera vacío (la comprobación de ポイント数 fallaba);
     * una ポイント数 no numérica rompía el UPDATE. Aquí no se guarda nada en ese caso.
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string'],
            'point' => ['required', 'regex:/^\s*[0-9]+\s*$/'],
        ]);
    }

    private function find(int $cd): PointSetting
    {
        return PointSetting::query()->findOrFail($cd);
    }
}
