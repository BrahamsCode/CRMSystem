<?php

namespace App\Services\Reservations;

use App\Models\Reservations\Reservation;
use App\Services\Nucleo\Hatuban;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Alta y anulación de la reserva web pública (`r/ajax_reserveRegister.php`, `r/ajax_reserveDelete.php`).
 * Devuelven los pares clave → valor de la respuesta JSON del legacy, en su orden y con sus textos.
 *
 * Diferencias buscadas con el legacy (seguridad, sin cambiar el contrato con el JS):
 * - `customer_cd` solo se acepta si es el socio de la sesión (`mobile.user_cd`); el `token` sigue valiendo.
 * - Solo se anulan reservas del socio de la sesión (antes bastaba con conocer el cd).
 * - El aforo se vuelve a calcular dentro de la transacción, con las franjas de la tienda de ese día bloqueadas
 *   (`lockForUpdate`, en lugar de `LOCK TABLE tb_yoyaku` después de calcular).
 */
class WebReservationBookingService
{
    public const MAIL_PATTERN = '/^[_a-z0-9-\.\+\/\?\=]+(\.[_a-z0-9-\.\+\/\?\=]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i';

    public function __construct(
        private readonly WebReservationService $web,
        private readonly ReservationFlowService $flow,
        private readonly ReservationMailer $mailer,
        private readonly Hatuban $hatuban,
    ) {}

