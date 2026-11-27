<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use App\Services\Senalizacion\SignageCmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** signage/list/terminal.php: 端末一覧 (alta, cambio con ?cd= y borrado) */
class TerminalController extends SignageController
{
    public function index(Request $request): Response
    {
        $cd = (int) $request->query('cd');
        $detail = $cd > 0 ? SignageWebTerminal::query()->toBase()->where('cd', $cd)->first() : null;

        return $this->legacyView->screen($request, 'terminal', [
            'data' => SignageWebTerminal::query()->toBase()->orderBy('cd')->get()->map(fn ($r) => (array) $r)->all(),
            '_packages_data' => SignageWebPackage::query()->toBase()->where('flg', 1)->orderBy('cd')->get()
                ->mapWithKeys(fn ($r) => [$r->cd => (array) $r])->all(),
            '_detail_data' => $detail ? (array) $detail : ['name' => '', 'flg' => 1],
            '_form_action' => $detail ? route('admin.senalizacion.terminals.update', $cd) : route('admin.senalizacion.terminals.store'),
        ]);
    }

    public function store(Request $request, SignageCmsService $cms): RedirectResponse
    {
        $cms->saveTerminal($request->post());

        return $this->done(route('admin.senalizacion.terminals.index'), self::SAVED);
    }

    public function update(Request $request, int $cd, SignageCmsService $cms): RedirectResponse
    {
        $cms->saveTerminal($request->post(), SignageWebTerminal::findOrFail($cd));

        return $this->done(route('admin.senalizacion.terminals.index', ['cd' => $cd]), self::SAVED);
    }

    public function destroy(int $cd): RedirectResponse
    {
        SignageWebTerminal::findOrFail($cd)->delete();

        return $this->done(route('admin.senalizacion.terminals.index'), self::DELETED);
    }
}
