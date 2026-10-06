<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatisticController extends ModuleController
{
    public function index(): View
    {
        $from = now()->copy()->subMonths(11)->startOfMonth();
        $to = now()->endOfMonth();

        $months = $this->altasPorMes($from, $to);
        $total = array_sum(array_column($months, 'valor'));

        return view('admin.customers.statistics', [
            'shops' => Shop::active()->orderBy('name')->get(),
            'from' => $from,
            'to' => $to,
            'months' => $months,
            'total' => $total,
            'kpis' => $this->kpis($months, $total),
            'byShop' => $this->porTienda($from, $to, $total),
        ]);
    }

    /**
     * Altas por mes del periodo, incluidos los meses sin ninguna: agrupar en SQL
     * solo devuelve los meses con filas, y el gráfico necesita los 12 huecos.
     */
    private function altasPorMes(Carbon $from, Carbon $to): array
    {
        // pluck() no admite expresiones crudas como columna de valor: hay que
        // seleccionar con alias y construir el mapa después.
        $conteos = Customer::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("to_char(created_at, 'YYYY-MM') as mes, count(*) as altas")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('altas', 'mes');

        $months = [];
        $cursor = $from->copy();

        while ($cursor <= $to) {
            $clave = $cursor->format('Y-m');

            $months[] = [
                'key' => $clave,
                'short' => ucfirst($cursor->translatedFormat('M')),
                'full' => ucfirst($cursor->translatedFormat('F Y')),
                'value' => (int) ($conteos[$clave] ?? 0),
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    private function kpis(array $months, int $total): array
    {
        $values = array_column($months, 'valor');
        $pico = $values ? max($values) : 0;
        $mesPico = $months[array_search($pico, $values, true)] ?? null;

        return [
            ['Altas en el periodo', number_format($total, 0, ',', '.'), 'Últimos 12 meses'],
            ['Media mensual', $months ? (string) (int) round($total / count($months)) : '0', 'altas por mes'],
            ['Mes con más altas', $mesPico['full'] ?? '—', $pico . ' altas'],
            ['Tienda principal', $this->tiendaPrincipal(), 'por nº de altas'],
        ];
    }

    private function tiendaPrincipal(): string
    {
        return Customer::query()
            ->join('shops', 'shops.id', '=', 'customers.shop_id')
            ->groupBy('shops.name')
            ->orderByRaw('count(*) desc')
            ->value('shops.name') ?? '—';
    }

    private function porTienda(Carbon $from, Carbon $to, int $total): array
    {
        return Customer::query()
            ->join('shops', 'shops.id', '=', 'customers.shop_id')
            ->whereBetween('customers.created_at', [$from, $to])
            ->groupBy('shops.name')
            ->orderByRaw('count(*) desc')
            ->get([DB::raw('shops.name'), DB::raw('count(*) as altas')])
            ->map(fn ($row) => [
                'name' => $row->name,
                'value' => (int) $row->altas,
                'pct' => $total > 0 ? (int) round($row->altas / $total * 100) : 0,
            ])
            ->all();
    }
}
