<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\Reports\CustomerStatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 顧客情報集計 (`customer/customer_sumup_list.php`; `customer_sumup_list2.php` era la versión anterior con Flash
 * y sin filtro de tienda, y abre la misma pantalla). Ventana emergente que abre 集計ツール (tb_shukei_link.url).
 */
class StatisticController extends Controller
{
    public function index(Request $request, CustomerStatisticsService $statistics): View
    {
        $staff = $request->user('admin');
        // No master: solo los clientes de su tienda (`$shop_search` del legacy)
        $shopCd = $staff->isMaster() ? null : (int) $staff->shop_cd;

        return view('admin.clientes.statistics.index', $statistics->report($request->all(), $shopCd) + [
            'input' => [
                'sumup_kind' => trim((string) $request->input('sumup_kind', '')),
                'touroku_date_1' => trim((string) $request->input('touroku_date_1', '')),
                'touroku_date_2' => trim((string) $request->input('touroku_date_2', '')),
                'kensu' => (string) $request->input('kensu', '10') ?: '10',
                'coupon_cd' => (string) $request->input('coupon_cd', ''),
            ],
        ]);
    }
}
