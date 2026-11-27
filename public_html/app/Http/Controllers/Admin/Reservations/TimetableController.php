<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Models\Reservations\ReservationConfig;
use App\Services\Nucleo\LegacyUi;
use App\Services\Reservations\ReservationFlowService;
use App\Services\Reservations\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 予約表 (legacy: category9/yoyaku/time_table.php, time_table_staff.php) y los AJAX que llama su JS:
 * ajax_time_table.php (popup de edición), ajax_loadNewYoyakuList.php / ajax_loadNewRaitenList.php (listas laterales)
 * y ajax_getYoyakuDatetimeForm.php (cambio de fecha y hora). Mismos parámetros de URL que el legacy.
 */
class TimetableController extends Controller
{
    use SelectsLegacyShop;

    /** Cookie del panel lateral (la escribe también el JS con setCookie(), sin cifrar) */
    public const PANEL_COOKIE = 'c9_yoyaku_control_panel';

    /** Última reserva vista en 新規予約リスト (para el aviso 「新しいリストをロードしました。」) */
    public const NEW_RECORD_COOKIE = 'c9_yoyaku_new_record';

    public function __construct(
        private readonly TimetableService $timetable,
        private readonly ReservationFlowService $flow,
    ) {}

    /** time_table.php: vista día (?d=Ymd), 月間 (?ichiran=1&date=Y-m) y 週間 (?ichiran=2&date=Y-Www) */
    public function index(Request $request, LegacyUi $legacyUi): View
    {
        $isMaster = $this->isMasterUser();
        $shopCd = $this->timetableShopCd($request, $isMaster);
        $today = now('Asia/Tokyo')->startOfDay();

        $d = $this->queryString($request, 'd');
        $baseDate = $this->parseYmd($d) ?? $today->copy();

        // 一覧表示: 1 = 月間, 2 = 週間 (otros valores = vista día)
        $ichiran = in_array((int) $request->query('ichiran'), [1, 2], true) ? (int) $request->query('ichiran') : 0;
        $ichiranDate = $this->queryString($request, 'date');
        $ichiranStart = $ichiranEnd = null;
        if ($ichiran > 0) {
            [$ichiranStart, $ichiranEnd, $ichiranDate] = $this->ichiranRange($ichiran, $ichiranDate, $today);
        }
        $dates = $ichiran > 0 ? $this->datesBetween($ichiranStart, $ichiranEnd) : [$baseDate->copy()];

        $config = $this->timetable->config($shopCd);
        $geometry = $this->timetable->geometry($config ?? new ReservationConfig(['start_time' => '00:00', 'end_time' => '00:00']));

        $sortDate = (string) $config?->timeline_sort_type === '1' ? $baseDate->format('Y/m/d') : null;
        $staff = $config ? $this->timetable->staffList($shopCd, $sortDate) : collect();
        $grid = $config
            ? $this->timetable->days($shopCd, $dates, $staff, $geometry, $today)
            : ['days' => [], 'totalYoyakuCnt' => 0, 'totalPeopleCnt' => 0];

        $adminMaster = AdminMaster::current();
        $userAgent = (string) $request->userAgent();

        return view('admin.reservas.timetable', [
            'shopCd' => $shopCd,
            'isMaster' => $isMaster,
            'shopName' => $isMaster ? null : $this->shopName($shopCd),
            'shops' => $isMaster ? $this->shopOptions() : collect(),
            'd' => $d,
            'td' => $d !== null && $d !== '' ? $d : $today->format('Ymd'),
            'baseDate' => $baseDate,
            'ichiran' => $ichiran,
            'ichiranDate' => $ichiranDate,
            'ichiranStart' => $ichiranStart,
            'ichiranEnd' => $ichiranEnd,
            'ctiParams' => $this->ctiParams($request),
            'customerCd' => $this->queryString($request, 'customerCd'),
            'versionFlg' => $legacyUi->versionFlg(),
            'isTablet' => $this->isTablet($userAgent),
            'isIpad' => str_contains($userAgent, 'iPad'),
            'isMac' => $this->isMac($userAgent),
            'isRw107' => str_contains($userAgent, 'CRMRW107'),
            'configError' => $config === null,
            'timeCount' => $geometry['timeCount'],
            'timeLabels' => $geometry['timeLabels'],
            'startsAtHalfHour' => $geometry['startsAtHalfHour'],
            'keikaByou' => $this->timetable->keikaByou($geometry['start']),
            'staffWord' => $this->flow->manageWord('スタッフ', 'c9_yoyaku[スタッフ]'),
            'yoyakuSetsubiFlg' => $adminMaster->yoyaku_setsubi_flg,
            'freeYoyakuFlg' => $adminMaster->free_yoyaku_flg,
            'raitenListEnabled' => DB::table('tb_base_system_child')->where('system_name', '来店履歴管理')->where('tb_base_system_cd', 3)->where('system_flg', '1')->exists(),
            'listPrintEnabled' => $this->reservationOption('予約一覧出力'),
            'realtimePrintEnabled' => $this->reservationOption('リアルタイム印刷'),
            'controlPanel' => $this->legacyCookie($request, self::PANEL_COOKIE),
            'calendarMonths' => [$today->copy()->startOfMonth(), $today->copy()->startOfMonth()->addMonth(), $today->copy()->startOfMonth()->addMonths(2)],
            'staffCount' => $staff->count(),
            'days' => $grid['days'],
            'totalYoyakuCnt' => $grid['totalYoyakuCnt'],
            'totalPeopleCnt' => $grid['totalPeopleCnt'],
            'freeKoumokuLabels' => $this->timetable->freeKoumokuLabels(),
            'openYoyakuCd' => $this->queryString($request, 'open_yoyaku_cd'),
            'openShopCd' => $this->queryString($request, 'open_shop_cd'),
            'openD' => $this->queryString($request, 'open_d'),
        ]);
    }

