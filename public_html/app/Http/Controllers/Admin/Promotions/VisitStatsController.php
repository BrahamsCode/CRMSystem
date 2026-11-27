<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\StaffContext;
use App\Services\Promotions\VisitStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 来店履歴管理 (system/category3/visit/): 来店履歴一覧 (visit_stats2.php), 来店履歴一覧 日別 (day_visit_list.php)
 * y 個別来店履歴 (store_visit_list.php). Como el legacy, la entrada directa no pide permiso (el menú muestra
 * 来店履歴一覧 con kengen 1012-1).
 */
class VisitStatsController extends Controller
{
    public function __construct(
        private readonly StaffContext $staff,
        private readonly VisitStatsService $stats,
    ) {}

    /** visit_stats2.php */
    public function index(Request $request): View|RedirectResponse
    {
        $shops = $this->stats->shops($this->staff->isMaster(), $this->staff->shopCd());
        if ($shops->isEmpty()) {
            return redirect()->route('logout');
        }

        return view('admin.promocion.visit.visit_stats2', $this->stats->stats($request->all(), $this->staff->isMaster(), $this->staff->shopCd()) + [
            'shops' => $shops,
            'isMaster' => $this->staff->isMaster(),
            'periods' => VisitStatsService::STATS_PERIODS,
            'submitted' => $request->has('submit'),
        ]);
    }

    /** day_visit_list.php */
    public function day(Request $request): View|RedirectResponse
    {
        $shops = $this->stats->shops($this->staff->isMaster(), $this->staff->shopCd());
        if ($shops->isEmpty()) {
            return redirect()->route('logout');
        }

        return view('admin.promocion.visit.day_visit_list', $this->stats->day($request->all(), $this->staff->isMaster(), $this->staff->shopCd()) + [
            'shops' => $shops,
            'isMaster' => $this->staff->isMaster(),
            'periods' => VisitStatsService::LIST_PERIODS,
            'requestShopCd' => (string) $request->input('shop_cd', ''),
            'submitted' => $request->has('submit'),
        ]);
    }

    /** store_visit_list.php: solo calcula al pulsar 表示 (submit); todas las tiendas, como el legacy */
    public function shop(Request $request): View
    {
        $kikan = in_array((string) $request->input('kikan'), ['0', '1', '2'], true) ? (int) $request->input('kikan') : 0;
        $now = now()->getTimestamp();
        $data = null;
        $visitors = collect();
        if ($request->has('submit')) {
            $data = $this->stats->shopTable($kikan, $now);
            $visitors = $this->stats->visitors(null, $data['begin'], $data['end'])->get();
        }

        return view('admin.promocion.visit.store_visit_list', [
            'kikan' => $kikan,
            'periods' => VisitStatsService::LIST_PERIODS,
            'data' => $data,
            'visitors' => $visitors,
            'selectedDay' => $now,
            'submitted' => $request->has('submit'),
        ]);
    }

    /** GET de la URL del legacy (menú, marcadores): misma pantalla con la misma query */
    public function legacy(Request $request, string $target): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to(route($target).($query ? '?'.$query : ''));
    }
}
