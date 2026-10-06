<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\Customer;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class VisitController extends ModuleController
{
    public function index(): View
    {
        return view('admin.customers.visits', [
            'todayVisits' => $this->deHoy(),
        ]);
    }

    /**
     * Registra la visita del socio.
     *
     * La pantalla del legacy solo pide el nº de socio: el importe de consumo no
     * se captura aquí, llega desde el POS. Y rechaza la visita si el mismo socio
     * ya pasó hace menos del intervalo configurado en la tienda
     * («来店処理できませんでした。インターバルなどをご確認ください»).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['code' => ['required', 'string']],
            [],
            ['code' => 'nº de socio'],
        );

        $customer = Customer::where('code', $data['code'])->first();

        if (! $customer) {
            return back()->withErrors(['code' => 'No hay ningún cliente con ese número de socio.'])->withInput();
        }

        if ($remaining = $this->segundosRestantes($customer)) {
            return back()
                ->withErrors(['code' => $this->mensajeIntervalo($customer, $remaining)])
                ->withInput();
        }

        // El observer de Visit recalcula las estadísticas del cliente
        Visit::create([
            'customer_id' => $customer->id,
            'shop_id' => $customer->shop_id,
            'visit_motive_id' => $customer->visit_motive_id,
            'visited_at' => now(),
        ]);

        return redirect()
            ->route('admin.customers.visits')
            ->with('visita', [
                'uid' => $customer->uid,
                'name' => $customer->full_name,
                'code' => $customer->code,
                'visits' => $customer->fresh()->visit_count,
            ]);
    }

    /** Segundos que faltan para poder volver a registrar, o null si ya se puede */
    private function segundosRestantes(Customer $customer): ?int
    {
        $interval = (int) ($customer->shop?->visit_interval_seconds ?? 0);

        if ($interval <= 0) {
            return null;
        }

        $last = Visit::where('customer_id', $customer->id)->max('visited_at');

        if (! $last) {
            return null;
        }

        $elapsed = (int) now()->diffInSeconds($last);

        return $elapsed < $interval ? $interval - $elapsed : null;
    }

    private function mensajeIntervalo(Customer $customer, int $seconds): string
    {
        $restante = $seconds >= 3600
            ? round($seconds / 3600, 1) . ' h'
            : max(1, (int) ceil($seconds / 60)) . ' min';

        return "{$customer->full_name} ya tiene una visita registrada hace poco. "
            . "Faltan {$restante} para poder volver a registrarla.";
    }

    private function deHoy()
    {
        return Visit::with('customer')
            ->whereDate('visited_at', now())
            ->orderByDesc('visited_at')
            ->get();
    }
}
