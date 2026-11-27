<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\StaffShift;
use App\Models\Operaciones\StaffShiftKind;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\LegacyMonth;
use App\Services\Operaciones\ShiftBoard;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * スケジュール管理: 日別シフト管理 (schedule/staff_shift.php). Tablero por empleado con franjas de 15 min y
 * el JS del legacy (add.shift.js: arrastrar, modal de 設定, 印刷).
 * Diferencias: el master puede elegir cualquier tienda (el legacy volvía siempre a la primera), el horario sale
 * de la tienda elegida y, sin kintai_tb_config, se usan sus valores por defecto en lugar de redirigir a 勤怠一覧 (⏸).
 */
class StaffShiftController extends Controller
{
    public function __construct(private readonly ShiftBoard $board)
    {
    }

    public function day(Request $request): View
    {
        $shops = $this->shops();
        $shopCd = $this->selectedShop($request, $shops);
        $day = $this->parseDay($request->query('d'));
        $hours = $this->board->hours($shopCd);
        $slots = $this->board->slots($day, $hours);
        $staff = $this->staffOf($shopCd);
        $rows = StaffShift::query()->where('shop_cd', $shopCd)->forMonth(LegacyMonth::of($day->year, $day->month))->get();

        $today = now(LegacyMonth::TIMEZONE);

        return view('admin.operaciones.shifts.day', [
            'day' => $day,
            'd' => $day->format('Ymd'),
            'shops' => $shops,
            'shopCd' => $shopCd,
            'staff' => $staff,
            'slots' => $slots,
            'hours' => $hours,
            'shifts' => $this->board->dayShifts($rows, $day),
            'kinds' => StaffShiftKind::ordered()->get(['cd', 'name', 'color']),
            // Los 3 calendarios del legacy: mes anterior, actual y siguiente respecto de hoy
            'calendars' => [$today->copy()->startOfMonth()->subMonth(), $today->copy()->startOfMonth(), $today->copy()->startOfMonth()->addMonth()],
            'print' => $request->has('print'),
        ]);
    }

    /** 更新 (mode=update): los turnos del día de todos los empleados activos de la tienda */
    public function save(Request $request): RedirectResponse
    {
        $shopCd = $this->selectedShop($request, $this->shops());
        $day = $this->parseDay($request->input('d'));
        $values = array_values(array_filter((array) $request->input('sch', []), 'is_string'));

        $this->board->saveDay($shopCd, $day, $values, $this->staffOf($shopCd)->pluck('cd')->all());

        return to_route('admin.operaciones.shifts.day', ['shop_cd' => $shopCd, 'd' => $day->format('Ymd')]);
    }

    /**
     * 月別シフト管理 (schedule/staff_shift_month.php): un empleado, una fila por día del mes (el legacy pintaba 31
     * filas en todos los meses; aquí solo los días que existen). Sin staff_cd elige el primero.
     */
    public function month(Request $request): View
    {
        $shops = $this->shops();
        $shopCd = $this->selectedShop($request, $shops);
        $first = $this->parseDay($request->query('d') ?? now(LegacyMonth::TIMEZONE)->format('Ym01'));
        $month = LegacyMonth::of($first->year, $first->month);
        $hours = $this->board->hours($shopCd);
        $staff = $this->staffOf($shopCd);
        $staffCd = $request->has('staff_cd') ? (int) $request->query('staff_cd') : (int) $staff->first()?->cd;
        $member = $staff->firstWhere('cd', $staffCd);
        $row = $member ? StaffShift::query()->where('shop_cd', $shopCd)->where('staff_cd', $staffCd)->forMonth($month)->first() : null;
        $thisMonth = now(LegacyMonth::TIMEZONE)->startOfMonth();

        return view('admin.operaciones.shifts.month', [
            'day' => $first,
            'month' => $month,
            'd' => $first->format('Ymd'),
            'shops' => $shops,
            'shopCd' => $shopCd,
            'staff' => $staff,
            'staffCd' => $staffCd,
            'member' => $member,
            'slots' => $this->board->slots($first, $hours),
            'hours' => $hours,
            'shifts' => $this->board->monthShifts($row, $month),
            'kinds' => StaffShiftKind::ordered()->get(['cd', 'name', 'color']),
            'months' => array_map(fn (int $i) => $thisMonth->copy()->addMonths($i), range(0, 5)),
        ]);
    }

    /** 更新 del mes del empleado */
    public function saveMonth(Request $request): RedirectResponse
    {
        $shopCd = $this->selectedShop($request, $this->shops());
        $first = $this->parseDay($request->input('d'));
        $staffCd = (int) $request->input('staff_cd');
        $values = array_values(array_filter((array) $request->input('sch', []), 'is_string'));

        // Solo empleados activos de la tienda (el legacy aceptaba cualquier staff_cd numérico)
        if ($this->staffOf($shopCd)->contains('cd', $staffCd)) {
            $this->board->saveMonth($shopCd, $staffCd, LegacyMonth::of($first->year, $first->month), $values);
        }

        return to_route('admin.operaciones.shifts.month', ['shop_cd' => $shopCd, 'staff_cd' => $staffCd, 'd' => $first->format('Ymd')]);
    }

    /** Tiendas que puede ver: todas (master) o la suya */
    private function shops(): Collection
    {
        $user = auth('admin')->user();

        return Shop::query()->when(! $user->isMaster(), fn ($q) => $q->where('cd', $user->shop_cd))->orderBy('cd')->pluck('kaishamei', 'cd');
    }

    private function selectedShop(Request $request, Collection $shops): int
    {
        $requested = (int) $request->input('shop_cd');

        return $shops->has($requested) ? $requested : (int) $shops->keys()->first();
    }

    /** ?d=YYYYMMDD (hoy si falta o no es una fecha) */
    private function parseDay(mixed $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3], 0, 0, 0, LegacyMonth::TIMEZONE);
        }

        return CarbonImmutable::now(LegacyMonth::TIMEZONE)->startOfDay();
    }

    /** Empleados activos (disp_flg = '1') de la tienda, del más reciente */
    private function staffOf(int $shopCd): Collection
    {
        return Staff::active()->where('shop_cd', $shopCd)->orderByDesc('touroku_date')->get(['cd', 'name']);
    }
}
