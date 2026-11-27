<?php

namespace App\Http\Controllers\Admin\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ShopWork;
use App\Models\Operaciones\WorkTime;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\LegacyMonth;
use App\Services\Operaciones\Working\WorkingReport;
use App\Services\Operaciones\Working\WorkingStaffSearch;
use App\Services\Operaciones\Working\WorkingTotals;
use App\Support\LegacyCsv;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 勤怠 (category5/working, S2): スタッフ一覧 mensual (working_list.php), スタッフ勤怠集計一覧
 * (working_total_list.php), 帳票管理 (working_chouhyou.php) y CSV 勤怠 (csv_download.php, csv_file_out.php).
 * Doc/diseno/operaciones-integraciones.md §1.3.
 */
class WorkingSummaryController extends Controller
{
    private const SCREENS = ['monthly' => WorkingTotals::MONTHLY_CUTOFF, 'totals' => WorkingTotals::TOTALS_CUTOFF];

    public function __construct(private readonly WorkingStaffSearch $search)
    {
    }

    /** スタッフ一覧: 出勤/退勤/労働時間 del día de trabajo de cada empleado */
    public function monthly(Request $request): View
    {
        $data = $this->listData($request, 'monthly');
        $rows = $this->workTimes($data['staff'], $data['month']);
        $data['days'] = $data['staff']->mapWithKeys(fn (Staff $s) => [$s->cd => WorkingTotals::dayRow($rows->get($s->cd), $data['day'])]);

        return view('admin.operaciones.working.monthly', $data);
    }

    /** スタッフ勤怠集計一覧: totales del mes de cada empleado */
    public function totals(Request $request): View
    {
        $data = $this->listData($request, 'totals');
        $rows = $this->workTimes($data['staff'], $data['month']);
        $shopWorks = ShopWork::query()->where('syukkinbi', $data['month']->slash())
            ->whereIn('shop_cd', $data['staff']->pluck('shop_cd')->filter()->unique())->get()->keyBy('shop_cd');
        $data['totals'] = $data['staff']->mapWithKeys(fn (Staff $s) => [$s->cd => WorkingTotals::month(
            $rows->get($s->cd), $shopWorks->get($s->shop_cd), $data['month'], $data['day'],
        )]);

        return view('admin.operaciones.working.totals', $data);
    }

    /** この条件で抽出する / 表示件数: guarda la búsqueda en la sesión y vuelve a la lista */
    public function search(Request $request, string $screen): RedirectResponse
    {
        abort_unless(isset(self::SCREENS[$screen]), 404);
        $request->session()->put(WorkingStaffSearch::SESSION_KEY, $this->search->parse($request->except(['_token', 'act', 'kensu', 'se_flg', 'search_frm', 'post_wherestr', 'item_max_no'])));

        return to_route("admin.operaciones.working.{$screen}", ['kensu' => $this->kensu($request->input('kensu')), 'se_flg' => 1, 'act' => 'up']);
    }

    /** 帳票管理: un empleado y un mes, en dos columnas (1-16 / 17-fin), con totales */
    public function report(Request $request): View
    {
        $user = $request->user('admin');
        $shops = Shop::query()->where('disp_flg', '!=', '9')
            ->when(! $user->isMaster(), fn ($q) => $q->where('cd', $user->shop_cd))->orderBy('cd')->get();
        $shop = $shops->firstWhere('cd', (int) $request->query('shop_cd')) ?? $shops->first();
        $month = $this->parseYm((string) $request->query('ym', ''));
        $staff = $shop ? Staff::active()->where('shop_cd', $shop->cd)->orderByDesc('touroku_date')->get(['cd', 'name']) : collect();
        $member = $staff->firstWhere('cd', (int) $request->query('staff_cd'));
        $row = $member ? WorkTime::query()->where('staff_cd', $member->cd)->forMonth($month)->first() : null;
        $report = WorkingReport::month($shop, $member ? $row : null, $month, CarbonImmutable::now(LegacyMonth::TIMEZONE));

        return view('admin.operaciones.working.report', [
            'ym' => $month->start->format('Y/m'),
            'shops' => $shops,
            'shop' => $shop,
            'staff' => $staff,
            'staffCd' => $member?->cd,
            'days' => $member ? $report['days'] : array_map(fn ($d) => ['attendance' => '---', 'return_office' => '---'] + $d, $report['days']),
            'totals' => $member ? $report['totals'] : ['working' => 0, 'overtime' => 0, 'holiday' => 0, 'attendances' => 0, 'absenteeism' => 0, 'paidUseDays' => 0],
        ]);
    }

