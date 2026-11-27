<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\BulkStoreReservationSlotRequest;
use App\Http\Requests\Reservations\StoreReservationSlotRequest;
use App\Models\Reservations\ReservationSlot;
use App\Models\Staff;
use App\Services\Reservations\ReservationSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 受付日設定 (legacy: category9/yoyaku/yoyaku_uketsuke_day.php, _register.php, _delete.php,
 * yoyaku_multi_uketsuke_day.php, _register.php y ajax_uketsuke_status.php). Ventanas emergentes sin yoyaku_navi.
 */
class ReservationSlotController extends Controller
{
    use SelectsLegacyShop;

    /** Filas por página del 予約者一覧 (el legacy ponía kensu = 10 si no venía) */
    private const DEFAULT_KENSU = 10;

    public function __construct(private readonly ReservationSlotService $slots)
    {
    }

    /**
     * Formulario de la franja de un día. Parámetros del legacy: `cd` (edición) o `staff` + `yoyaku_date`
     * ('Ymd' o 'YmdHi', alta); desde 時間選択 llegan además `kijun_time`, `menu[]`, `option_menu[]`… para volver.
     */
    public function edit(Request $request): View
    {
        $slot = null;
        if (filled($request->query('cd'))) {
            // El legacy hacía exit (página en blanco) si no existía la franja o su empleado
            $slot = ReservationSlot::withDeleted()->with(['staff' => fn ($q) => $q->withDeleted()])->findOrFail((int) $request->query('cd'));
            abort_if($slot->staff === null, 404);
            $staff = $slot->staff;
            $ymd = str_replace('/', '', (string) $slot->moushikomi_date);
        } else {
            $staff = Staff::withDeleted()->findOrFail((int) $request->query('staff'));
            $ymd = (string) $request->query('yoyaku_date');
        }

        $shopCd = filled($staff->shop_cd) ? (int) $staff->shop_cd : null;
        $config = $this->slots->shopConfig($shopCd);
        $kensu = (int) ($request->query('kensu') ?: self::DEFAULT_KENSU);

        return view('admin.reservas.slots.edit', [
            'slot' => $slot,
            'staffName' => $staff->name,
            'staffCd' => $staff->cd,
            'shopCd' => $shopCd,
            'staffLabel' => $this->slots->manageWord('スタッフ名', 'c9_yoyaku[スタッフ]'),
            'displayDate' => $this->displayDate($ymd),
            'config' => $config,
            'start' => ReservationSlotService::toHourMin($slot?->start_time),
            'end' => ReservationSlotService::toHourMin($slot?->end_time),
            'getData' => $this->calendarQuery($request),
            'isIpad' => str_contains((string) $request->userAgent(), 'iPad'),
            'reservations' => $slot ? $this->slots->reservationList($slot, max(1, $kensu), (int) $request->query('page', 1)) : null,
            'pageQuery' => $request->except(['page', 'kensu']),
        ]);
    }

    public function store(StoreReservationSlotRequest $request): RedirectResponse
    {
        $isNew = $request->input('act') === 'new';
        $slot = $this->slots->save(
            $isNew ? null : (int) $request->input('cd'),
            (int) $request->input('staff'),
            $isNew ? ReservationSlotService::dateFromYmd($request->input('yoyaku_date')) : null,
            $request->slotTimes(),
        );

        // TODO(S2 R3): 時間選択 aún no existe; hasta entonces se vuelve a la propia franja
        if ($request->has('kijun_time') && $request->has('yoyaku_date') && \Illuminate\Support\Facades\Route::has('admin.reservas.calendar-select')) {
            // Vuelve a 時間選択 (yoyaku_calendar.php) con los mismos parámetros
            return redirect()->to(route('admin.reservas.calendar-select') . '?' . $request->input('getData') . '&lastact=reg');
        }

        return redirect()->route('admin.reservas.slots.edit', ['cd' => $slot->cd, 'lastact' => 'reg']);
    }

    /** yoyaku_uketsuke_delete.php: borra la franja y muestra 「予約受付削除しました。」, que recarga el 予約表 */
    public function destroy(int $slot): View
    {
        $model = ReservationSlot::withDeleted()->findOrFail($slot);
        // Con reservas vigentes la pantalla no ofrece el botón; si llega igual, no se borra
        abort_unless($this->slots->destroy($model), 409);

        return view('admin.reservas.slots.deleted');
    }

