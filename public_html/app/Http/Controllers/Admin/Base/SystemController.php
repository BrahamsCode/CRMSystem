<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Services\Base\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * システム管理 (system/category0/config/system.php): una URL con un formulario por bloque (`act` o `act_flg`).
 * Sin 初期化 (init_all) ni エクスポート (sincronía de BD/archivos con otros servidores): ver Doc/diseno/nucleo-base.md.
 */
class SystemController extends Controller
{
    public function __invoke(Request $request, SystemSettingsService $settings): View|RedirectResponse|Response
    {
        $act = (string) ($request->input('act') ?: $request->input('act_flg', ''));
        // マイコレアプリ使用フラグ solo se ve desde las IP de desarrollo; el guardado también lo exige
        $allowed = $act !== 'application_use_flg' || Gate::allows('nucleo.developer');

        if ($request->isMethod('post') && $act !== '' && $allowed) {
            $message = $settings->apply($act, $request);
            if ($message !== null) {
                return response($message);
            }
            if (isset(SystemSettingsService::ANCHORS[$act])) {
                return redirect('/system/category0/config/system.php#' . SystemSettingsService::ANCHORS[$act]);
            }
        }

        return view('admin.base.system.index', $settings->viewData() + ['isDevelop' => Gate::allows('nucleo.developer')]);
    }
}
