<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Models\Senalizacion\SignageWebContent;
use App\Models\Senalizacion\SignageWebContentCategory;
use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Services\Senalizacion\SignageCmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * signage/list/cycle.php: ciclos de un paquete (状態, 表示方法, 表示フレーム del diseño, 画像登録).
 * Las filas del marco salen de los fragmentos de diseño (designs/inc, inc_flame_cd, inc_js).
 */
class CycleController extends SignageController
{
    public function edit(Request $request, int $packagesCd, SignageCmsService $cms): Response
    {
        $package = SignageWebPackage::query()->toBase()->where('cd', $packagesCd)->first();
        $cd = (int) $request->query('cd');
        $cycle = $cd > 0 ? SignageWebCycle::where('cd', $cd)->where('packages_cd', $packagesCd)->where('flg', 1)->first() : null;
        $categories = SignageWebContentCategory::query()->toBase()->where('flg', 1)->orderBy('cd')->get()->map(fn ($r) => (array) $r);

        return $this->legacyView->screen($request, 'cycle', [
            '_request' => ['packages_cd' => $packagesCd] + $request->query(),
            'data' => SignageWebCycle::query()->toBase()->where('packages_cd', $packagesCd)->where('flg', 1)->orderBy('cd')->get()
                ->map(fn ($r) => (array) $r)->all(),
            '_packages_data' => $package ? (array) $package : [],
            '_contents_category_data' => $categories->all(),
            '_category_by_cd' => $categories->keyBy('cd')->all(),
            '_contents_data' => SignageWebContent::query()->toBase()->where('flg', 1)->orderBy('contents_category_cd')->orderBy('cd')->get()
                ->map(fn ($r) => (array) $r)->all(),
            '_detail_data' => $cycle ? $cms->cycleDetail($cycle) : ['name' => '', 'schedule_repeat_type' => 0, 'flg' => 1],
            '_form_action' => $cycle
                ? route('admin.senalizacion.cycles.update', [$packagesCd, $cycle->cd])
                : route('admin.senalizacion.cycles.store', $packagesCd),
        ]);
    }

    /** Sin ciclo elegido el legacy creaba un ciclo suelto (sin paquete); aquí se crea en el paquete */
    public function store(Request $request, int $packagesCd, SignageCmsService $cms): RedirectResponse
    {
        $package = SignageWebPackage::findOrFail($packagesCd);
        $cycle = $cms->saveCycle(new SignageWebCycle(['packages_cd' => $package->cd, 'flg' => 1]), $request->post());

        return $this->done(route('admin.senalizacion.cycles.edit', [$packagesCd, 'cd' => $cycle->cd]), self::SAVED);
    }

    public function update(Request $request, int $packagesCd, int $cd, SignageCmsService $cms): RedirectResponse
    {
        $cycle = SignageWebCycle::where('packages_cd', $packagesCd)->findOrFail($cd);
        $cms->saveCycle($cycle, $request->post());

        return $this->done(route('admin.senalizacion.cycles.edit', [$packagesCd, 'cd' => $cd]), self::SAVED);
    }
}