    /** time_table_staff.php: el mes de un empleado (?shop, staff, d, bd = día al que vuelve 「戻る」) */
    public function staff(Request $request, LegacyUi $legacyUi): View|RedirectResponse
    {
        $isMaster = $this->isMasterUser();
        $shopCd = $this->timetableShopCd($request, $isMaster);
        $staffCd = (int) $request->query('staff');

        $member = $this->timetable->staffOfShop($staffCd, $shopCd);
        if ($member === null) {
            return redirect()->route('admin.reservas.timetable');
        }

        $today = now('Asia/Tokyo')->startOfDay();
        $d = $this->queryString($request, 'd');
        $month = ($this->parseYmd($d) ?? $today)->copy()->startOfMonth();
        $bd = $this->queryString($request, 'bd');
        $backDay = (string) ($bd !== null && $bd !== '' ? $bd : $d);

        $config = $this->timetable->config($shopCd);
        $geometry = $this->timetable->geometry($config ?? new ReservationConfig(['start_time' => '00:00', 'end_time' => '00:00']));
        $userAgent = (string) $request->userAgent();
        $adminMaster = AdminMaster::current();

        return view('admin.reservas.timetable_staff', [
            'shopCd' => $shopCd,
            'isMaster' => $isMaster,
            'shopName' => $isMaster ? null : $this->shopName($this->sessionShopCd()),
            'shops' => $isMaster ? $this->shopOptions() : collect(),
            'staffCd' => $staffCd,
            'staffRequest' => $this->queryString($request, 'staff'),
            'staffName' => (string) $member->name,
            'd' => $d,
            'td' => $d !== null && $d !== '' ? $d : $today->format('Ymd'),
            'backDay' => $backDay,
            'month' => $month,
            'prevMonth' => $month->copy()->subDay()->format('Ymd'),
            'nextMonth' => $month->copy()->addMonth()->format('Ymd'),
            'versionFlg' => $legacyUi->versionFlg(),
            'isTablet' => $this->isTablet($userAgent),
            'isIpad' => str_contains($userAgent, 'iPad'),
            'isIos' => (bool) preg_match('/iPhone|iPad|iPod/', $userAgent),
            'timeCount' => $geometry['timeCount'],
            'timeLabels' => $geometry['timeLabels'],
            'staffWord' => $this->flow->manageWord('スタッフ', 'c9_yoyaku[スタッフ]'),
            'yoyakuSetsubiFlg' => $adminMaster->yoyaku_setsubi_flg,
            'freeYoyakuFlg' => $adminMaster->free_yoyaku_flg,
            'monthDays' => $this->timetable->staffMonth($shopCd, $staffCd, $month, $geometry, $today),
            'freeKoumokuLabels' => $this->timetable->freeKoumokuLabels(),
        ]);
    }

