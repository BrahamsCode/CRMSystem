<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\PointAggregateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ポイント集計 (category3/point/point_aggregate.php + ajax_point_aggregate.php): formulario de periodo y
 * tablas 付与/使用 por tienda que pinta el JS del legacy con la respuesta JSON.
 */
class PointAggregateController extends Controller
{
    public function index(): View
    {
        return view('admin.promocion.point.point_aggregate');
    }

    /** ajax_point_aggregate.php: siempre 200 con `status` ok/error, como el legacy */
    public function data(Request $request, PointAggregateService $service): JsonResponse
    {
        return response()->json($service->aggregate(
            $this->text($request->input('startDate')),
            $this->text($request->input('endDate')),
        ), options: JSON_UNESCAPED_UNICODE);
    }

    /** GET de la URL del legacy (menú, enlaces) → pantalla nueva */
    public function legacy(): RedirectResponse
    {
        return redirect()->route('admin.promocion.points.aggregate');
    }

    private function text(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
