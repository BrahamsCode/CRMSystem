<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Visit;

/**
 * Mantiene al día las estadísticas que `customers` guarda desnormalizadas.
 *
 * El esquema original lo resolvía con un trigger de PostgreSQL, pero aquel solo
 * se disparaba en INSERT: corregir el importe de una visita o borrarla dejaba al
 * cliente con cifras equivocadas para siempre. Aquí se cubren los tres casos.
 */
class VisitObserver
{
    public function created(Visit $visit): void
    {
        $this->recalcular($visit->customer_id);
    }

    public function updated(Visit $visit): void
    {
        $this->recalcular($visit->customer_id);

        // Si la visita cambió de cliente, hay que rehacer también el anterior
        if ($visit->wasChanged('customer_id')) {
            $this->recalcular($visit->getOriginal('customer_id'));
        }
    }

    public function deleted(Visit $visit): void
    {
        $this->recalcular($visit->customer_id);
    }

    public function restored(Visit $visit): void
    {
        $this->recalcular($visit->customer_id);
    }

    private function recalcular(?int $customerId): void
    {
        $customer = $customerId ? Customer::find($customerId) : null;

        if (! $customer) {
            return;
        }

        $visitas = Visit::where('customer_id', $customer->id)->get();
        $fechas = $visitas->pluck('visited_at')->filter()->sort()->values();

        $customer->forceFill([
            'visit_count' => $visitas->count(),
            'total_amount' => (int) $visitas->sum('amount'),
            'average_amount' => $visitas->isEmpty() ? 0 : (int) round($visitas->avg('amount')),
            'last_visit_date' => $fechas->last()?->toDateString(),
            'average_visit_cycle' => $this->cicloMedio($fechas),
        ]);

        $customer->next_visit_date = $customer->last_visit_date && $customer->average_visit_cycle
            ? $customer->last_visit_date->copy()->addDays($customer->average_visit_cycle)->toDateString()
            : null;

        $customer->save();
    }

    /** Días que pasan de media entre una visita y la siguiente */
    private function cicloMedio($fechas): ?int
    {
        if ($fechas->count() < 2) {
            return null;
        }

        return (int) round($fechas->first()->diffInDays($fechas->last()) / ($fechas->count() - 1));
    }
}
