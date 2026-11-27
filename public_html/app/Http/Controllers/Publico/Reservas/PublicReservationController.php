<?php

namespace App\Http\Controllers\Publico\Reservas;

use App\Http\Controllers\Controller;
use App\Services\Reservations\ReservationFlowService;
use App\Services\Reservations\WebReservationBookingService;
use App\Services\Reservations\WebReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Reserva web pública del legacy `r/` (y su alias `reserve/`): integración externa (web de cada tienda en iframe,
 * appview/reservation.php). Mismas URL, parámetros ($_REQUEST: GET o POST), JSON y textos de error.
 *
 * Sin login: la sesión (`mobile.*`, la de マイページ / token de マイコレ) y el parámetro `sess` (= id de sesión)
 * hacen de control, como en el legacy. `reserve/` era la variante POS (catálogo `tb_c2_*` del servidor de la
 * tienda): queda descartada y sus URL responden con la lógica CRM de `r/`.
 */
class PublicReservationController extends Controller
{
    /** _DAYS_TO_DISPLAY_ */
    private const DAYS_TO_DISPLAY = 7;

    public function __construct(
        private readonly WebReservationService $web,
        private readonly WebReservationBookingService $booking,
        private readonly ReservationFlowService $flow,
    ) {}

    /** r/index.php: 予約フォーム */
    public function index(Request $request): Response|RedirectResponse
    {
        // Como el legacy: siempre por HTTPS (en local y en pruebas no hay certificado)
        if (! $request->secure() && app()->isProduction()) {
            return redirect()->to('https://'.$request->getHttpHost().$request->getRequestUri(), 301);
        }

        // マイコレがONでなければ不正アクセス; con 店舗グループ hace falta shop_cd
        $admin = DB::table('admin_master')->where('cd', 1)->first(['application_use_flg', 'shop_category_flg']);
        if ($admin !== null) {
            if ((string) $admin->application_use_flg !== '1') {
                return $this->text('不正アクセス。');
            }
            if (! $request->input('shop_cd') && (string) $admin->shop_category_flg === '1') {
                return $this->text('パラメータが正しくありません。');
            }
        }

        $this->loginByToken($request);

        $shopCd = $request->input('shop_cd');
        $shops = $this->shops(is_scalar($shopCd) ? (string) $shopCd : '');

        // Semana mostrada: hoy + 6 días, o la pedida si no acaba antes de hoy
        $now = WebReservationService::now();
        $startDate = WebReservationService::date('Y-m-d', $now);
        $endDate = WebReservationService::date('Y-m-d', $now + 86400 * (self::DAYS_TO_DISPLAY - 1));
        $requestedStart = $request->input('start_date');
        if (is_scalar($requestedStart) && (string) $requestedStart !== '') {
            $startUt = (int) WebReservationService::ut((string) $requestedStart);
            $endUt = $startUt + 86400 * (self::DAYS_TO_DISPLAY - 1);
            if ($endUt >= WebReservationService::ut('today')) {
                $startDate = (string) $requestedStart;
                $endDate = WebReservationService::date('Y-m-d', $endUt);
            }
        }

        $defaults = [];
        if ($shopCd && is_scalar($shopCd) && isset($shops[(int) $shopCd])) {
            $defaults['shop_cd'] = $shopCd;
            if ($request->input('staff_cd')) {
                $defaults['staff_cd'] = $request->input('staff_cd');
            }
            if ($request->input('menu_cd')) {
                $defaults['menu_cd'] = $request->input('menu_cd');
            }
        }

        $mobile = (array) session('mobile', []);

        return response()->view('publico.reservas.index', [
            'now' => $now,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sess' => session()->getId(),
            'shops' => $shops,
            'defaults' => $defaults,
            'koumoku' => $this->flow->koumoku(),
            'freeFields' => $this->flow->freeFields(),
            'mobile' => $mobile,
            'customerReserves' => ! empty($mobile['user_cd']) ? $this->web->customerActiveReserves($mobile['user_cd']) : [],
            'token' => is_scalar($request->input('token')) ? (string) $request->input('token') : '',
            'iphoneApp' => $this->isIphoneApp((string) $request->userAgent()),
        ]);
    }

