<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Models\Sales\ServiceMenu;
use App\Support\LegacyText;
use Illuminate\Http\Request;

/**
 * メニュー管理 (legacy: system/category1/karute_menu/karute_menu.php, karute_menu_register.php,
 * ajax_karute_menu_list.php). Tabla tb_karute_menu.
 */
class ServiceMenuController extends KaruteMasterController
{
    protected function modelClass(): string
    {
        return ServiceMenu::class;
    }

    protected function key(): string
    {
        return 'menus';
    }

    /** karute_menu_register.php: name, kingaku (float), type (texto de form2Db) */
    protected function values(Request $request): array
    {
        $request->validate(['name' => ['required'], 'kingaku' => ['required']], [
            'name.required' => __('ventas.menu_name_required', [], 'ja'),
            'kingaku.required' => __('ventas.menu_kingaku_required', [], 'ja'),
        ]);

        return [
            'name' => LegacyText::db($request->input('name')),
            'kingaku' => (int) (float) LegacyText::db($request->input('kingaku')),
            'type' => LegacyText::db($request->input('type')),
        ];
    }
}
