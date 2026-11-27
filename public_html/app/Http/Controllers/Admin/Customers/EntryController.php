<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ShopWork;
use App\Services\Customers\EntryService;
use App\Services\Nucleo\LegacyUi;
use App\Services\Operaciones\LegacyHolidays;
use App\Services\Operaciones\LegacyMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * 入退室一覧 (legacy `system/category1/entrance/working_calendar.php` y `work_list.php`):
 * calendario del mes con un botón 表示 por día, y la lista del día con entrada/salida de cada socio
 * (tb_play_time) y su corrección.
 */
class EntryController extends Controller
{
    public function __construct(private EntryService $entries) {}

    /**
     * working_calendar.php: mes de ?year=&mon= o de ?dd= (vuelta desde la lista); si no, el actual.
     * 休日 según tb_shop_work de la tienda de la sesión (el legacy tomaba la primera fila del mes de cualquier tienda).
     */
    public function calendar(Request $request): View
    {
        $month = $this->month($request);
        $work = ShopWork::query()->forMonth($this->shopCd($request), $month)->first();
        $today = LegacyMonth::current()->slash() === $month->slash() ? now(LegacyMonth::TIMEZONE)->day : -1;

        $days = [];
        foreach (range(1, $month->daysInMonth()) as $day) {
            $days[$day] = [
                'open' => $work ? $work->isOpen($day) : true,
                'class' => match (true) {
                    $day === $today => 'nowday',
                    $month->day($day)->dayOfWeek === 0 || LegacyHolidays::code($month->start->year, $month->start->month, $day) !== 0 => 'holiday',
                    default => 'normalday',
                },
            ];
        }

        return view('admin.clientes.entries.calendar', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'month' => $month,
            'previous' => $month->previous(),
            'next' => $month->next(),
            'days' => $days,
            'firstWeekday' => $month->start->dayOfWeek,
        ]);
    }

    /** work_list.php: socios con su entrada/salida del día ?dd=Y/n/j (sin fecha válida, hoy) */
    public function index(Request $request): View
    {
        $date = $this->entries->parseDate($request->input('dd')) ?? now(LegacyMonth::TIMEZONE)->toImmutable()->startOfDay();
        $shopCd = $request->input('shop_cd');
        $shopCd = is_scalar($shopCd) && ctype_digit((string) $shopCd) ? (int) $shopCd : null;

        return view('admin.clientes.entries.index', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'date' => $date,
            'dd' => $date->format('Y/n/j'),
            'shopCd' => $shopCd,
            'rows' => $this->entries->list($date, $shopCd, $this->int($request, 'page', 1), $this->int($request, 'kensu', 20)),
            'entryStatuses' => EntryService::ENTRY_STATUSES,
            'exitStatuses' => EntryService::EXIT_STATUSES,
        ]);
    }

    /** 決定 del editor de una fila: scd (tb_customer.cd), dd, sh/sm, th/tm, sf, sk; vuelve a la misma página */
    public function update(Request $request): RedirectResponse
    {
        $date = $this->entries->parseDate($request->input('dd'));
        abort_if($date === null || ! ctype_digit((string) $request->input('scd')), 404);

        $this->entries->update($date, (int) $request->input('scd'), $request->only(['sh', 'sm', 'th', 'tm', 'sf', 'sk']));

        return to_route('admin.clientes.entries.index', array_filter([
            'page' => $request->input('page'),
            'shop_cd' => $request->input('shop_cd'),
            'dd' => $date->format('Y/n/j'),
            'kensu' => $request->input('kensu'),
        ], fn ($v) => is_scalar($v) && $v !== ''));
    }

    /** Mes de ?dd=Y/n[/j] o de ?year=&mon=; inválido o ausente = el actual */
    private function month(Request $request): LegacyMonth
    {
        $dd = $request->input('dd');
        if (is_string($dd) && preg_match('#^(\d{4})/(\d{1,2})#', trim($dd), $m)) {
            [$year, $mon] = [$m[1], $m[2]];
        } else {
            [$year, $mon] = [$request->input('year'), $request->input('mon')];
        }
        if (! is_scalar($year) || ! is_scalar($mon) || ! ctype_digit((string) $year) || ! ctype_digit((string) $mon)) {
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
        return (int) $request->session()->get('shop_cd', $request->user('admin')->shop_cd);
    }

    private function int(Request $request, string $key, int $default): int
    {
        $value = $request->input($key);

        return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : $default;
    }
}
