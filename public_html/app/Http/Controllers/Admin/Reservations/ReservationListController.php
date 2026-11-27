<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Services\Nucleo\LegacyUi;
use App\Services\Reservations\ReservationFlowService;
use App\Services\Reservations\ReservationListService;
use App\Support\LegacyCsv;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Listados de 予約管理 (legacy category9/yoyaku): view_shop.php (店舗毎予約数), yoyaku_list_calendar.php (予約一覧
 * de tb_reserve), pop_calendar.php (年間カレンダー), print/yoyaku_list_print.php (予約一覧出力) y
 * print/csv_download.php (CSV; sustituye también a csv_download/csv_file_out.php y csv_file_del.php del Flash).
 */
class ReservationListController extends Controller
{
    use SelectsLegacyShop;

    /** Sesión con la tienda y el día del último 予約一覧出力 (el legacy guardaba ahí la SQL para el CSV) */
    private const PRINT_SESSION = 'reservas.print';

    public function __construct(
        private readonly ReservationListService $lists,
        private readonly ReservationFlowService $flow,
    ) {}

    /** view_shop.php (?d=Ymd): reservas por tienda y día del mes */
    public function byShop(Request $request, LegacyUi $legacyUi): View
    {
        $d = $this->stringParam($request, 'd');
        $month = ($this->parseYmd($d) ?? now('Asia/Tokyo')->startOfDay())->copy()->startOfMonth();
        $userAgent = (string) $request->userAgent();
        $adminMaster = AdminMaster::current();

        return view('admin.reservas.lists.by_shop', [
            'd' => $d,
            'month' => $month,
            'prevMonth' => $month->copy()->subDay()->format('Ymd'),
            'nextMonth' => $month->copy()->addMonth()->format('Ymd'),
            'days' => $this->lists->monthDays($month),
            'shops' => $this->lists->activeShops(),
            'counts' => $this->lists->countsByShop($month),
            'versionFlg' => $legacyUi->versionFlg(),
            'siteName' => $legacyUi->siteName(),
            'isIos' => (bool) preg_match('/iPhone|iPad|iPod/', $userAgent),
            'isTablet' => str_contains($userAgent, 'CRMRW107') || str_contains($userAgent, 'CRMH580VT_A'),
            'staffWord' => $this->flow->manageWord('スタッフ', 'c9_yoyaku[スタッフ]'),
            'yoyakuSetsubiFlg' => $adminMaster->yoyaku_setsubi_flg,
            'freeYoyakuFlg' => $adminMaster->free_yoyaku_flg,
        ]);
    }

    /** yoyaku_list_calendar.php (year + mon, o dd = 'Y/n/j' al volver): 予約・来店一覧 de tb_reserve */
    public function calendar(Request $request, LegacyUi $legacyUi): View
    {
        $today = now('Asia/Tokyo')->startOfDay();
        $month = $this->listMonth($request) ?? $today->copy()->startOfMonth();
        $onlyShop = $this->isMasterUser() ? null : (int) $this->sessionShopCd();

        return view('admin.reservas.lists.calendar', $this->lists->simpleCalendar($month, $onlyShop, $today) + [
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth(),
            'nextMonth' => $month->copy()->addMonth(),
            'siteName' => $legacyUi->siteName(),
        ]);
    }

    /** pop_calendar.php (?shop, y, d; + isCti/tel/customerCd): 年間カレンダー que cambia el 予約表 de la ventana madre */
    public function popCalendar(Request $request, LegacyUi $legacyUi): View
    {
        $y = $this->stringParam($request, 'y');
        $year = $y !== null && preg_match('/^\d{4}$/', $y) ? (int) $y : (int) now('Asia/Tokyo')->year;
        $shop = $this->stringParam($request, 'shop');

        return view('admin.reservas.lists.pop_calendar', [
            'year' => $year,
            'shop' => $shop,
            'ctiParams' => $this->ctiParams($request),
            'months' => $this->lists->yearCalendar($year, $this->parseYmd($this->stringParam($request, 'd'))),
            'siteName' => $legacyUi->siteName(),
        ]);
    }

    /** print/yoyaku_list_print.php (?shop, d): lista imprimible de las reservas del día */
    public function print(Request $request): View|Response
    {
        $shopCd = $this->printShopCd($request);
        if ($shopCd === null) {
            return response(''); // if(!$_REQUEST['shop']) exit;
        }
        $date = $this->printDate($this->stringParam($request, 'd'));
        $request->session()->put(self::PRINT_SESSION, ['shop' => $shopCd, 'date' => $date]);

        return view('admin.reservas.lists.print', [
            'date' => $date,
            'shopCd' => $shopCd,
            'd' => $this->stringParam($request, 'd'),
            'columns' => $this->lists->printColumns(),
            'rows' => $this->lists->printRows($shopCd, $date),
        ]);
    }

