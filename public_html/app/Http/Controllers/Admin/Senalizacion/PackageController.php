<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use App\Services\Senalizacion\SignageCmsService;
use App\Services\Senalizacion\SignagePlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** signage/list/packages.php: パッケージ一覧 (crea y ajusta los ciclos) y view/packages.php (vista previa) */
class PackageController extends SignageController
{
    public function index(Request $request, SignageCmsService $cms): Response
    {
        $cd = (int) $request->query('cd');
        $detail = $cd > 0 ? SignageWebPackage::query()->toBase()->where('cd', $cd)->first() : null;

        return $this->legacyView->screen($request, 'packages', [
            'data' => SignageWebPackage::query()->toBase()->orderBy('cd')->get()->map(fn ($r) => (array) $r)->all(),
            '_terminal_data' => SignageWebTerminal::query()->toBase()->where('flg', 1)->orderBy('cd')->get()
                ->mapWithKeys(fn ($r) => [$r->cd => (array) $r])->all(),
            '_first_cycle_cd' => $cms->firstCycleCds(),
            '_detail_data' => $detail ? (array) $detail : ['name' => '', 'flg' => 1],
            '_form_action' => $detail ? route('admin.senalizacion.packages.update', $cd) : route('admin.senalizacion.packages.store'),
        ]);
    }

    public function store(Request $request, SignageCmsService $cms): RedirectResponse
    {
        $cms->savePackage($request->post());

        return $this->done(route('admin.senalizacion.packages.index'), self::SAVED);
    }

    public function update(Request $request, int $cd, SignageCmsService $cms): RedirectResponse
    {
        $cms->savePackage($request->post(), SignageWebPackage::findOrFail($cd));

        return $this->done(route('admin.senalizacion.packages.index', ['cd' => $cd]), self::SAVED);
    }

    /** Como el legacy, los ciclos del paquete se quedan (no los ve nadie) */
    public function destroy(int $cd): RedirectResponse
    {
        SignageWebPackage::findOrFail($cd)->delete();

        return $this->done(route('admin.senalizacion.packages.index'), self::DELETED);
    }

    /** view/packages.php?cd=: el reproductor de un paquete, para los iframes de 端末一覧 y パッケージ一覧 */
    public function preview(Request $request, SignagePlayer $player): Response
    {
        $data = $player->forPackage((int) $request->query('cd'));
        if (is_string($data)) {
            return response($data);
        }
        $view = $this->legacyView->playerView($data['_packages_data']['display_type'] ?? null);

        return response($view ? $this->legacyView->render($view, $this->legacyView->common($request) + $data) : '現在、準備中です。');
    }
}
