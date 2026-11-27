<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\FieldSettingService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 表示・検索・CSV項目設定 (legacy `customer/form_config.php` y `form_config_register.php`).
 */
class FieldSettingController extends Controller
{
    public function index(FieldSettingService $service): View
    {
        return view('admin.clientes.field-settings.index', $service->viewData());
    }

    /** form_config_register.php: guarda y muestra 「登録中です。」 antes de volver a la matriz (meta Refresh de 2 s) */
    public function update(Request $request, FieldSettingService $service, LegacyUi $legacyUi): View
    {
        $service->update($request->all());

        return view('admin.clientes.settings.processing', [
            'title' => '表示・検索・CSV項目設定 - '.$legacyUi->siteName(),
            'heading' => '■表示・検索・CSV項目設定',
            'message' => '登録中です。',
            'redirectUrl' => route('admin.clientes.field-settings'),
        ]);
    }
}
