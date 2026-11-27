<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Ventas\SaleService;
use App\Services\Ventas\VentasShop;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/** Utilidades AJAX de karute_menu: ajax_search_kaiin.php (会員検索) y ajax_staff.php (<option> de スタッフ) */
class SaleOptionController extends Controller
{
    public function __construct(private SaleService $sales, private VentasShop $shop) {}

    /** ajax_search_kaiin.php: filas <tr> del buscador del formulario de 売上データ作成 */
    public function searchMembers(Request $request): Response
    {
        $shopCd = $this->shop->isMaster() ? (int) $request->input('shop_cd') : $this->shop->sessionShop();

        return response()->view('admin.ventas.ajax.search-kaiin', [
            'members' => $this->sales->searchMembers(LegacyText::db($request->input('keyword')), $shopCd, $this->shop->isMaster()),
        ]);
    }

    /** ajax_staff.php: <option> de las スタッフ de la tienda (ORDER BY common_flg, rank, cd) */
    public function staffOptions(Request $request): Response
    {
        $shopCd = $this->shop->isMaster() ? (int) $request->input('shop_cd') : (int) $this->shop->sessionShop();
        $staff = Staff::query()
            ->where('disp_flg', '1')
            ->where('shop_cd', $shopCd)
            ->orderByRaw("coalesce(common_flg, '')")->orderBy('rank')->orderBy('cd')
            ->get(['cd', 'name']);

        // manageWord("スタッフ", "c9_yoyaku[スタッフ]")
        $word = DB::table('tb_manage_words')->where('content_name', 'c9_yoyaku[スタッフ]')->value('naiyou');

        return response()->view('admin.ventas.ajax.staff-options', [
            'staff' => $staff,
            'staffWord' => filled($word) ? (string) $word : 'スタッフ',
        ]);
    }
}
