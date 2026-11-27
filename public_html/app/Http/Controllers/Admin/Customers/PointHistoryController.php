<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\PointHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ポイント利用一覧 (`system/category1/customer/point_history.php`): buscador, lista paginada (?kensu, 20 por defecto)
 * y CSV (en el legacy, POST csv_print=1 a la misma URL).
 */
class PointHistoryController extends Controller
{
    public function index(Request $request, PointHistoryService $history): View|StreamedResponse
    {
        if ((string) $request->input('csv_print') === '1') {
            return $this->csv($request, $history);
        }

        $filters = $history->filters($request->all());
        $query = $history->query($filters);

        $kensu = (int) $request->input('kensu', 20) ?: 20;
        $total = (clone $query)->count();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, min((int) $request->input('page', 1), max(1, $maxPage)));

        return view('admin.clientes.point-history.index', [
            'filters' => $filters,
            'rows' => $total > 0 ? $query->forPage($page, $kensu)->get() : collect(),
            'total' => $total,
            'page' => $page,
            'maxPage' => $maxPage,
            'kensu' => $kensu,
            'from' => ($page - 1) * $kensu,
            // Shop::getList(): disp_flg = '1' ORDER BY touroku_date DESC
            'shops' => DB::table('tb_shop')->where('disp_flg', '1')->orderByDesc('touroku_date')->get(['cd', 'kaishamei']),
            'yearFrom' => (int) now('Asia/Tokyo')->subYears(2)->format('Y'),
            'yearTo' => (int) now('Asia/Tokyo')->addYear()->format('Y'),
        ]);
    }

    public function csv(Request $request, PointHistoryService $history): StreamedResponse
    {
        return $history->csv($history->filters($request->all()));
    }
}
