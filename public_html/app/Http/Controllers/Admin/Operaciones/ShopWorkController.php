<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ShopWork;
use App\Services\Operaciones\LegacyHolidays;
use App\Services\Operaciones\LegacyMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * 営業日設定 (category5/config/shop_work_calendar.php): calendario del mes de la tienda de la sesión;
 * las casillas marcadas son 休日 (syukkin_time_N = '0'), el resto 営業日 ('1').
 * Diferencias: la fila se busca por tienda y mes (el legacy no filtraba la tienda al buscarla) y un
 * year/mon inválido muestra el mes actual.
 */
class ShopWorkController extends Controller
{
    public function edit(Request $request): View
    {
        $month = $this->month($request);
        $work = ShopWork::forMonthOrNew($this->shopCd($request), $month);
        $today = LegacyMonth::current()->slash() === $month->slash() ? now(LegacyMonth::TIMEZONE)->day : -1;

        $days = [];
        foreach (range(1, $month->daysInMonth()) as $day) {
            $days[$day] = [
                'open' => $work->isOpen($day),
                'class' => match (true) {
                    $day === $today => 'nowday',
                    $month->day($day)->dayOfWeek === 0 || LegacyHolidays::code($month->start->year, $month->start->month, $day) !== 0 => 'holiday',
                    default => 'normalday',
                },
            ];
        }

        return view('admin.operaciones.shop-work.edit', [
            'month' => $month,
            'previous' => $month->previous(),
            'next' => $month->next(),
            'days' => $days,
            'firstWeekday' => $month->start->dayOfWeek,
        ]);
    }

    /** 更新: las casillas enviadas (name = día, value 0) quedan 休日; las demás 営業日 */
    public function update(Request $request): RedirectResponse
    {
        $month = $this->month($request);

        DB::transaction(function () use ($request, $month) {
            $work = ShopWork::forMonthOrNew($this->shopCd($request), $month);
            foreach (range(1, $month->daysInMonth()) as $day) {
                $work->setOpen($day, (string) $request->input((string) $day) !== '0');
            }
            $work->save();
        });

        return to_route('admin.operaciones.shop-work.edit', ['year' => $month->start->year, 'mon' => $month->start->month]);
    }

    private function month(Request $request): LegacyMonth
    {
        $year = $request->input('year');
        $mon = $request->input('mon');
        if (! is_scalar($year) || ! is_scalar($mon) || $year === '' || $mon === '') {
            return LegacyMonth::current();
        }

        try {
            return LegacyMonth::of((int) $year, (int) $mon);
        } catch (InvalidArgumentException) {
            return LegacyMonth::current();
        }
    }

    private function shopCd(Request $request): int
    {
        return (int) $request->session()->get('shop_cd', auth('admin')->user()->shop_cd);
    }
}
