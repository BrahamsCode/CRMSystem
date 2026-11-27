<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * システム選択 (system/select.php): una tarjeta por módulo activo.
 * No replica los efectos secundarios del legacy en cada carga (sync con los POS, normalizar
 * fechas, insertar tb_koumoku/tb_kengen_contents): ver Riesgos de 00-nucleo-y-base.md.
 */
class SelectController extends Controller
{
    public function __invoke(Request $request, LegacyUi $legacyUi): View
    {
        return view('admin.nucleo.select', [
            'legacyUi' => $legacyUi,
            'modules' => $legacyUi->visibleModules($request->user('admin')),
        ]);
    }
}