    /**
     * print/csv_download.php: el mismo listado en CSV (SJIS-win, yoyaku.csv). Toma shop/d del formulario o, como el
     * legacy, el último 予約一覧出力 de la sesión. Sin reservas responde vacío (el legacy no enviaba nada).
     */
    public function csv(Request $request): StreamedResponse|Response
    {
        $last = (array) $request->session()->get(self::PRINT_SESSION, []);
        $shopCd = $this->printShopCd($request) ?? (isset($last['shop']) ? $this->printShopCdFrom($last['shop']) : null);
        $date = $this->stringParam($request, 'd') !== null ? $this->printDate($this->stringParam($request, 'd')) : ($last['date'] ?? null);
        if ($shopCd === null || $date === null) {
            return response('');
        }

        $columns = $this->lists->printColumns();
        $rows = $this->lists->printRows($shopCd, $date);
        if ($rows->isEmpty()) {
            return response('');
        }

        $header = ['', '名前'];
        foreach (['kana' => 'カナ', 'tel' => '電話番号', 'mail' => 'メールアドレス'] as $key => $label) {
            if ($columns[$key]) {
                $header[] = $label;
            }
        }
        array_push($header, ...array_values($columns['free']), ...[$columns['shimei'], '作業内容']);

        return response()->streamDownload(function () use ($date, $header, $rows, $columns) {
            // Primera línea: tabulador + fecha, sin comillas (igual que el legacy)
            echo LegacyCsv::encode("\t" . $date . "\r\n" . LegacyCsv::line($header));
            foreach ($rows as $row) {
                $cells = [$row['time'], $row['name']];
                if ($columns['kana']) {
                    $cells[] = $row['kana'];
                }
                if ($columns['tel']) {
                    $cells[] = "\t" . $row['tel'];
                }
                if ($columns['mail']) {
                    $cells[] = $row['mail'];
                }
                foreach (array_keys($columns['free']) as $n) {
                    $cells[] = "\t" . ($row['free'][$n] ?? '');
                }
                $cells[] = $row['staff'];
                $cells[] = $row['menu'];
                echo LegacyCsv::encode(LegacyCsv::line($cells));
            }
        }, 'yoyaku.csv', ['Content-Type' => 'text/x-csv']);
    }

    // ---------------------------------------------------------------- auxiliares

    /** Tienda del listado: `shop` de la petición (master) o la de la sesión (resto); null si no viene ninguna */
    private function printShopCd(Request $request): ?int
    {
        $shop = $this->stringParam($request, 'shop');

        return $shop === null || $shop === '' || ! is_numeric($shop) ? null : $this->printShopCdFrom($shop);
    }

    private function printShopCdFrom(mixed $shop): int
    {
        return $this->isMasterUser() ? (int) $shop : (int) $this->sessionShopCd();
    }

    /** `d` (Ymd) → 'Y/m/d'; sin `d` (o no válido), hoy */
    private function printDate(?string $d): string
    {
        return ($this->parseYmd($d) ?? now('Asia/Tokyo'))->format('Y/m/d');
    }

    /** Mes de yoyaku_list_calendar.php: `dd` ('Y/n/…') tiene prioridad sobre `year` + `mon` */
    private function listMonth(Request $request): ?Carbon
    {
        $dd = $this->stringParam($request, 'dd');
        if ($dd !== null && $dd !== '') {
            [$year, $mon] = array_pad(explode('/', $dd), 2, '');

            return $this->monthOf($year, $mon);
        }

        return $this->monthOf((string) $this->stringParam($request, 'year'), (string) $this->stringParam($request, 'mon'));
    }

    private function monthOf(string $year, string $mon): ?Carbon
    {
        if (! preg_match('/^\d{4}$/', $year) || ! preg_match('/^\d{1,2}$/', $mon) || (int) $mon < 1 || (int) $mon > 12) {
            return null;
        }

        return Carbon::create((int) $year, (int) $mon, 1, 0, 0, 0, 'Asia/Tokyo');
    }

    /** CTI 電話予約モード: solo si llegan isCti=1, tel y customerCd */
    private function ctiParams(Request $request): array
    {
        $tel = $this->stringParam($request, 'tel');
        $customerCd = $this->stringParam($request, 'customerCd');
        if ($this->stringParam($request, 'isCti') !== '1' || blank($tel) || blank($customerCd)) {
            return [];
        }

        return ['isCti' => '1', 'tel' => $tel, 'customerCd' => $customerCd];
    }

    private function stringParam(Request $request, string $key): ?string
    {
        $value = $request->input($key);

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
}