    /** ajax_time_table.php (POST cd): formulario de edición de la reserva; 'ERROR:01' si no existe */
    public function data(Request $request): View|Response
    {
        $row = $this->timetable->reservationWithSlot((int) $request->input('cd'));
        if ($row === null) {
            return response('ERROR:01');
        }
        $userAgent = (string) $request->userAgent();

        return view('admin.reservas.timetable.data', $this->timetable->popupData($row, str_contains($userAgent, 'iPad')) + [
            'cd' => (int) $request->input('cd'),
            'isIpad' => str_contains($userAgent, 'iPad'),
            'isTablet' => $this->isTablet($userAgent),
        ]);
    }

    /** ajax_getYoyakuDatetimeForm.php (?cd): selector de la nueva fecha y hora de inicio */
    public function datetimeForm(Request $request): View
    {
        $row = $this->timetable->reservationWithSlot((int) $request->input('cd'));

        return view('admin.reservas.timetable.datetime_form', $this->timetable->datetimeFormData($row) + [
            'cd' => $this->queryString($request, 'cd'),
        ]);
    }

    /** ajax_loadNewYoyakuList.php (POST tb_shop_cd, type): 新規予約リスト (10 últimas reservas) */
    public function recent(Request $request): Response
    {
        if (blank($request->input('tb_shop_cd'))) {
            return response('');
        }
        $shopCd = $this->listShopCd($request);
        $rows = $this->timetable->recentReservations($shopCd);

        $toast = false;
        $response = response();
        $newRecord = $rows->isNotEmpty() ? (string) $rows->first()->cd : null;
        if ($newRecord !== null && $this->legacyCookie($request, self::NEW_RECORD_COOKIE) !== $newRecord) {
            $toast = true;
        }

        $content = view('admin.reservas.timetable.recent', [
            'rows' => $rows,
            'toast' => $toast,
            'shopCd' => $this->queryString($request, 'tb_shop_cd', 'input'),
            'staffLabel' => $this->flow->manageWord('スタッフ名', 'c9_yoyaku[スタッフ]'),
            'isTablet' => $this->isTablet((string) $request->userAgent()),
        ])->render();

        $response = $response->make($content)->withCookie($this->panelCookie($request->input('type') === 'yoyakuList' ? 'yoyakuList' : null));
        if ($toast) {
            $response->withCookie(Cookie::make(self::NEW_RECORD_COOKIE, $newRecord, 60 * 24 * 30, '/', null, null, false));
        }

        return $response;
    }

    /** ajax_loadNewRaitenList.php (POST tb_shop_cd, type; ?d=Ymd): 来店リスト del día */
    public function visits(Request $request): Response
    {
        if (blank($request->input('tb_shop_cd'))) {
            return response('');
        }
        $shopCd = $this->listShopCd($request);
        $day = $this->parseYmd($this->queryString($request, 'd', 'input')) ?? now('Asia/Tokyo')->startOfDay();

        $content = view('admin.reservas.timetable.visits', [
            'rows' => $this->timetable->visits($shopCd, $day),
            'day' => $day,
            'shopCd' => $this->queryString($request, 'tb_shop_cd', 'input'),
            'isTablet' => $this->isTablet((string) $request->userAgent()),
        ])->render();

        return response($content)->withCookie($this->panelCookie($request->input('type') === 'raitenList' ? 'raitenList' : null));
    }

    /** system/category9/ (index.php del legacy) → 予約表 */
    public function legacyEntry(Request $request): RedirectResponse
    {
        return redirect()->route('admin.reservas.timetable', $request->query());
    }

    // ---------------------------------------------------------------- auxiliares

    /** Tienda del 予約表: `?shop` solo para master; si no (o no viene), la de la sesión */
    private function timetableShopCd(Request $request, bool $isMaster): int
    {
        return ! $isMaster || blank($request->query('shop'))
            ? (int) $this->sessionShopCd()
            : (int) $request->query('shop');
    }

    /** Listas laterales: el legacy aceptaba cualquier tb_shop_cd; aquí un no master solo ve su tienda */
    private function listShopCd(Request $request): int
    {
        return $this->isMasterUser() ? (int) $request->input('tb_shop_cd') : (int) $this->sessionShopCd();
    }

    /** Parámetro de texto (los arrays se ignoran) */
    private function queryString(Request $request, string $key, string $source = 'query'): ?string
    {
        $value = $source === 'query' ? $request->query($key) : $request->input($key);

        return is_scalar($value) ? (string) $value : null;
    }

