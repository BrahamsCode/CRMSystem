<?php

namespace App\Http\Controllers\Admin\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Ventas\SalesReportService;
use App\Services\Ventas\VentasShop;
use App\Support\LegacyCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * スタッフ別集計 (staff_total.php + ajax_staffTotal.php) y 日報・週報・月報 (report.php + ajax_report.php).
 * Las pantallas cargan el resultado por AJAX en las URL del legacy; el CSV (SJIS-win) es nuevo.
 */
class SalesReportController extends Controller
{
    public function __construct(private SalesReportService $reports, private VentasShop $shop) {}

    /** staff_total.php */
    public function staff(): View
    {
        return view('admin.ventas.reports.staff', [
            'isMaster' => $this->shop->isMaster(),
            'shops' => $this->shop->options(),
            'sessionShop' => $this->shop->sessionShop(),
            'sessionShopName' => $this->shop->name($this->shop->sessionShop()),
        ]);
    }

    /** ajax_staffTotal.php: si el periodo no vale, solo el mensaje (el legacy hacía die()) */
    public function staffData(Request $request): Response
    {
        $period = $this->reports->period($request->input('date1'), $request->input('date2'));
        if (isset($period['error'])) {
            return response($period['error']);
        }
        $staffCd = $this->allowedStaff($request->input('staff_cd'));

        return response()->view('admin.ventas.reports.staff-data', [
            'report' => $this->reports->staffTotal($staffCd ?? 0, $period),
            'period' => $period,
            'csvUrl' => route('admin.ventas.reports.staff.csv', $request->only('shop_cd', 'staff_cd', 'date1', 'date2')),
        ]);
    }

    /** CSV de スタッフ別集計 */
    public function staffCsv(Request $request): StreamedResponse|Response
    {
        $period = $this->reports->period($request->input('date1'), $request->input('date2'));
        if (isset($period['error'])) {
            return response($period['error'], 422);
        }
        $report = $this->reports->staffTotal($this->allowedStaff($request->input('staff_cd')) ?? 0, $period);

        return LegacyCsv::download('staff_total_'.now()->format('YmdHis').'.csv', $this->reports->staffCsvRows($report, $period));
    }

    /** report.php */
    public function period(): View
    {
        return view('admin.ventas.reports.period', [
            'isMaster' => $this->shop->isMaster(),
            'shops' => $this->shop->options(),
            'sessionShop' => $this->shop->sessionShop(),
            'sessionShopName' => $this->shop->name($this->shop->sessionShop()),
        ]);
    }

    /** ajax_report.php */
    public function periodData(Request $request): Response
    {
        $period = $this->reports->period($request->input('date1'), $request->input('date2'));
        if (isset($period['error'])) {
            return response($period['error']);
        }

        return response()->view('admin.ventas.reports.period-data', [
            'report' => $this->reports->periodReport($this->reportShop($request), $period),
            'period' => $period,
            'csvUrl' => route('admin.ventas.reports.period.csv', $request->only('shop_cd', 'date1', 'date2')),
        ]);
    }

    /** CSV de 日報・週報・月報 */
    public function periodCsv(Request $request): StreamedResponse|Response
    {
        $period = $this->reports->period($request->input('date1'), $request->input('date2'));
        if (isset($period['error'])) {
            return response($period['error'], 422);
        }
        $report = $this->reports->periodReport($this->reportShop($request), $period);

        return LegacyCsv::download('report_'.now()->format('YmdHis').'.csv', $this->reports->periodCsvRows($report, $period));
    }

    /** staff_total.php del legacy → pantalla nueva */
    public function legacyStaff(): RedirectResponse
    {
        return redirect()->route('admin.ventas.reports.staff');
    }

    /** report.php del legacy → pantalla nueva */
    public function legacyPeriod(): RedirectResponse
    {
        return redirect()->route('admin.ventas.reports.period');
    }

    /** Tienda del informe: master elige (-1 = 全店舗); el resto, siempre la suya */
    private function reportShop(Request $request): ?int
    {
        if (! $this->shop->isMaster()) {
            return $this->shop->sessionShop();
        }

        return is_numeric($request->input('shop_cd')) ? (int) $request->input('shop_cd') : null;
    }

    /** スタッフ a consultar: un no master solo las de su tienda */
    private function allowedStaff(mixed $staffCd): ?int
    {
        if (! is_numeric($staffCd)) {
            return null;
        }
        if ($this->shop->isMaster()) {
            return (int) $staffCd;
        }

        return Staff::withDeleted()->whereKey((int) $staffCd)->where('shop_cd', $this->shop->sessionShop())->exists() ? (int) $staffCd : null;
    }
}
