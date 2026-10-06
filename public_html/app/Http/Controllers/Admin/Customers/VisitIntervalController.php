<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Intervalo entre lecturas del mismo socio (カード重複読込時間間隔).
 *
 * En el sistema legacy esta pantalla vive en el módulo de promociones, bajo
 * «来店履歴管理 › カードリーダ設定». Aquí se trae al módulo de clientes, que es
 * donde se registran las visitas y donde el ajuste tiene efecto.
 */
class VisitIntervalController extends ModuleController
{
    public function index(): View
    {
        return view('admin.customers.visit-interval', [
            'shops' => Shop::active()->orderBy('name')->get(),
            'options' => $this->opciones(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'intervals' => ['required', 'array'],
            'intervalos.*' => ['required', 'integer', 'in:' . implode(',', array_keys($this->opciones()))],
        ]);

        foreach ($data['intervals'] as $shopId => $seconds) {
            Shop::where('id', $shopId)->update(['visit_interval_seconds' => (int) $seconds]);
        }

        return redirect()
            ->route('admin.customers.visit-interval')
            ->with('status', 'Intervalos actualizados.');
    }

    /**
     * Las mismas opciones que ofrece el legacy: sin intervalo, media hora y
     * luego de hora en hora hasta 24.
     */
    private function opciones(): array
    {
        $options = [
            0 => 'Sin intervalo',
            1800 => '30 minutos',
        ];

        for ($h = 1; $h <= 24; $h++) {
            $options[$h * 3600] = $h === 1 ? '1 hora' : "{$h} horas";
        }

        return $options;
    }
}
