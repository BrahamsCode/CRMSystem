<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\PointCsvGrantService;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

/**
 * ポイント一括付与 (category3/point/csv_grant_points.php): ひな形 (`?mode=base_csv`), subida del CSV y resultado
 * en la misma pantalla. El `sessId` del legacy lo sustituye el token CSRF.
 */
class PointGrantController extends Controller
{
    public function index(Request $request): View|Response
    {
        if ($request->query('mode') === 'base_csv') {
            return $this->template();
        }

        return view('admin.promocion.point.csv_grant_points', ['posted' => false]);
    }

    public function store(Request $request, PointCsvGrantService $service, StaffContext $staff): View|Response
    {
        if ($request->input('mode') === 'base_csv') {
            return $this->template();
        }

        $result = ['successIds' => [], 'errorIds' => []];
        $error = null;
        try {
            $file = $request->file('csvFile');
            $result = $service->grant(is_array($file) ? null : $file, (int) $staff->shopCd());
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        return view('admin.promocion.point.csv_grant_points', ['posted' => true, 'error' => $error] + $result);
    }

    /** GET de la URL del legacy (menú, enlaces) → pantalla nueva, conservando `?mode=base_csv` */
    public function legacy(Request $request): RedirectResponse
    {
        return redirect()->route('admin.promocion.points.grant-csv', $request->query());
    }

    /** base.csv en SJIS, como el legacy */
    private function template(): Response
    {
        return response(mb_convert_encoding(PointCsvGrantService::TEMPLATE, 'SJIS', 'UTF-8'), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=base.csv',
        ]);
    }
}
