<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Enums\RankScheduleMode;
use App\Enums\RankType;
use App\Models\RankSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/** Asignación automática de rangos */
class RankScheduleController extends ModuleController
{
    public function index(): View
    {
        $guardadas = RankSchedule::where('shop_id', $this->shop()?->id)
            ->get()
            ->keyBy(fn (RankSchedule $r) => $r->type->value);

        return view('admin.customers.rank-schedules', [
            'config' => collect(RankType::cases())
                ->mapWithKeys(fn (RankType $t) => [
                    $t->name === 'Amount' ? 'importe' : 'visits' => $this->estado($guardadas->get($t->value)),
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', new Enum(RankType::class)],
            'enabled_flg' => ['required', 'integer', 'in:0,1'],
            'period_days' => ['nullable', 'integer', 'min:1'],
            'mode' => ['required', new Enum(RankScheduleMode::class)],
            'months' => ['array'],
            'months.*' => ['integer', 'between:1,12'],
            'days' => ['array'],
            'days.*' => ['string'],
            'weekdays' => ['array'],
            'weekdays.*' => ['string'],
            'run_hour' => ['required', 'integer', 'between:0,23'],
            'run_minute' => ['required', 'integer', 'between:0,59'],
        ]);

        RankSchedule::updateOrCreate(
            ['shop_id' => $this->shop()?->id, 'type' => $data['type']],
            $data,
        );

        $activada = (int) $data['enabled_flg'] === 1;

        return redirect()
            ->route('admin.customers.rank-schedules')
            ->with('status', $activada
                ? 'Asignación guardada y activada.'
                : 'Asignación desactivada.');
    }

    /** Valores que consume Alpine, con los defectos cuando aún no hay fila guardada */
    private function estado(?RankSchedule $regla): array
    {
        return [
            'enabled' => $regla?->enabled_flg === 1,
            'mode' => $regla?->mode?->value ?? RankScheduleMode::ByDate->value,
            'periodDays' => $regla?->period_days ?? '',
            'months' => $regla?->months ?? [],
            'days' => $regla?->days ?? [],
            'weekdays' => $regla?->weekdays ?? [],
            'hour' => $regla?->run_hour ?? 0,
            'minute' => $regla?->run_minute ?? 0,
        ];
    }
}
