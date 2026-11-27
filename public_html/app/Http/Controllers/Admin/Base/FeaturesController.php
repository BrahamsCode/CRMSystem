<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Services\Base\FeatureSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * 機能設定 (system/category0/config/features_config.php): una sola URL; cada formulario manda su `act`,
 * se guarda y se vuelve a pintar la pantalla (como el legacy, sin redirección).
 */
class FeaturesController extends Controller
{
    public function __invoke(Request $request, FeatureSettingsService $settings): View
    {
        $act = (string) $request->input('act', '');
        // メルマガ配信時のログ solo se muestra desde las IP de desarrollo; el guardado también lo exige
        $allowed = $act !== 'change_sended_log' || Gate::allows('nucleo.developer');
        if ($request->isMethod('post') && in_array($act, FeatureSettingsService::ACTS, true) && $allowed) {
            $settings->apply($act, $request);
        }

        return view('admin.base.features.index', $settings->viewData() + [
            'isDevelop' => Gate::allows('nucleo.developer'),
        ]);
    }
}
