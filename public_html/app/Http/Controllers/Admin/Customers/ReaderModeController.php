<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\ReaderModeService;
use App\Services\Nucleo\LegacyUi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * カードリーダーモード設定 (legacy `system/category1/config/felica_mode.php`): modo del lector
 * (運用 / 入室強制 / 退室強制) e intervalo entre lecturas, en la fila `cd = 2` de tb_felica_mode.
 * El legacy hacía POST a la misma página; aquí el POST guarda y vuelve a la pantalla.
 */
class ReaderModeController extends Controller
{
    public function __construct(private ReaderModeService $readerMode) {}

    public function edit(): View
    {
        $row = $this->readerMode->row();

        return view('admin.clientes.config.reader-mode', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'mode' => (string) $row?->mode,
            'interval' => (string) $row?->interval_time,
            'intervalMinutes' => ReaderModeService::INTERVAL_MINUTES,
        ]);
    }

    /** felica_mode_up=up (modo) o interval_time_action_act=up (intervalo) */
    public function update(Request $request): RedirectResponse
    {
        if ($request->input('felica_mode_up') === 'up') {
            $this->readerMode->setMode($request->input('felica_mode'));
        }
        if ($request->input('interval_time_action_act') === 'up') {
            $this->readerMode->setInterval($request->input('inter_time'));
        }

        return to_route('admin.clientes.reader-mode');
    }
}
