<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Sales\SaleCustomerSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * AJAX de apoyo de ventas internas (legacy: system/category1/karute_menu/ajax_search_kaiin.php, ajax_staff.php).
 * Las listas de メニュー/割引 (ajax_karute_*_list.php) están en ServiceMenuController/DiscountController@list.
 */
class OptionController extends Controller
{
    use SelectsLegacyShop;

    /** 会員検索 del formulario de venta: filas <tr> (máx. 200) */
    public function customers(Request $request, SaleCustomerSearch $search): View
    {
        // El legacy filtraba por el shop_cd del formulario si no es master; aquí, la tienda de la sesión
        $shopCd = $this->isMasterUser() ? null : $this->sessionShopCd();

        return view('admin.ventas.options.customers', [
            'customers' => $search->search((string) $request->input('keyword', ''), $shopCd),
        ]);
    }

    /** <option> de スタッフ de una tienda (orden common_flg, rank, cd) */
    public function staff(Request $request): View|string
    {
        $shopCd = $this->isMasterUser() ? (int) $request->input('shop_cd') : (int) $this->sessionShopCd();
        $staff = Staff::query()->where('disp_flg', '1')->where('shop_cd', $shopCd)
            ->orderByRaw("coalesce(common_flg, '')")->orderBy('rank')->orderBy('cd')->get(['cd', 'name']);

        return view('admin.ventas.options.staff', [
            'staff' => $staff,
            // manageWord("スタッフ", "c9_yoyaku[スタッフ]"): el texto por defecto lo pone la vista
            'staffWord' => DB::table('tb_manage_words')->where('content_name', 'c9_yoyaku[スタッフ]')->value('naiyou'),
        ]);
    }
}