    /**
     * ajax_reserveRegister.php. $in = petición tal cual ($_REQUEST); $sessionCustomerCd = `mobile.user_cd`.
     *
     * @return array<string, mixed>
     */
    public function register(array $in, string $sessionId, mixed $sessionCustomerCd): array
    {
        $str = fn (string $key) => is_scalar($in[$key] ?? null) ? (string) $in[$key] : '';

        $exitError = $str('sess') === '' || $str('sess') !== $sessionId
            || ! preg_match(WebReservationService::CD_PATTERN, $str('shop_cd'))
            || ! preg_match(WebReservationService::DATE_PATTERN, $str('selectedDate'))
            || ! preg_match('/^[0-9]{1,2}:[0-9]{1,2}$/', $str('selectedTime'))
            || ! isset($in['menu_cd']) || ! is_array($in['menu_cd'])
            || ($str('staff_cd') && ! preg_match(WebReservationService::CD_PATTERN, $str('staff_cd')));
        if ($exitError) {
            return ['status' => 'error', 'errorMsg' => '不正アクセス。'];
        }

        // オプションメニューだけの予約を否定する
        $baseMenuList = $optionMenuList = [];
        $menuCds = array_map(fn ($v) => (int) (is_scalar($v) ? $v : 0), $in['menu_cd']);
        foreach (DB::table('tb_yoyaku_koumoku_master')->whereIn('cd', $menuCds)->orderBy('cd')->get(['cd', 'type_flg']) as $row) {
            if ((string) $row->type_flg === '2') {
                $optionMenuList[] = $row->cd;
            } else {
                $baseMenuList[] = $row->cd;
            }
        }
        if ($baseMenuList === []) {
            return ['status' => 'error', 'errorMsg' => 'オプションメニューのみで予約できません。'];
        }

        if (($error = $this->inputErrors($in)) !== '') {
            return ['status' => 'error', 'errorMsg' => $error];
        }

        $selectedDate = $str('selectedDate');
        $selectedTime = $str('selectedTime');
        $isFreeReserve = ! $str('staff_cd');
        $crmConfig = $this->web->crmConfig();
        $customer = $this->customer($in, $sessionCustomerCd);

        $result = DB::transaction(function () use ($in, $str, $selectedDate, $selectedTime, $isFreeReserve, $crmConfig, $customer, $baseMenuList, $optionMenuList) {
            // Franjas de la tienda ese día bloqueadas: dos altas a la vez no pasan del aforo
            DB::table('tb_yoyaku_uketsuke_day')
                ->whereIn('moushikomi_date', [str_replace('-', '/', $selectedDate), str_replace('/', '-', $selectedDate)])
                ->whereIn('tb_staff_cd', fn ($q) => $q->select('cd')->from('tb_staff')->where('shop_cd', (int) $str('shop_cd')))
                ->lockForUpdate()
                ->pluck('cd');

            $params = $in;
            $params['start_date'] = $params['end_date'] = $selectedDate;
            $params['listCandidates'] = '1';
            $data = $this->web->scheduleData($params, $crmConfig);
            $schedule = $data['schedule'][$selectedDate]['timetable'][$selectedTime] ?? null;

            if (empty($schedule['available'])) {
                return ['status' => 'error', 'errorMsg' => '選択した日時の予約できる人数を超えた為登録できませんでした。お手数ですが別の日時を選択してください。', 'refreshSchedule' => true];
            }

            // フリー予約時は1人目
            $staffCd = $schedule['candidates'][0] ?? null;
            $shift = $this->web->staffDateShift($str('shop_cd'), $selectedDate, $selectedDate, $staffCd)[$selectedDate]['staff'][$staffCd] ?? null;
            if (! $shift) {
                return ['status' => 'error', 'errorMsg' => 'パラメータが正しくありません。'];
            }
            $requiredTime = (int) ($data['requiredTime'][$staffCd] ?? 0);

            $start = $this->web->realTime($selectedDate, $selectedTime) - (int) WebReservationService::ut($selectedDate);
            $reservation = new Reservation([
                'tb_yoyaku_uketsuke_day_cd' => (int) $shift['tb_yoyaku_uketsuke_day_cd'],
                'tel' => $str('tel'),
                'free_koumoku' => $this->flow->encodeFreeFields(is_array($in['free_koumoku'] ?? null) ? $in['free_koumoku'] : []),
                'start_time' => $start,
                'end_time' => $start + $requiredTime * 60,
                'base_tb_yoyaku_koumoku_master_cd' => implode(',', $baseMenuList),
                'option_tb_yoyaku_koumoku_master_cd' => implode(',', $optionMenuList),
                'people' => 1,
                'entry_type' => $isFreeReserve ? Reservation::ENTRY_PUBLIC_WEB_FREE : Reservation::ENTRY_PUBLIC_WEB,
                'hyouji_flg' => '1',
                'disp_flg' => '1',
            ]);
            if ($customer !== null) {
                $reservation->tb_customer_cd = (int) $customer->cd;
                if ((string) $customer->email1 === '' && $str('mail') !== '') {
                    $reservation->mail = $str('mail');
                }
            } else {
                $reservation->name = $str('name');
                $reservation->name_kana = $str('name_kana');
                $reservation->mail = $str('mail');
            }
            $reservation->save();

            return $reservation;
        });

        if (is_array($result)) {
            return $result;
        }

        $this->mailer->actionMails((int) $result->cd, 'new');
        $this->log((int) $result->cd, '新Web予約から予約情報を新規登録しました。', $result->getAttributes(), __LINE__);

        return ['status' => 'ok', 'refreshSchedule' => true];
    }

    /**
     * ajax_reserveDelete.php
     *
     * @return array<string, mixed>
     */
    public function cancel(array $in, string $sessionId, mixed $sessionCustomerCd): array
    {
        $sess = is_scalar($in['sess'] ?? null) ? (string) $in['sess'] : '';
        $shopCd = is_scalar($in['shop_cd'] ?? null) ? (string) $in['shop_cd'] : '';
        $cd = is_scalar($in['cd'] ?? null) ? (string) $in['cd'] : '';
        if ($sess === '' || $sess !== $sessionId || ! preg_match(WebReservationService::CD_PATTERN, $shopCd) || ! preg_match(WebReservationService::CD_PATTERN, $cd)) {
            return ['status' => 'error', 'errorMsg' => '不正アクセス。'];
        }

        $reserve = $this->web->reserveByCd($cd);
        if (! $reserve || (string) $sessionCustomerCd === '' || (string) $reserve['tb_customer_cd'] !== (string) $sessionCustomerCd) {
            return ['status' => 'error', 'errorMsg' => '予約データがありません。'];
        }

        try {
            DB::transaction(function () use ($cd) {
                Reservation::withDeleted()->whereKey((int) $cd)->lockForUpdate()->first();
                Reservation::withDeleted()->whereKey((int) $cd)->toBase()
                    ->update(['disp_flg' => Reservation::DELETED_FLAG, 'update_date' => (string) WebReservationService::now()]);
            });
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'errorMsg' => '取消処理に失敗しました。'];
        }

