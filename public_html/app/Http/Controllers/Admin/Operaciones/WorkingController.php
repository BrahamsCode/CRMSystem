<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ShopWork;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\LegacyHolidays;
use App\Services\Operaciones\LegacyMonth;
use App\Services\Operaciones\Working\WorkTimeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * 勤怠一覧 (category5/working): calendario del mes (working_calendar.php), lista del día (work_list.php,
 * work_list2.php, ng_work_list.php), corrección (work_list_update.php) y AJAX 出勤区分 (change_syukkin.php).
 * El legacy no pide permiso (kengen) en estas pantallas: basta la sesión del personal.
 * change_syukkin2.php no se migra (sin enlaces e inyección SQL).
 */
class WorkingController extends Controller
{
    public function __construct(private readonly WorkTimeService $service)
    {
    }

    /**
     * 勤怠一覧 (calendario). POST con shop_work_flg=up guarda 営業日/休日 del mes como el legacy
     * (ninguna pantalla lo envía, pero working_calendar.php lo procesa).
     */
    public function calendar(Request $request): View|RedirectResponse
    {
        $month = $this->month($request);
        $shopCd = $this->sessionShopCd($request);

        if ($request->isMethod('post') && $request->input('shop_work_flg') === 'up') {
            DB::transaction(function () use ($request, $month, $shopCd) {
                $work = ShopWork::forMonthOrNew($shopCd, $month);
                foreach (range(1, $month->daysInMonth()) as $day) {
                    $work->setOpen($day, (string) $request->input((string) $day) !== '0');
                }
                $work->save();
            });

            return to_route('admin.operaciones.working.calendar', ['year' => $month->start->year, 'mon' => $month->start->month]);
        }

        // Master: todas las tiendas visibles; el resto, solo la suya (2009.09.29 マルティン)
        $shops = Shop::query()->where('disp_flg', '!=', '9')
            ->when(! auth('admin')->user()->isMaster(), fn ($q) => $q->where('cd', $shopCd))
            ->orderBy('cd')->get(['cd', 'kaishamei']);

        $work = ShopWork::forMonthOrNew($shopCd, $month);
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

        return view('admin.operaciones.working.calendar', [
            'month' => $month,
            'previous' => $month->previous(),
            'next' => $month->next(),
            'days' => $days,
            'firstWeekday' => $month->start->dayOfWeek,
            'shops' => $shops,
        ]);
    }

    /** 勤怠一覧 del día (work_list.php): corrige con work_list_update.php */
    public function day(Request $request): View
    {
        return $this->dayList($request, WorkTimeService::VARIANT_DAY);
    }

    /** Variante work_list2.php: corrige por AJAX (change_syukkin.php) y recarga */
    public function day2(Request $request): View
    {
        return $this->dayList($request, WorkTimeService::VARIANT_DAY2);
    }

    /** Variante ng_work_list.php: solo corrige 出勤状況/出勤区分 por AJAX */
    public function ng(Request $request): View
    {
        return $this->dayList($request, WorkTimeService::VARIANT_NG);
    }

    /** 勤怠情報 (work_list_update.php): guarda el día y vuelve a la lista con un Refresh de 1 s */
    public function update(Request $request): View
    {
        $day = $this->service->parseDate($request->input('dd'));
        if ($day && $request->filled('scd')) {
            $this->service->saveDay(
                (int) $request->input('scd'), $day,
                [$request->input('sh'), $request->input('sm')],
                [$request->input('th'), $request->input('tm')],
                $request->input('sf'), $request->input('sk'),
            );
        }

        return view('admin.operaciones.working.update', [
            'back' => route('admin.operaciones.working.day', [
                'page' => $request->input('page'), 'shop_cd' => $request->input('shop_cd'),
                'dd' => $request->input('dd'), 'kensu' => $request->input('kensu'),
            ]),
        ]);
    }

    /** AJAX 出勤区分 (change_syukkin.php): misma respuesta JSON que el legacy */
    public function change(Request $request): JsonResponse
    {
        $day = $this->service->parseDate($request->input('nengappi'));
        abort_if($day === null, 422);

        $time = fn (string $h, string $m) => $request->has($h) || $request->has($m) ? [$request->input($h), $request->input($m)] : null;
        $row = $this->service->saveDay(
            (int) $request->input('staff_cd'), $day,
            $time('syukkin_hour', 'syukkin_min'), $time('taikin_hour', 'taikin_min'),
            $request->input('syukkin_flg'), $request->input('syukkin_kubun'),
        );

        return response()->json([
            'shukkin_flg' => (string) $row?->getDayValue('syukkin_flg', $day->day),
            'shukkin_kubun' => (string) $row?->getDayValue('syukkin_kubun', $day->day),
        ], options: JSON_UNESCAPED_UNICODE);
    }