    /** r/check.php: 登録済み予約一覧 (con 取消) */
    public function check(Request $request): Response
    {
        $freeTimelineStaff = $freeName = [];
        foreach (DB::table('contents_master')->where('category', 'tb_c9_yoyaku')->where('field', 'free_yoyaku_timeline_staff')->where('disp_flg', '1')->get() as $row) {
            $freeTimelineStaff[$row->int_data] = (bool) $row->text_data;
        }
        foreach (DB::table('contents_master')->where('category', 'tb_c9_yoyaku')->where('field', 'free_yoyaku_name')->where('disp_flg', '1')->get() as $row) {
            $freeName[$row->int_data] = $row->text_data ?: '指名なし';
        }

        $menuNames = DB::table('tb_yoyaku_koumoku_master')->orderBy('cd')->pluck('name', 'cd')->all();

        $reserves = [];
        foreach ($this->web->customerActiveReserves(session('mobile.user_cd')) as $reserve) {
            $menuArray = array_filter(explode(',', $reserve['base_tb_yoyaku_koumoku_master_cd'].','.$reserve['option_tb_yoyaku_koumoku_master_cd']));
            $reserve['menuDisplay'] = implode('、', array_map(fn ($cd) => (string) ($menuNames[$cd] ?? ''), $menuArray));

            // 担当者を出すか出さないか (フリー予約)
            if (empty($freeTimelineStaff[$reserve['shop_cd']]) && in_array($reserve['entry_type'], ['5', '101'], true)) {
                $reserve['staff_name'] = $freeName[$reserve['shop_cd']] ?? null;
            }
            $reserve['dispReserveTime'] = $reserve['reservation_date'].'('.$reserve['youbi'].') '.$reserve['displayStartTime'].'～';
            $reserves[] = $reserve;
        }

        return response()->view('publico.reservas.check', [
            'now' => WebReservationService::now(),
            'sess' => session()->getId(),
            'reserves' => $reserves,
            'backQuery' => http_build_query($request->except(['_token'])),
        ]);
    }

    /** r/ajax_getScheduleData.php */
    public function schedule(Request $request): Response
    {
        $in = $request->all();
        if (! $this->validDates($in) || (! empty($in['staff_cd']) && ! preg_match(WebReservationService::CD_PATTERN, $this->str($in, 'staff_cd')))) {
            return $this->json(['status' => 'error', 'errorMsg' => '不正アクセス。']);
        }

        return $this->json(['status' => 'ok', 'data' => $this->web->scheduleData($in, $this->web->crmConfig())], ['data']);
    }

    /** r/ajax_getChoiceData.php: empleados, menús y ajustes del formulario */
    public function choices(Request $request): Response
    {
        $in = $request->all();
        if (! $this->validDates($in) || (! empty($in['staff_cd']) && ! preg_match(WebReservationService::CD_PATTERN, $this->str($in, 'staff_cd')))) {
            return $this->json(['status' => 'error', 'errorMsg' => '不正アクセス']);
        }

        $shopCd = $this->str($in, 'shop_cd');
        $staffCd = $in['staff_cd'] ?? null;
        $kind = $in['kind'] ?? null;
        $staffList = $this->web->shopStaff($shopCd, $staffCd) ?: [];
        $staffMenuList = $this->web->staffMenu($shopCd, $staffCd) ?: [];
        $menuCds = $in['menu_cd'] ?? null;

        $data = [];
        if (! $kind || $kind == 'staff') {
            if ($kind == 'staff' && $staffList) {
                // Solo quien hace todos los menús marcados
                $data['staff'] = [];
                $wanted = is_array($menuCds) ? count($menuCds) : (isset($in['menu_cd']) ? 1 : 0);
                foreach ($staffList as $staff) {
                    $cnt = 0;
                    foreach ($staffMenuList[$staff['cd']] ?? [] as $menu) {
                        if (is_array($menuCds) && in_array($menu['menu_cd'], $menuCds)) {
                            $cnt++;
                        }
                        if ($cnt == $wanted) {
                            $data['staff'][] = $staff;
                            break;
                        }
                    }
                }
            } else {
                $data['staff'] = $staffList;
            }

            foreach ($data['staff'] as $key => $staff) {
                [$exists, $mtime] = $this->web->uploadInfo('hp/uploads/c5/staff/'.$staff['cd'].'/'.$staff['cd'].'.jpg');
                $data['staff'][$key]['hasImage'] = $exists && $staff['pic_name'] == '1';
                $data['staff'][$key]['imgLastModify'] = $data['staff'][$key]['hasImage'] ? $mtime : 0;
            }
        }

        if (! $kind || $kind == 'menu') {
            $data['menu'] = [];
            $seen = [];
            foreach ($staffList as $staff) {
                foreach ($staffMenuList[$staff['cd']] ?? [] as $menu) {
                    if (in_array($menu['menu_cd'], $seen)) {
                        continue;
                    }
                    $seen[] = $menu['menu_cd'];
                    $data['menu'][] = $menu;
                }
            }
            foreach ($data['menu'] as $key => $menu) {
                [$exists, $mtime] = $this->web->uploadInfo('hp/uploads/c9/yoyaku_koumoku/'.$menu['menu_cd'].'/1_s.jpg');
                $data['menu'][$key]['hasImage'] = $exists;
                $data['menu'][$key]['imgLastModify'] = $exists ? $mtime : 0;
            }
        }

        if (! $kind) {
            $data['settings'] = $this->settings($shopCd);
        }

        return $this->json(['status' => 'ok', 'data' => $data], ['data']);
    }

