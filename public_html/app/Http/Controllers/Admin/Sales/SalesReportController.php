<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Sales\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * スタッフ別集計 y 日報・週報・月報 (legacy: system/category1/karute_menu/staff_total.php, ajax_staffTotal.php,
 * report.php, ajax_report.php). Las pantallas piden el resultado por AJAX ($("#result").load).
 */
class SalesReportController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(private SalesReportService $reports) {}

    /** スタッフ別集計 */
    public function staff(): View
    {
        return view('admin.ventas.reports.staff', $this->screenData());
    }

    /** ajax_staffTotal.php (shop_cd, staff_cd, date1, date2) */
    public function staffData(Request $request): View|Response
    {
        [$period, $error] = $this->reports->period($request->input('date1'), $request->input('date2'));
        if ($error) {
            return response($error); // el legacy hacía die($error)
        }
        $staffCd = (string) $request->input('staff_cd');

        return view('admin.ventas.reports.staff_data', $this->reports->staffTotals($staffCd, $period) + [
            'staff' => ctype_digit($staffCd) ? Staff::query()->find((int) $staffCd) : null,
            'periodLabel' => $period['label'],
        ]);
    }

    /** 日報・週報・月報 */
    public function period(): View
    {
        return view('admin.ventas.reports.period', $this->screenData());
    }

    /** ajax_report.php (shop_cd: -1 = 全店舗, date1, date2). El no master siempre ve su tienda */
    public function periodData(Request $request): View|Response
    {
        [$period, $error] = $this->reports->period($request->input('date1'), $request->input('date2'));
        if ($error) {
            return response($error);
        }
        $shopCd = $this->isMasterUser() ? (int) $request->input('shop_cd') : (int) $this->sessionShopCd();

        return view('admin.ventas.reports.period_data', $this->reports->periodTotals($shopCd, $period) + [
            'shopName' => $shopCd > 0 ? Shop::withDeleted()->whereKey($shopCd)->value('kaishamei') : null,
            'periodLabel' => $period['label'],
        ]);
    }

    private function screenData(): array
    {
        return [
            'isMaster' => $this->isMasterUser(),
            'shops' => $this->isMasterUser() ? $this->shopOptions() : collect(),
            'sessionShopCd' => $this->sessionShopCd(),
            'sessionShopName' => $this->shopName($this->sessionShopCd()),
        ];
    }
}