    /** 'Ymd' → Carbon (Asia/Tokyo, 00:00); null si no es una fecha válida */
    private function parseYmd(?string $ymd): ?Carbon
    {
        if ($ymd === null || ! preg_match('/^(\d{4})(\d{2})(\d{2})/', $ymd, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], 0, 0, 0, 'Asia/Tokyo');
    }

    /**
     * Primer y último día del 月間 ('Y-m') o 週間 ('Y-Www', semana ISO de lunes a domingo).
     * Si `date` no es válida se usa el mes / la semana actual.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function ichiranRange(int $ichiran, ?string $date, Carbon $today): array
    {
        if ($ichiran === 1) {
            if ($date !== null && preg_match('/^(\d{4})-(\d{2})$/', $date, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
                $start = Carbon::create((int) $m[1], (int) $m[2], 1, 0, 0, 0, 'Asia/Tokyo');
            } else {
                $start = $today->copy()->startOfMonth();
                $date = $start->format('Y-m');
            }

            return [$start, $start->copy()->endOfMonth()->startOfDay(), $date];
        }

        if ($date !== null && preg_match('/^(\d{4})-W(\d{2})$/', $date, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 53) {
            $start = $today->copy()->setISODate((int) $m[1], (int) $m[2], 1)->startOfDay();
        } else {
            $start = $today->copy()->startOfWeek(Carbon::MONDAY);
            $date = $start->format('o-\WW');
        }

        return [$start, $start->copy()->addDays(6), $date];
    }

    /** @return list<Carbon> */
    private function datesBetween(Carbon $start, Carbon $end): array
    {
        $dates = [];
        for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            $dates[] = $day->copy();
        }

        return $dates;
    }

    /** CTI 電話予約モード: solo si llegan isCti=1, tel y customerCd */
    private function ctiParams(Request $request): array
    {
        $tel = $this->queryString($request, 'tel');
        $customerCd = $this->queryString($request, 'customerCd');
        if ($this->queryString($request, 'isCti') !== '1' || blank($tel) || blank($customerCd)) {
            return [];
        }

        return ['isCti' => '1', 'tel' => $tel, 'customerCd' => $customerCd];
    }

    /** Opción del 予約管理 en tb_base_system_grandchild (tb_base_system_cd 9, child 105) */
    private function reservationOption(string $name): bool
    {
        return DB::table('tb_base_system_grandchild')
            ->where('tb_base_system_cd', 9)
            ->where('tb_base_system_child_cd', 105)
            ->where('system_name', $name)
            ->where('system_flg', '1')
            ->exists();
    }

    /**
     * Cookie del legacy: la cifrada por Laravel si la escribió el servidor, o la que escribe el JS (setCookie(),
     * texto plano con escape()) leyendo la cabecera Cookie tal cual llegó.
     */
    private function legacyCookie(Request $request, string $name): ?string
    {
        $value = $request->cookie($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        foreach (explode(';', (string) $request->headers->get('cookie')) as $pair) {
            [$key, $raw] = array_pad(explode('=', trim($pair), 2), 2, '');
            if ($key === $name) {
                return rawurldecode($raw);
            }
        }

        return null;
    }

    /** setcookie('c9_yoyaku_control_panel', …, 30 días, '/') o su borrado */
    private function panelCookie(?string $value): \Symfony\Component\HttpFoundation\Cookie
    {
        return $value === null
            ? Cookie::forget(self::PANEL_COOKIE, '/')
            : Cookie::make(self::PANEL_COOKIE, $value, 60 * 24 * 30, '/', null, null, false);
    }

    /** $isTablet de kihon.php: cUserAgent DEVICE_NAME en ('CRMRW107', 'CRMH580VT_A') */
    private function isTablet(string $userAgent): bool
    {
        return str_contains($userAgent, 'CRMRW107') || str_contains($userAgent, 'CRMH580VT_A');
    }

    /** cUserAgent TYPE == 'mac': PC (no móvil) con "Mac" y sin "Win" */
    private function isMac(string $userAgent): bool
    {
        if (preg_match('/iPhone|iPad|iPod|Android|DoCoMo|KDDI|SoftBank|Windows Phone/i', $userAgent)) {
            return false;
        }

        return ! str_contains($userAgent, 'Win') && str_contains($userAgent, 'Mac');
    }
}