    /** yoyaku_multi_uketsuke_day.php: `days[]` ('Y/m/d'), `shop`, `staff`, `d`, `bd` desde スタッフ別予約表 */
    public function bulkCreate(Request $request): View
    {
        $shopCd = $this->resolveShopCd(null, $request->query('shop'));
        $days = collect((array) $request->query('days', []))
            ->map(fn ($day) => (string) $day)
            ->map(fn ($day) => ['value' => $day, 'weekday' => ReservationSlotService::weekday(str_replace('/', '', $day))]);

        return view('admin.reservas.slots.bulk', [
            'days' => $days,
            'shopCd' => $shopCd,
            'config' => $this->slots->shopConfig($shopCd),
        ]);
    }

    public function bulkStore(BulkStoreReservationSlotRequest $request): RedirectResponse
    {
        $this->slots->bulkSave(
            (int) $request->input('staff'),
            array_values(array_unique($request->input('days'))),
            $request->slotTimes(),
        );

        // TODO(S2 R4): スタッフ別予約表 aún no existe; hasta entonces se vuelve al alta de varios días
        if (! \Illuminate\Support\Facades\Route::has('admin.reservas.timetable.staff')) {
            return redirect()->route('admin.reservas.slots.bulk', $request->only('shop', 'staff', 'd'))->with('lastact', 'reg');
        }

        return redirect()->route('admin.reservas.timetable.staff', [
            'shop' => $request->input('shop'),
            'staff' => $request->input('staff'),
            'd' => $request->input('d'),
            'bd' => $request->input('bd'),
        ]);
    }

    /**
     * ajax_uketsuke_status.php (lo llama setStatus() del 予約表 por GET con td, sess, staffCd, statusFlg).
     * Respuesta como el legacy: cuerpo vacío si fue bien (el JS hace location.reload()); si no, el texto del error
     * (el JS muestra 「エラーが発生しました。。。」+texto). Siempre HTTP 200 y text/html.
     */
    public function status(Request $request): Response
    {
        $error = ReservationSlotService::statusRequestError(
            $request->input('staffCd'),
            $request->input('td'),
            $request->input('sess'),
            $request->session()->getId(),
        );
        if ($error !== null) {
            return response($error);
        }

        $this->slots->changeStatus((int) $request->input('staffCd'), (string) $request->input('td'), (string) $request->input('statusFlg', ''));

        return response('');
    }

    /** 'YYYY年MM月DD日(曜) ' como el legacy (el día de la semana con Carbon, sin mktime) */
    private function displayDate(string $ymd): string
    {
        $weekday = ReservationSlotService::weekday($ymd);

        return substr($ymd, 0, 4) . '年' . substr($ymd, 4, 2) . '月' . substr($ymd, 6, 2) . '日'
            . ($weekday !== null ? '(' . $weekday . ') ' : ' ');
    }

    /**
     * `$getData` del legacy: la consulta para volver a 時間選択 (solo si llegan kijun_time y yoyaku_date).
     */
    private function calendarQuery(Request $request): ?string
    {
        if (! $request->has('kijun_time') || ! $request->has('yoyaku_date')) {
            return null;
        }

        $parts = [
            'kaiin=' . rawurlencode((string) $request->query('kaiin')),
            'shop=' . rawurlencode((string) $request->query('shop')),
            'staff=' . rawurlencode((string) $request->query('staff')),
        ];
        foreach (['menu', 'option_menu', 'free_koumoku'] as $list) {
            foreach ((array) $request->query($list, []) as $key => $value) {
                if (is_scalar($value)) {
                    $parts[] = $list . '[' . rawurlencode((string) $key) . ']=' . rawurlencode((string) $value);
                }
            }
        }
        foreach (['kijun_time', 'yoyaku_date', 'name', 'name_kana', 'tel', 'mail', 'people'] as $field) {
            $value = $request->query($field);
            $parts[] = $field . '=' . rawurlencode(is_scalar($value) ? (string) $value : '');
        }

        return implode('&', $parts);
    }
}