    /**
     * CSV 勤怠 del día `dd` ('Y/n/j') de la tienda `shop_cd` (sin tienda: la cd 1 como cabecera y todos los empleados
     * activos), Shift_JIS sin comillas como el legacy. Sustituye también a csv_file_out.php (Flash + archivo temporal).
     */
    public function csv(Request $request): StreamedResponse
    {
        $shopCd = (int) $request->input('shop_cd');
        $shop = Shop::withDeleted()->where('cd', $request->filled('shop_cd') ? $shopCd : 1)->first();
        [$month, $day] = $this->parseDd((string) $request->input('dd', ''));
        $staff = Staff::active()->when($request->filled('shop_cd'), fn ($q) => $q->where('shop_cd', $shopCd))->orderBy('cd')->get();
        $rows = $this->workTimes($staff, $month);
        $lines = [
            WorkingReport::CSV_TITLES,
            ...WorkingReport::csvRows($shop, $staff, $rows, $month, $day, CarbonImmutable::now(LegacyMonth::TIMEZONE)),
        ];

        return LegacyCsv::download('staff_kintai.csv', $lines, [(string) $shop?->kaishamei], ['quote' => 'none']);
    }

    /** Datos comunes de las dos listas: búsqueda en sesión, paginación y día de trabajo */
    private function listData(Request $request, string $screen): array
    {
        // Sin se_flg (entrada por el menú) el legacy reiniciaba la búsqueda a "WHERE disp_flg = '1'"
        if ((string) $request->query('se_flg') !== '1') {
            $request->session()->forget(WorkingStaffSearch::SESSION_KEY);
        }
        $saved = $request->session()->get(WorkingStaffSearch::SESSION_KEY, ['conditions' => [WorkingStaffSearch::activeCondition()], 'words' => []]);

        $kensu = $this->kensu($request->query('kensu'));
        $total = $this->search->query($saved['conditions'])->count();
        $maxPage = (int) ceil($total / $kensu);
        $page = max(1, min((int) $request->query('page', 1), $maxPage));
        $from = ($page - 1) * $kensu;
        $date = WorkingTotals::businessDate(CarbonImmutable::now(LegacyMonth::TIMEZONE), self::SCREENS[$screen]);

        return [
            'screen' => $screen,
            'kensu' => $kensu,
            'page' => $page,
            'maxPage' => $maxPage,
            'total' => $total,
            'from' => $from,
            'staff' => $this->search->query($saved['conditions'])->orderByDesc('cd')->limit($kensu)->offset($from)->get(),
            'fields' => $this->search->fields(),
            'words' => $saved['words'],
            'shops' => Shop::where('disp_flg', '!=', '9')->orderBy('cd')->get(['cd', 'kaishamei']),
            'belongposts' => DB::table('tb_belongpost')->orderBy('cd')->pluck('belongpost'),
            'month' => LegacyMonth::of($date->year, $date->month),
            'day' => $date->day,
        ];
    }

    /** tb_work_time del mes de los empleados, por staff_cd */
    private function workTimes(Collection $staff, LegacyMonth $month): Collection
    {
        return WorkTime::query()->forMonth($month)->whereIn('staff_cd', $staff->pluck('cd'))->orderBy('cd')->get()
            ->unique('staff_cd')->keyBy('staff_cd');
    }

    /** 表示件数 (10 por defecto, como el legacy) */
    private function kensu(mixed $value): int
    {
        $kensu = is_scalar($value) ? (int) $value : 0;

        return $kensu > 0 ? min($kensu, 1000) : 10;
    }

    /** `ym` del datepicker ('Y/m'); si no es válido, el mes actual */
    private function parseYm(string $ym): LegacyMonth
    {
        if (preg_match('#^(\d{4})/(\d{1,2})$#', trim($ym), $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            return LegacyMonth::of((int) $m[1], (int) $m[2]);
        }

        return LegacyMonth::current();
    }

    /**
     * `dd` del CSV ('Y/n/j'): mes (el actual si falta año o mes) y día (null si no existe en ese mes).
     *
     * @return array{0: LegacyMonth, 1: ?int}
     */
    private function parseDd(string $dd): array
    {
        $parts = explode('/', trim($dd));
        $month = LegacyMonth::current();
        if (ctype_digit($parts[0] ?? '') && ctype_digit($parts[1] ?? '') && (int) $parts[1] >= 1 && (int) $parts[1] <= 12 && (int) $parts[0] > 1970) {
            $month = LegacyMonth::of((int) $parts[0], (int) $parts[1]);
        }
        $day = ctype_digit($parts[2] ?? '') ? (int) $parts[2] : 0;

        return [$month, $day >= 1 && $day <= $month->daysInMonth() ? $day : null];
    }
}
