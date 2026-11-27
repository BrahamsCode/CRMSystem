<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\RankScheduleService;
use App\Services\Customers\RankService;
use App\Services\Nucleo\LegacyUi;
use App\Services\Reservations\ReservationInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 顧客ランク振り分け設定 (legacy: category1/rank/customer_rank_schedule.php y customer_rank_schedule_register.php).
 * Guarda en admin_master; ya no reescribe el crontab: la tarea la programa ClientesServiceProvider.
 */
class RankScheduleController extends Controller
{
    public function __construct(private RankScheduleService $schedules) {}

    public function edit(): View
    {
        $settings = [];
        foreach (array_keys(RankService::MODELS) as $type) {
            $settings[$type] = $this->schedules->isAvailable($type) ? $this->schedules->settings($type) : null;
        }

        return view('admin.clientes.ranks.schedule', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'kingaku' => $settings[RankService::KINGAKU],
            'raiten' => $settings[RankService::RAITEN],
        ]);
    }

    /** この内容で設定する (con type_flg) / 設定を解除する (solo act) */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'act' => ['required', 'in:' . implode(',', array_keys(RankService::MODELS))],
            'type_flg' => ['nullable', 'in:1,2'],
            'month' => ['nullable', 'array'],
            'month.*' => ['integer', 'between:1,12'],
            'day' => ['nullable', 'array'],
            'day.*' => ['integer', 'between:1,32'],
            'week' => ['nullable', 'array'],
            'week.*' => ['integer', 'between:0,6'],
            'hour' => ['nullable', 'regex:/^\d{1,2}$/'],
            'minute' => ['nullable', 'regex:/^\d{1,2}$/'],
            'rank_shori_kikan' => ['nullable', 'string', 'max:4'],
        ]);

        // form2Db() del legacy sobre todo el $_REQUEST
        $this->schedules->save($data['act'], [
            'type_flg' => (string) ($data['type_flg'] ?? ''),
            'month' => $data['month'] ?? [],
            'day' => $data['day'] ?? [],
            'week' => $data['week'] ?? [],
            'hour' => ReservationInput::form2Db($data['hour'] ?? ''),
            'minute' => ReservationInput::form2Db($data['minute'] ?? ''),
            'rank_shori_kikan' => ReservationInput::form2Db($data['rank_shori_kikan'] ?? ''),
        ]);

        return redirect()->route('admin.clientes.rank-schedules');
    }
}