    /** URL del legacy (marcadores, formularios viejos): a la pantalla nueva con los mismos parámetros */
    public function legacy(Request $request, string $target): RedirectResponse
    {
        return to_route('admin.operaciones.working.'.$target, $request->except('_token'));
    }

    private function dayList(Request $request, string $variant): View
    {
        $shopCd = (string) $request->input('shop_cd', '');
        $shop = $shopCd !== ''
            ? Shop::query()->find((int) $shopCd)
            : Shop::query()->orderBy('cd')->first();
        $day = $this->service->parseDate($request->input('dd')) ?? CarbonImmutable::now(LegacyMonth::TIMEZONE)->startOfDay();
        $kensu = max(1, (int) ($request->input('kensu') ?: 20));
        $page = max(1, (int) ($request->input('page') ?: 1));
        $byShop = fn ($q) => $q->when($shopCd !== '', fn ($q) => $q->where('shop_cd', (int) $shopCd));

        if ($variant === WorkTimeService::VARIANT_DAY) {
            // inc/page_control.php: cuenta y lista con la misma consulta (no borrados, más nuevos primero)
            $query = Staff::query()->tap($byShop)->orderByDesc('touroku_date')->orderByDesc('cd');
            $total = (clone $query)->count();
            $maxPage = (int) ceil($total / $kensu);
            $page = max(1, min($page, $maxPage));
        } else {
            // work_list2 / ng: el total cuenta los no borrados (ng: todos); la lista, solo disp_flg = '1'
            $count = $variant === WorkTimeService::VARIANT_NG ? Staff::withDeleted() : Staff::query();
            $total = $count->tap($byShop)->count();
            $maxPage = (int) ceil($total / $kensu);
            $query = Staff::query()->tap($byShop)->where('disp_flg', '1')->orderByDesc('cd');
        }
        $from = ($page - 1) * $kensu;
        $staff = $query->skip($from)->take($kensu)->get();

        return view('admin.operaciones.working.'.$variant, [
            'rows' => $this->service->dayRows($variant, $shop, $staff, $day),
            'shop' => $shop,
            'day' => $day,
            'dd' => (string) $request->input('dd', ''),
            'shopCd' => $shopCd,
            'kensu' => $kensu,
            'page' => $page,
            'maxPage' => $maxPage,
            'total' => $total,
            'from' => $from,
            'pager' => $this->pager($variant, $page, $maxPage, $kensu, $total),
        ]);
    }

    /** Botones de página de cada variante: [tipo (prev|now|num|next), página] */
    private function pager(string $variant, int $page, int $maxPage, int $kensu, int $total): array
    {
        $items = [];
        if ($page !== 1 && $kensu < $total) {
            $items[] = ['prev', $page - 1];
        }
        if ($maxPage > 1) {
            [$first, $last] = $variant === WorkTimeService::VARIANT_DAY
                ? [max(1, $page - 5), min($maxPage, $page + 5)]
                : [1, $maxPage];
            foreach (range($first, $last) as $n) {
                $items[] = [$n === $page ? 'now' : 'num', $n];
            }
        }
        // work_list2 / ng solo muestran 次へ si hubo números (last_page)
        if ($page < $maxPage && ($variant === WorkTimeService::VARIANT_DAY || $maxPage > 1)) {
            $items[] = ['next', $page + 1];
        }

        return $items;
    }

    /** Mes de year/mon o, al volver de la lista, de dd ('Y/n/j'); si no vale, el actual */
    private function month(Request $request): LegacyMonth
    {
        $year = $request->input('year');
        $mon = $request->input('mon');
        if (is_string($request->input('dd')) && $request->input('dd') !== '') {
            [$year, $mon] = explode('/', $request->input('dd')) + [null, null];
        }
        if (! is_scalar($year) || ! is_scalar($mon) || $year === '' || $mon === '') {
            return LegacyMonth::current();
        }

        try {
            return LegacyMonth::of((int) $year, (int) $mon);
        } catch (InvalidArgumentException) {
            return LegacyMonth::current();
        }
    }

    private function sessionShopCd(Request $request): int
    {
        return (int) $request->session()->get('shop_cd', auth('admin')->user()->shop_cd);
    }
}
