<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operaciones\StaffShiftKindRequest;
use App\Models\Operaciones\StaffShiftKind;
use App\Services\Operaciones\LegacyRanking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * シフト区分管理: la sección de kintai/config.php (各種設定, ⏸ con el resto de 勤怠) + staff_shift_kind_register.php,
 * como pantalla propia porque los turnos (スケジュール管理) la necesitan. Nombre, color (ColorPicker del legacy) y ▲▼.
 */
class StaffShiftKindController extends Controller
{
    public function __construct(private readonly LegacyRanking $ranking)
    {
    }

    /** Alta arriba (o edición con ?cd=) y lista abajo */
    public function index(Request $request): View
    {
        return view('admin.operaciones.shift-kinds.index', [
            'kind' => $request->filled('cd') ? StaffShiftKind::find((int) $request->query('cd')) : null,
            'kinds' => StaffShiftKind::ordered()->get(),
        ]);
    }

    public function store(StaffShiftKindRequest $request): RedirectResponse
    {
        DB::transaction(fn () => StaffShiftKind::create([
            'name' => $request->validated('name'),
            'color' => (string) $request->validated('color'),
            'rank' => $this->ranking->nextRank(StaffShiftKind::class),
            'disp_flg' => StaffShiftKind::ACTIVE_FLAG,
        ]));

        return to_route('admin.operaciones.shift-kinds.index');
    }

    public function update(StaffShiftKindRequest $request, int $cd): RedirectResponse
    {
        StaffShiftKind::findOrFail($cd)->update(['name' => $request->validated('name'), 'color' => (string) $request->validated('color')]);

        return to_route('admin.operaciones.shift-kinds.index');
    }

    public function destroy(int $cd): RedirectResponse
    {
        StaffShiftKind::find($cd)?->delete();

        return to_route('admin.operaciones.shift-kinds.index');
    }

    /** ▲▼ (?thisCd=&kind=up|down) */
    public function sort(Request $request): RedirectResponse
    {
        $cd = (string) $request->query('thisCd', '');
        if (preg_match('/^[0-9]+$/', $cd)) {
            $this->ranking->move(StaffShiftKind::class, (int) $cd, $request->query('kind') === 'up' ? 'up' : 'down');
        }

        return to_route('admin.operaciones.shift-kinds.index');
    }
}
