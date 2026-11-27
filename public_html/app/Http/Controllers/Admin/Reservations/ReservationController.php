<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Models\Reservations\Reservation;
use App\Services\Reservations\AvailabilityService;
use App\Services\Reservations\ReservationCapacityException;
use App\Services\Reservations\ReservationFlowService;
use App\Services\Reservations\ReservationService;
use App\Services\Reservations\ReservationSlotMissingException;
use App\Services\Reservations\ReservationSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Flujo de reserva de la administración (ventanas emergentes del 予約表, legacy category9/yoyaku/):
 * yoyaku.php (予約) → yoyaku_calendar.php (時間選択) → yoyaku_customer_select.php (顧客情報入力)
 * → yoyaku_register.php; ajax_yoyaku_register.php / ajax_move_objs.php (popup y arrastre del 予約表)
 * y related_customer.php / ajax_related_customer.php (顧客関連づけ).
 */
class ReservationController extends Controller
{
    use SelectsLegacyShop;

    /** Clave de sesión del anti doble envío (`$_SESSION['register_verify']`) */
    private const VERIFY_KEY = 'reservas.register_verify';

    public function __construct(
        private readonly ReservationFlowService $flow,
        private readonly ReservationService $reservations,
    ) {}

    /** yoyaku.php: datos del cliente, empleado y menús (los menús los pinta ajax_yoyaku_menu.php) */
    public function create(Request $request): View
    {
        $input = ReservationFlowService::clean($request->query());

        $kijunTime = null;
        if (($input['td'] ?? '') !== '') {
            $td = (string) $input['td'];
            $kijunTime = checkdate((int) substr($td, 4, 2), (int) substr($td, 6, 2), (int) substr($td, 0, 4))
                ? Carbon::create((int) substr($td, 0, 4), (int) substr($td, 4, 2), (int) substr($td, 6, 2), 0, 0, 0, 'Asia/Tokyo')->getTimestamp()
                : null;
        } elseif (($input['kijun_time'] ?? '') !== '') {
            $kijunTime = $input['kijun_time'];
        }

        // Menús marcados al volver de 時間選択 (solo si llegan menu, shop y staff)
        $menuCds = (($input['menu'] ?? '') !== '' && ($input['shop'] ?? '') !== '' && ($input['staff'] ?? '') !== '')
            ? implode(',', ReservationFlowService::menuCds($input['menu'] ?? null, $input['option_menu'] ?? null))
            : '';

        $isMaster = $this->isMasterUser();
        $people = is_numeric($input['people'] ?? null) && (float) $input['people'] >= 2 ? (int) $input['people'] : 1;

        return view('admin.reservas.create', [
            'input' => $input,
            'koumoku' => $this->flow->koumoku(),
            'freeFields' => $this->flow->freeFields(),
            'staffWord' => $this->flow->manageWord('スタッフ', 'c9_yoyaku[スタッフ]'),
            'staffWorkWord' => $this->flow->manageWord('スタッフ施術', 'c9_yoyaku[スタッフ]'),
            'siteName' => app(\App\Services\Nucleo\LegacyUi::class)->siteName(),
            'memberLabel' => $this->flow->memberLabel($input['kaiin'] ?? null),
            'isMaster' => $isMaster,
            'sessionShopCd' => $this->sessionShopCd(),
            'sessionShopName' => $this->shopName($this->sessionShopCd()),
            'shops' => $isMaster ? $this->shopOptions() : collect(),
            'menuCds' => $menuCds,
            'people' => $people,
            'kijunTime' => $kijunTime,
            'isIpad' => str_contains((string) $request->userAgent(), 'iPad'),
        ]);
    }

