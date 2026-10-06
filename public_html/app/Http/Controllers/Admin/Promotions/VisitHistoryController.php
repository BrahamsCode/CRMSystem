<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Models\Customer;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Historial de visitas por hora y día de la semana (来店履歴一覧) */
class VisitHistoryController extends ModuleController
{
    public const PERIODS = ['today' => 'Hoy', 'month' => 'Este mes', 'year' => 'Este año', 'all' => 'Todo'];

    public function index(Request $request): View
    {
        $period = array_key_exists($request->input('period'), self::PERIODS) ? $request->input('period') : 'month';
        $shopId = $request->integer('shop') ?: $this->shop()?->id;

        $visits = Visit::query()
            ->where('shop_id', $shopId)
            ->when($period !== 'all', fn ($q) => $q->where('visited_at', '>=', match ($period) {
                'today' => today(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            }));

        // Matriz día de la semana × hora; el legacy empieza el día a las 5:00
        $cells = (clone $visits)
            ->selectRaw('extract(dow from visited_at)::int as dow, extract(hour from visited_at)::int as hour, count(*) as total')
            ->groupBy('dow', 'hour')
            ->get();

        $matrix = [];
        foreach ($cells as $c) {
            $matrix[$c->dow][$c->hour] = (int) $c->total;
        }

        $byHour = array_fill(0, 24, 0);
        foreach ($cells as $c) {
            $byHour[$c->hour] += (int) $c->total;
        }

        $customers = Customer::query()
            ->joinSub((clone $visits)->select('customer_id', DB::raw('count(*) as visitas'), DB::raw('max(visited_at) as ultima'))->groupBy('customer_id'), 'v', 'v.customer_id', '=', 'customers.id')
            ->orderByDesc('v.ultima')
            ->select('customers.*', 'v.visitas', 'v.ultima')
            ->paginate(20)
            ->withQueryString();

        return view('admin.promotions.visit-history', [
            'period' => $period,
            'periods' => self::PERIODS,
            'shopId' => $shopId,
            'shops' => $this->shops(),
            'matrix' => $matrix,
            'byHour' => $byHour,
            'total' => (clone $visits)->count(),
            'max' => $cells->max('total') ?? 0,
            'customers' => $customers,
        ]);
    }
}
