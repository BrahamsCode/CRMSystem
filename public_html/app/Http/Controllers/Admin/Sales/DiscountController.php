<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Models\Sales\Discount;
use App\Support\LegacyText;
use Illuminate\Http\Request;

/**
 * 割引管理 (legacy: system/category1/karute_menu/karute_waribiki.php, karute_waribiki_register.php,
 * ajax_karute_waribiki_list.php). Tabla tb_karute_waribiki.
 */
class DiscountController extends KaruteMasterController
{
    protected function modelClass(): string
    {
        return Discount::class;
    }

    protected function key(): string
    {
        return 'discounts';
    }

    /** karute_waribiki_register.php: name, value (float), type (1 ％ / 2 円), buai_flg (1 含む / 2 含まない) */
    protected function values(Request $request): array
    {
        $request->validate(['name' => ['required'], 'value' => ['required']], [
            'name.required' => __('ventas.menu_name_required', [], 'ja'),
            'value.required' => __('ventas.discount_value_required', [], 'ja'),
        ]);

        return [
            'name' => LegacyText::db($request->input('name')),
            'value' => (int) (float) LegacyText::db($request->input('value')),
            'type' => LegacyText::db($request->input('type')),
            'buai_flg' => LegacyText::db($request->input('buai_flg')),
        ];
    }
}