        $this->mailer->actionMails((int) $cd, 'delete');
        $this->log((int) $cd, '新Web予約から予約情報を削除しました。', ['cd' => (int) $cd], __LINE__);

        return ['status' => 'ok'];
    }

    /**
     * Textos de error de los campos (unidos con '<br />', como el legacy). `isset($_REQUEST[x])` del legacy es
     * array_key_exists: Laravel convierte los '' en null (ConvertEmptyStringsToNull).
     */
    private function inputErrors(array $in): string
    {
        $koumoku = $this->flow->koumoku();
        $value = fn (string $key) => is_scalar($in[$key] ?? null) ? (string) $in[$key] : '';
        $required = fn (string $field) => (string) ($koumoku[$field]['koumoku_check'] ?? '') === '1';

        $error = '';
        if ($value('name') === '') {
            $error .= '※お名前が入力されていません。<br />';
        }
        if (array_key_exists('name_kana', $in) && $required('name_kana') && $value('name_kana') === '') {
            $error .= '※お名前（カナ）が入力されていません。<br />';
        }
        if (array_key_exists('mail', $in) && $required('mail') && $value('mail') === '') {
            $error .= '※メールアドレスが入力されていません。<br />';
        } elseif ($value('mail') !== '' && ! preg_match(self::MAIL_PATTERN, $value('mail'))) {
            $error .= '※メールアドレスが不適切な形式です。<br />';
        }
        if (array_key_exists('tel', $in) && $required('tel') && $value('tel') === '') {
            $error .= '※電話番号が入力されていません。<br />';
        }
        $free = is_array($in['free_koumoku'] ?? null) ? $in['free_koumoku'] : [];
        foreach ($this->flow->freeFields() as $n => $field) {
            if ((string) $field['koumoku_check'] === '1' && empty($free[$n])) {
                $error .= '※'.$field['tb_koumoku_name'].'が入力されていません。<br />';
            }
        }

        return $error;
    }

    /** Socio de la reserva: el de la sesión (customer_cd) o el del token de マイコレ; solo 本登録 vigentes */
    private function customer(array $in, mixed $sessionCustomerCd): ?object
    {
        $base = fn () => DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2');

        $customerCd = is_scalar($in['customer_cd'] ?? null) ? (string) $in['customer_cd'] : '';
        if ($customerCd !== '' && ctype_digit($customerCd) && $customerCd === (string) $sessionCustomerCd) {
            if ($row = $base()->where('cd', (int) $customerCd)->first()) {
                return $row;
            }
        }

        $token = is_scalar($in['token'] ?? null) ? (string) $in['token'] : '';
        if ($token !== '') {
            return $base()->whereRaw("md5(cd || ':' || touroku_date) = ?", [$token])->first();
        }

        return null;
    }

    /** Fila de `log` como Log::addLog() del legacy (type1 'V2予約') */
    private function log(int $cd, string $message, array $data, int $line): void
    {
        DB::table('log')->insert([
            'cd' => $this->hatuban->next('log'),
            'record_date' => Carbon::now('Asia/Tokyo')->getTimestamp(),
            'type1' => 'V2予約',
            'type2' => 'tb_yoyaku',
            'type3' => $cd,
            'log_message' => $message,
            'log_data' => json_encode($data + ['mobile' => session('mobile')], JSON_UNESCAPED_UNICODE),
            'referer' => (string) Request::header('referer'),
            'script_name' => static::class,
            'execution_row' => $line,
            'variable' => json_encode(Request::except(['sess']), JSON_UNESCAPED_UNICODE),
        ]);
    }
}