    /** r/ajax_reserveRegister.php */
    public function register(Request $request): Response
    {
        return $this->json($this->booking->register($request->all(), session()->getId(), session('mobile.user_cd')));
    }

    /** r/ajax_reserveDelete.php */
    public function cancel(Request $request): Response
    {
        return $this->json($this->booking->cancel($request->all(), session()->getId(), session('mobile.user_cd')));
    }

    /** r/getReservationWaitTime.php (QuickReservation::getReservationWaitTime) */
    public function waitTime(Request $request): Response
    {
        $result = $this->web->waitTime($request->input('shop_cd'));
        if (! $result) {
            return $this->json(['status' => 'error', 'errorMsg' => '予約できません。']);
        }

        return $this->json([
            'status' => 'ok',
            'waitTime' => max($result['waitTime'], 10),
            'minReservationDatetime' => WebReservationService::date('Y-m-d H:i', $result['minReservationUT']),
            'minReservationUT' => $result['minReservationUT'],
        ]);
    }

    // ---------------------------------------------------------------- apoyo

    /** settings de ajax_getChoiceData.php (sin `kind`) */
    private function settings(string $shopCd): array
    {
        $config = $this->web->config($shopCd);

        // 「営業時間中の予約受付」しない場合
        $businessHoursReservationError = false;
        if (($config['uketuke_type'] ?? null) == '1') {
            $now = WebReservationService::now();
            $today = WebReservationService::date('Y-m-d', $now);
            $todaySchedule = $this->web->shopDateSchedule($shopCd, $today, $today);
            if ($todaySchedule && $todaySchedule[$today]['startTimeUT'] <= $now && $now < $todaySchedule[$today]['endTimeUT']) {
                $businessHoursReservationError = true;
            }
        }

        $crm = $this->web->crmConfig();
        $comments = DB::table('tb_manage_words')->orderBy('cd')->pluck('naiyou', 'content_name')->all();
        $contents = fn (string $field) => (string) DB::table('contents_master')->where('category', 'tb_c9_yoyaku')->where('field', $field)
            ->where('int_data', (int) $shopCd)->where('disp_flg', '1')->value('text_data');
        $freeYoyaku = ($crm['free_yoyaku_flg'] ?? null) == '1';
        $comment = fn (string $key) => nl2br((string) ($comments['mobile_yoyaku['.$key.']'] ?? ''));

        return [
            'showMenuPrice' => true,
            'showEstimatedTime' => ($crm['meyasu_jikan_flg'] ?? '') == '',
            'allowFreeReserve' => $freeYoyaku && $contents('free_yoyaku_disp_flg') == '1',
            'allowNominationWhenFreeReserveIsAllowed' => $freeYoyaku && $contents('free_yoyaku_staff_select') == '1',
            'freeReserveName' => $contents('free_yoyaku_name'),
            'selectStaffAnnotation' => $comment('V2予約のスタッフ選択箇所'),
            'selectMenuAnnotation' => $comment('V2予約のメニュー選択箇所'),
            'selectShopAnnotation' => $comment('V2予約の店舗選択箇所'),
            'selectTimeAnnotation' => $comment('V2予約の日時選択箇所'),
            'customerFormAnnotation' => $comment('V2予約の顧客情報入力箇所'),
            'businessHoursReservationError' => $businessHoursReservationError,
        ];
    }