    /** yoyaku_calendar.php: franjas del día del empleado para los menús elegidos */
    public function calendar(Request $request, AvailabilityService $availability): View|Response
    {
        $input = ReservationFlowService::clean($request->query());
        if (blank($input['shop'] ?? null) || blank($input['staff'] ?? null) || blank($input['menu'] ?? null)) {
            return response('キーコードがありません。');
        }

        $shopCd = (int) $input['shop'];
        $staffCd = (int) $input['staff'];
        $menuCds = ReservationFlowService::menuCds($input['menu'] ?? null, $input['option_menu'] ?? null);
        $menus = $this->flow->staffMenus($staffCd, $menuCds);

        // Sin kijun_time, el legacy empezaba dos días antes de hoy
        $kijunTime = is_numeric($input['kijun_time'] ?? null) ? (int) $input['kijun_time'] : Carbon::now('Asia/Tokyo')->getTimestamp() - 86400 * 2;
        $day = $availability->day($shopCd, $staffCd, $kijunTime, $menuCds, (int) $menus->sum('shoyou_time'), $input['people'] ?? null);
        $date = $day['day'];

        $getData = ReservationFlowService::query($input);
        $ymd = $date->format('Ymd');

        return view('admin.reservas.calendar', [
            'input' => $input,
            'getData' => $getData,
            'kijunTime' => $kijunTime,
            'preKijun' => $kijunTime - 86400,
            'nextKijun' => $kijunTime + 86400,
            'dateTitle' => $date->format('Y年m月d日'),
            'weekday' => ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek],
            'shopName' => $this->flow->shopName($shopCd),
            'staffName' => $this->flow->staff($staffCd)?->name,
            'menus' => $menus,
            'ymd' => $ymd,
            'day' => $day,
            'slotEditUrl' => route('admin.reservas.slots.edit') . '?' . ($day['slot'] ? 'cd=' . $day['slot']->cd . '&' : '')
                . $getData . '&kijun_time=' . $kijunTime . '&yoyaku_date=' . $ymd . ($day['rows'][0]['hhmm'] ?? ''),
        ]);
    }

    /** yoyaku_customer_select.php: 顧客情報入力 (socio o datos libres) antes de registrar */
    public function customerSelect(Request $request): View|Response
    {
        $input = ReservationFlowService::clean($request->query());
        foreach (['shop', 'staff', 'menu', 'yoyaku_date'] as $key) {
            if (blank($input[$key] ?? null)) {
                return response('キーコードがありません。');
            }
        }

        $staffCd = (int) $input['staff'];
        $menus = $this->flow->staffMenus($staffCd, ReservationFlowService::menuCds($input['menu'] ?? null, $input['option_menu'] ?? null));

        // 2012.04.16: valores iniciales 「ゲスト」
        $input['name'] = ($input['name'] ?? '') === '' ? 'ゲスト' : $input['name'];
        $input['name_kana'] = ($input['name_kana'] ?? '') === '' ? 'ゲスト' : $input['name_kana'];

        $ymd = (string) $input['yoyaku_date'];
        $weekday = ReservationSlotService::weekday(substr($ymd, 0, 8));
        $hour = substr($ymd, 8, 2);
        $dispDate = substr($ymd, 0, 4) . '年' . substr($ymd, 4, 2) . '月' . substr($ymd, 6, 2) . '日'
            . '(' . ($weekday ?? '') . ') '
            . ((($input['plus'] ?? null) == '1') ? (int) $hour + 24 : $hour) . '時' . substr($ymd, 10, 2) . '分～';

        $mailSendStatus = DB::table('contents_master')->where('category', 'tb_c9_yoyaku')->where('field', 'mail_send_status')
            ->orderByDesc('update_date')->value('text_data');

        return view('admin.reservas.customer_select', [
            'input' => $input,
            'getData' => ReservationFlowService::query($input, [
                'yoyaku_date' => (string) $input['yoyaku_date'],
                'kijun_time' => (string) ($input['kijun_time'] ?? ''),
            ]),
            'koumoku' => $this->flow->koumoku(),
            'freeFields' => $this->flow->freeFields(),
            'shopName' => $this->flow->shopName($input['shop']),
            'staffName' => $this->flow->staff($staffCd)?->name,
            'staffLabel' => $this->flow->manageWord('担当スタッフ', 'c9_yoyaku[スタッフ]'),
            'menus' => $menus,
            'totalMinutes' => (int) $menus->sum('shoyou_time'),
            'dispDate' => $dispDate,
            'showOshirase' => (string) $mailSendStatus !== '1',
            'memberLabel' => $this->flow->memberLabel($input['kaiin'] ?? null),
            'registerVerify' => Carbon::now('Asia/Tokyo')->getTimestamp(),
        ]);
    }

    /** yoyaku_register.php: alta, correo opcional y cierre de la ventana (recarga el 予約表) */
    public function store(StoreReservationRequest $request): View|RedirectResponse
    {
        $input = ReservationFlowService::clean($request->except(['_token']));

        // ダブルクリック、リロードを防ぐ
        $verify = (string) ($input['register_verify'] ?? '');
        if ($request->session()->get(self::VERIFY_KEY) !== $verify) {
            $request->session()->put(self::VERIFY_KEY, $verify);

            try {
                $reservation = $this->reservations->create($input);
            } catch (ReservationSlotMissingException) {
                abort(404);
            } catch (ReservationCapacityException $e) {
                $request->session()->forget(self::VERIFY_KEY);

                return redirect()->to(route('admin.reservas.customer-select') . '?' . ReservationFlowService::query($input, [
                    'yoyaku_date' => (string) ($input['yoyaku_date'] ?? ''),
                    'kijun_time' => (string) ($input['kijun_time'] ?? ''),
                ] + ((($input['plus'] ?? '') === '1') ? ['plus' => '1'] : [])))->with('reservationError', $e->getMessage());
            }

            if (($input['oshirase'] ?? null) == '1') {
                $this->reservations->notifyRegistered($reservation, $input);
            }
        }

        return view('admin.reservas.registered', [
            'shopCd' => (string) ($input['shop'] ?? ''),
            'retDay' => substr((string) ($input['yoyaku_date'] ?? ''), 0, 8),
            'isIpad' => str_contains((string) $request->userAgent(), 'iPad'),
        ]);
    }

    /**
     * Cambio desde el popup del 予約表 (mismos parámetros y respuesta que ajax_yoyaku_register.php sin act=del):
     * '' si fue bien; si no, el texto del error.
     */
    public function update(Request $request, int $reservation): Response
    {
        return $this->textResponse(fn () => $this->reservations->update($reservation, ReservationFlowService::clean($request->all())));
    }

    /** ajax_yoyaku_register.php (URL legacy, GET): comprueba `sess` y da de baja (act=del) o modifica */
    public function ajaxRegister(Request $request): Response
    {
        if ((string) $request->input('sess') !== $request->session()->getId()) {
            return response('不正アクセス！！');
        }

        $cd = (int) $request->input('cd');
        if ($request->input('act') === 'del') {
            return $this->textResponse(fn () => $this->reservations->cancel($cd, $request->input('sendMailFlg') == '1'));
        }

        return $this->update($request, $cd);
    }

    /** ajax_move_objs.php: mover a otro empleado o cambiar día y hora ('' si fue bien) */
    public function move(Request $request): Response
    {
        $input = ReservationFlowService::clean($request->only(['reserveDate', 'startTime', 'sendMailFlg', 'newStaff', 'objs']));

        return $this->textResponse(fn () => $this->reservations->move((int) $request->input('cd'), $input));
    }

    /** related_customer.php: buscar el socio al que se liga una reserva de no socio */
    public function relatedCustomer(Request $request): View|Response
    {
        if (blank($request->input('yoyaku_cd'))) {
            return response('');
        }

        $reservation = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->where('tb1.cd', (int) $request->input('yoyaku_cd'))
            ->first(['tb1.cd', 'tb1.name', 'tb1.name_kana', 'tb2.moushikomi_date']);

        return view('admin.reservas.related_customer', [
            'yoyakuCd' => (int) $request->input('yoyaku_cd'),
            'reservation' => $reservation,
            'siteName' => app(\App\Services\Nucleo\LegacyUi::class)->siteName(),
        ]);
    }

    /** ajax_related_customer.php: responde '1' (el JS recarga) */
    public function storeRelatedCustomer(Request $request): Response
    {
        if (blank($request->input('yoyaku_cd')) && blank($request->input('customer_cd'))) {
            return response('');
        }
        $this->reservations->relateCustomer((int) $request->input('yoyaku_cd'), (int) $request->input('customer_cd'));

        return response('1');
    }

    /** Respuesta de texto que espera el JS del 予約表: '' o el error (como el pg_last_error del legacy) */
    private function textResponse(\Closure $action): Response
    {
        try {
            return response((string) $action());
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);

            return response('ERROR:' . class_basename($e));
        }
    }
}