    /** Tiendas visibles (con 店舗グループ, las del grupo de la tienda pedida): [cd => [cd, name, tel]] */
    private function shops(string $shopCd): array
    {
        $query = DB::table('tb_shop as res')
            ->select('cd', 'kaishamei as name', 'tel')
            ->where('res.disp_flg', '1')
            ->whereRaw("COALESCE(res.hyouji_flg, '') <> '9'")
            ->orderBy('res.cd');
        if ($shopCd !== '') {
            $cd = (int) $shopCd;
            $query->whereRaw(<<<'SQL'
                1 = (SELECT CASE
                    WHEN (SELECT shop_category_flg FROM admin_master WHERE cd = 1) = 0 OR (SELECT shop_category_flg FROM admin_master WHERE cd = 1) IS NULL THEN 1
                    WHEN shop_category_cd > 0 AND res.shop_category_cd = shop_category_cd THEN 1
                    WHEN (shop_category_cd IS NULL OR shop_category_cd = 0) AND res.cd = ? THEN 1
                    ELSE 0 END
                FROM tb_shop WHERE cd = ?)
                SQL, [$cd, $cd]);
        }

        $shops = [];
        foreach ($query->get() as $row) {
            $shops[(int) $row->cd] = WebReservationService::pgRow($row);
        }

        return $shops;
    }

    /** マイコレから: token = md5(cd:touroku_date) del socio → sesión `mobile` */
    private function loginByToken(Request $request): void
    {
        $token = $request->input('token');
        if (! is_scalar($token) || (string) $token === '' || ! empty(session('mobile.user_id'))) {
            return;
        }
        $row = DB::table('tb_customer')
            ->whereRaw("md5(cd || ':' || touroku_date) = ?", [(string) $token])
            ->where('disp_flg', '1')
            ->where('touroku_kubun', '2')
            ->first(['cd', 'id', 'name', 'email1', 'tel']);
        if ($row !== null) {
            session()->put('mobile', array_merge((array) session('mobile', []), [
                'user_cd' => (string) $row->cd,
                'user_id' => (string) $row->id,
                'user_name' => $row->name,
                'email1' => $row->email1,
                'tel' => $row->tel,
            ]));
        }
    }

    /** iPhone dentro de una app (no Safari, Opera ni Yahoo!): el legacy subía la barra de botones 50 px */
    private function isIphoneApp(string $ua): bool
    {
        if (preg_match('/Android/', $ua) || ! preg_match('/iPhone|iPad|iPod/', $ua)) {
            return false;
        }
        $device = explode(';', explode('(', $ua)[1] ?? '')[0];

        return $device === 'iPhone' && ! preg_match('/Safari/', $ua) && ! preg_match('/OPT/', $ua) && ! preg_match('/YJApp/', $ua);
    }

    /** shop_cd, start_date y end_date obligatorios */
    private function validDates(array $in): bool
    {
        return preg_match(WebReservationService::CD_PATTERN, $this->str($in, 'shop_cd'))
            && preg_match(WebReservationService::DATE_PATTERN, $this->str($in, 'start_date'))
            && preg_match(WebReservationService::DATE_PATTERN, $this->str($in, 'end_date'));
    }

    private function str(array $in, string $key): string
    {
        return is_scalar($in[$key] ?? null) ? (string) $in[$key] : '';
    }

    /**
     * JSON escrito como el legacy: `{ "status" : "ok", "data" : <json_encode()> }` (espacios incluidos), en text/html.
     * Los textos van entre comillas tal cual (solo se escapan " y \); las claves de $encoded van con json_encode().
     */
    private function json(array $pairs, array $encoded = []): Response
    {
        $parts = [];
        foreach ($pairs as $key => $value) {
            $parts[] = '"'.$key.'" : '.match (true) {
                in_array($key, $encoded, true) => json_encode($value),
                is_bool($value) => $value ? 'true' : 'false',
                is_int($value) || is_float($value) => (string) $value,
                default => '"'.addcslashes((string) $value, "\"\\\n\r").'"',
            };
        }

        return $this->text('{ '.implode(', ', $parts).' }');
    }

    private function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
