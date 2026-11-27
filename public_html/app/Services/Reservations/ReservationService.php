<?php

namespace App\Services\Reservations;

use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationSlot;
use App\Services\Nucleo\Hatuban;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Altas, cambios y movimientos de reservas de la administración (`tb_yoyaku`):
 * - create(): yoyaku_register.php (act=new), con numeración hatuban y bloqueo del aforo de la franja.
 * - update(): ajax_yoyaku_register.php (popup del 予約表); el cambio de fecha y de empleado llama directamente
 *   a move() en lugar de pedir ajax_move_objs.php por HTTP.
 * - move(): ajax_move_objs.php (cambiar fecha/hora o pasar a otro empleado una reserva, las del día o las de
 *   ese día en adelante); crea la franja de destino con el horario de la tienda si no existe.
 * - cancel(): act=del de ajax_yoyaku_register.php (`disp_flg = '9'`).
 * - relateCustomer(): ajax_related_customer.php.
 *
 * Los métodos que responden al JS del 予約表 devuelven '' si fue bien o el texto de error del legacy.
 */
class ReservationService
{
    public function __construct(
        private readonly ReservationFlowService $flow,
        private readonly ReservationMailer $mailer,
        private readonly Hatuban $hatuban,
    ) {}

    /**
     * Alta de yoyaku_register.php. $input ya limpio con form2Db: kaiin | name, name_kana, tel, mail;
     * free_koumoku[N], people, mark, yoyaku_date ('YmdHi'), plus ('1' = después de medianoche), staff,
     * menu[cd], option_menu[cd], comment. La duración sale de los menús del empleado (el legacy la recibía
     * en el campo oculto shoyou_time con el mismo cálculo).
     *
     * @throws ReservationCapacityException si la franja no admite más personas en ese horario
     */
    public function create(array $input): Reservation
    {
        $ymd = (string) ($input['yoyaku_date'] ?? '');
        $date = ReservationSlotService::dateFromYmd($ymd);
        $staffCd = (int) ($input['staff'] ?? 0);

        $start = (int) substr($ymd, 8, 2) * 3600 + (int) substr($ymd, 10, 2) * 60;
        if (($input['plus'] ?? null) == '1') {
            $start += 86400; // 24時以降
        }
        $menuCds = ReservationFlowService::menuCds($input['menu'] ?? null, $input['option_menu'] ?? null);
        $end = $start + $this->flow->totalMinutes($staffCd, $menuCds) * 60;
        $people = ! empty($input['people']) ? max((int) $input['people'], 1) : 1;

        return DB::transaction(function () use ($input, $date, $staffCd, $start, $end, $people) {
            // Bloqueo de la franja: dos altas a la vez no pueden pasar del aforo (`people`)
            $slot = ReservationSlot::withDeleted()
                ->where('tb_staff_cd', $staffCd)
                ->where('moushikomi_date', $date)
                ->lockForUpdate()
                ->first();
            if ($slot === null) {
                throw new ReservationSlotMissingException('受付日が設定されていません。');
            }
            if (! $this->fits($slot, $start, $end, $people)) {
                throw new ReservationCapacityException('最大受付人数を超えるため予約できません。');
            }

            $isMember = (string) ($input['kaiin'] ?? '') !== '';
            $reservation = new Reservation([
                'tb_yoyaku_uketsuke_day_cd' => $slot->cd,
                'tb_customer_cd' => $isMember ? (int) $input['kaiin'] : null,
                'name' => $isMember ? null : (string) ($input['name'] ?? ''),
                'name_kana' => $isMember ? null : (string) ($input['name_kana'] ?? ''),
                'tel' => $isMember ? null : (string) ($input['tel'] ?? ''),
                'mail' => $isMember ? null : (string) ($input['mail'] ?? ''),
                'free_koumoku' => $this->flow->encodeFreeFields((array) ($input['free_koumoku'] ?? [])),
                'people' => $people,
                'mark' => (string) ($input['mark'] ?? ''),
                'start_time' => $start,
                'end_time' => $end,
                'base_tb_yoyaku_koumoku_master_cd' => implode(',', ReservationFlowService::menuCds($input['menu'] ?? null)),
                'option_tb_yoyaku_koumoku_master_cd' => implode(',', ReservationFlowService::menuCds($input['option_menu'] ?? null)),
                'comment' => (string) ($input['comment'] ?? ''),
                'hyouji_flg' => '1',
                'disp_flg' => '1',
            ]);
            if ($this->realtimePrintEnabled()) {
                $reservation->print_flg = '1'; // リアルタイム印刷
            }
            $reservation->save();

            $this->log($reservation->cd, '管理画面から予約情報を新規登録しました。', ['reservation' => $reservation->getAttributes()], __LINE__);

            return $reservation;
        });
    }

    /** Envía los correos de yoyaku_register.php (tras el COMMIT) */
    public function notifyRegistered(Reservation $reservation, array $input): void
    {
        $this->mailer->registerMails($reservation, $input);
    }

    /**
     * ¿Caben $people personas más en la franja entre $start y $end? (suma de `people` de las reservas vigentes
     * en curso en cada momento frente a `tb_yoyaku_uketsuke_day.people`, como el ○/× de 時間選択)
     */
    public function fits(ReservationSlot $slot, int $start, int $end, int $people, ?int $exceptCd = null): bool
    {
        $others = Reservation::query()
            ->where('tb_yoyaku_uketsuke_day_cd', $slot->cd)
            ->where('disp_flg', '1')
            ->when($exceptCd !== null, fn ($q) => $q->where('cd', '!=', $exceptCd))
            ->where('start_time', '<', max($end, $start + 1))
            ->where('end_time', '>', $start)
            ->get(['start_time', 'end_time', 'people']);

        // La ocupación máxima se da al empezar la nueva reserva o al empezar alguna de las existentes
        $points = $others->pluck('start_time')->filter(fn ($t) => $t > $start && $t < $end)->push($start)->unique();
        foreach ($points as $t) {
            $busy = $others->filter(fn ($r) => $r->start_time <= $t && $r->end_time > $t)->sum('people');
            if ($busy + $people > (int) $slot->people) {
                return false;
            }
        }

        return true;
    }

    /**
     * ajax_yoyaku_register.php (sin act=del): cambio desde el popup del 予約表.
     * $input limpio con form2Db: name, name_kana, tel, mail, startTime/endTime 'HH:MM', checked_recalculation,
     * bartime (minutos), people, mark, comment, base_menu/option_menu ('cd,cd'), free_koumoku[N],
     * tb_staff_cd (cambio de empleado), moushikomi_date (cambio de día), sendMailFlg.
     */
    public function update(int $cd, array $input): string
    {
        $current = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->where('tb1.cd', $cd)
            ->value('tb2.moushikomi_date');

        // Cambio de día (antes por HTTP a ajax_move_objs.php sin correo)
        $newDate = (string) ($input['moushikomi_date'] ?? '');
        $startTime = (string) ($input['startTime'] ?? '');
        if ($current != $newDate && $newDate !== ''
            && preg_match('/^[0-9]{4}(\/|\-)[0-9]{2}(\/|\-)[0-9]{2}$/', $newDate)
            && $startTime !== '' && preg_match('/^[0-9]{2}:[0-9]{2}$/', $startTime)) {
            if ($this->move($cd, ['reserveDate' => str_replace('-', '/', $newDate), 'startTime' => $startTime, 'sendMailFlg' => '']) !== '') {
                return 'ERROR:ajax_move_objs error code 2';
            }
        }

        // Cambio de empleado (una sola reserva)
        $staffCd = (string) ($input['tb_staff_cd'] ?? '');
        if ($staffCd !== '') {
            if (! ctype_digit($staffCd)) {
                return 'ERROR:staff_cd';
            }
            if ($this->move($cd, ['newStaff' => $staffCd, 'objs' => '1']) !== '') {
                return 'ERROR:ajax_move_objs error code 4';
            }
        }

        $start = self::clockToSeconds($startTime);
        $end = ($input['checked_recalculation'] ?? null) != '1'
            ? self::clockToSeconds((string) ($input['endTime'] ?? ''))
            : $start + (int) round((is_numeric($input['bartime'] ?? null) ? (float) $input['bartime'] : 0) * 60);

        $data = [];
        if ((string) ($input['name'] ?? '') !== '') {
            $data += ['name' => (string) $input['name'], 'name_kana' => (string) ($input['name_kana'] ?? ''), 'mail' => (string) ($input['mail'] ?? '')];
        }
        $data += [
            'tel' => (string) ($input['tel'] ?? ''),
            'free_koumoku' => $this->flow->encodeFreeFields((array) ($input['free_koumoku'] ?? [])),
            'start_time' => $start,
            'end_time' => $end,
            'people' => ! empty($input['people']) ? max((int) $input['people'], 1) : 1,
            'mark' => (string) ($input['mark'] ?? ''),
            'comment' => (string) ($input['comment'] ?? ''),
            'base_tb_yoyaku_koumoku_master_cd' => (string) ($input['base_menu'] ?? ''),
            'option_tb_yoyaku_koumoku_master_cd' => (string) ($input['option_menu'] ?? ''),
        ];

        DB::transaction(function () use ($cd, $data) {
            $this->lockSlotOf($cd);
            // Como el legacy: UPDATE directo, sin tocar update_date
            Reservation::withDeleted()->whereKey($cd)->toBase()->update($data);
            $this->log($cd, '管理画面から予約情報を更新しました。', $data, __LINE__);
        });

        if (($input['sendMailFlg'] ?? null) == '1') {
            $this->mailer->actionMails($cd, 'update');
        }

        return '';
    }

    /** act=del de ajax_yoyaku_register.php: `disp_flg = '9'` (el legacy también avisaba con sendMailFlg=1, como 'update') */
    public function cancel(int $cd, bool $sendMail = false): string
    {
        DB::transaction(function () use ($cd) {
            Reservation::withDeleted()->whereKey($cd)->toBase()->update(['disp_flg' => Reservation::DELETED_FLAG]);
            $this->log($cd, '管理画面から予約情報を削除しました。', ['cd' => $cd], __LINE__);
        });

        if ($sendMail) {
            $this->mailer->actionMails($cd, 'update');
        }

        return '';
    }

    /**
     * ajax_move_objs.php. Parámetros:
     * - reserveDate ('Y/m/d') + startTime ('HH:MM', 24+ = madrugada) [+ sendMailFlg]: cambia día y hora;
     * - newStaff + objs: 'all' (las de ese día en adelante del empleado), 'today' (las de ese día) u otro (solo esta).
     * Devuelve '' si fue bien (o si no había nada que hacer) o el texto de error del legacy.
     */
    public function move(int $cd, array $input): string
    {
        $result = DB::transaction(function () use ($cd, $input) {
            $reservation = Reservation::withDeleted()->with(['slot' => fn ($q) => $q->withDeleted()->with(['staff' => fn ($s) => $s->withDeleted()])])
                ->lockForUpdate()->find($cd);
            if ($reservation === null || $reservation->slot === null || $reservation->slot->staff === null) {
                return ''; // el legacy hacía die() sin texto
            }
            $slot = $reservation->slot;
            $staff = $slot->staff;

            // Sin 予約設定 de la tienda tampoco hacía nada (die())
            $config = ReservationConfig::query()->where('tb_shop_cd', $staff->shop_cd)->first();
            if ($config === null) {
                return '';
            }

            if (array_key_exists('reserveDate', $input) && array_key_exists('startTime', $input)) {
                return $this->moveDatetime($reservation, $slot, $config, (string) $input['reserveDate'], (string) $input['startTime'], (string) ($input['sendMailFlg'] ?? '') === '1');
            }

            $newStaff = (string) ($input['newStaff'] ?? '');
            if (! ctype_digit($newStaff)) {
                return 'ERROR:staff_cd';
            }
            $newStaff = (int) $newStaff;

            switch ((string) ($input['objs'] ?? '')) {
                case 'all':
                    // Las franjas del empleado de ese día en adelante
                    $slots = ReservationSlot::withDeleted()->where('tb_staff_cd', $staff->cd)->where('moushikomi_date', '>=', $slot->moushikomi_date)->get();
                    foreach ($slots as $from) {
                        $to = $this->slotFor((string) $from->moushikomi_date, $newStaff, $config);
                        Reservation::withDeleted()->where('tb_yoyaku_uketsuke_day_cd', $from->cd)->toBase()->update(['tb_yoyaku_uketsuke_day_cd' => $to->cd]);
                        $this->log($from->cd, '管理画面からこの日以降全件を一括で移動しました。', ['from' => $from->cd, 'to' => $to->cd], __LINE__);
                    }
                    break;

                case 'today':
                    $to = $this->slotFor((string) $slot->moushikomi_date, $newStaff, $config);
                    Reservation::withDeleted()->where('tb_yoyaku_uketsuke_day_cd', $slot->cd)->toBase()->update(['tb_yoyaku_uketsuke_day_cd' => $to->cd]);
                    $this->log($slot->cd, '管理画面からこの日の予約を一括で移動しました。', ['from' => $slot->cd, 'to' => $to->cd], __LINE__);
                    break;

                default:
                    $to = $this->slotFor((string) $slot->moushikomi_date, $newStaff, $config);
                    Reservation::withDeleted()->whereKey($cd)->toBase()->update(['tb_yoyaku_uketsuke_day_cd' => $to->cd]);
                    $this->log($cd, '管理画面からこの一件を移動しました。', ['from' => $slot->cd, 'to' => $to->cd], __LINE__);
            }

            return '';
        });

        if ($result === true) {
            // Cambio de día y hora hecho con aviso por correo
            $this->mailer->actionMails($cd, 'update');

            return '';
        }

        return $result;
    }

    /** ajax_related_customer.php: liga la reserva a un socio existente */
    public function relateCustomer(int $yoyakuCd, int $customerCd): void
    {
        DB::transaction(fn () => Reservation::withDeleted()->whereKey($yoyakuCd)->toBase()->update(['tb_customer_cd' => $customerCd]));
    }

    /** 'HH:MM' → segundos desde las 00:00 del día de la franja (horas 24+ = madrugada siguiente); '' = 0 */
    public static function clockToSeconds(string $clock): int
    {
        [$h, $m] = array_pad(explode(':', $clock), 2, 0);

        return (int) $h * 3600 + (int) $m * 60;
    }

    /**
     * Cambio de día y hora de ajax_move_objs.php. Devuelve '' (sin cambios), true (hecho, y hay que avisar si
     * sendMailFlg) o el texto de error.
     */
    private function moveDatetime(Reservation $reservation, ReservationSlot $slot, ReservationConfig $config, string $reserveDate, string $startTime, bool $sendMail): string|bool
    {
        [$yy, $mm, $dd] = array_pad(explode('/', $reserveDate), 3, '');
        if (! ctype_digit((string) $yy) || ! ctype_digit((string) $mm) || ! ctype_digit((string) $dd) || ! checkdate((int) $mm, (int) $dd, (int) $yy)) {
            return '正しい開始日時を選択してください。';
        }
        if (! preg_match('/^[0-9]{1,2}:[0-9]{2}$/', $startTime)) {
            return '正しい開始日時を選択してください。';
        }

        $date = sprintf('%04d/%02d/%02d', $yy, $mm, $dd);
        $start = self::clockToSeconds($startTime);
        $current = $slot->dayStart()?->addSeconds((int) $reservation->start_time);
        $new = Carbon::create((int) $yy, (int) $mm, (int) $dd, 0, 0, 0, 'Asia/Tokyo')->addSeconds($start);
        if ($current !== null && $current->equalTo($new)) {
            return ''; // 変更ない場合終了する
        }

        $to = $this->slotFor($date, (int) $slot->tb_staff_cd, $config);
        $data = [
            'tb_yoyaku_uketsuke_day_cd' => $to->cd,
            'start_time' => $start,
            'end_time' => $start + ((int) $reservation->end_time - (int) $reservation->start_time),
        ];
        Reservation::withDeleted()->whereKey($reservation->cd)->toBase()->update($data);
        $this->log($reservation->cd, '管理画面から予約日時を変更しました。', $data, __LINE__);

        return $sendMail ? true : '';
    }

    /**
     * getUketsukeCd() de ajax_move_objs.php: la franja del empleado ese día; si no existe se crea con el
     * horario de 予約設定 de la tienda y 1 persona.
     */
    private function slotFor(string $date, int $staffCd, ReservationConfig $config): ReservationSlot
    {
        $slot = ReservationSlot::withDeleted()->where('tb_staff_cd', $staffCd)->where('moushikomi_date', $date)->first();
        if ($slot !== null) {
            return $slot;
        }

        $slot = ReservationSlot::create([
            'tb_staff_cd' => $staffCd,
            'moushikomi_date' => $date,
            'start_time' => self::clockToSeconds((string) $config->start_time),
            'end_time' => self::clockToSeconds((string) $config->end_time),
            'people' => 1,
            'hyouji_flg' => '1',
            'disp_flg' => '1',
        ]);
        $this->log($slot->cd, '予約の移動に伴って予約日設定を新設しました。', ['slot' => $slot->getAttributes()], __LINE__, 'tb_yoyaku_uketsuke_day');

        return $slot;
    }

    /** Bloquea la franja de una reserva durante el cambio */
    private function lockSlotOf(int $cd): void
    {
        $slotCd = Reservation::withDeleted()->whereKey($cd)->value('tb_yoyaku_uketsuke_day_cd');
        if ($slotCd !== null) {
            ReservationSlot::withDeleted()->whereKey($slotCd)->lockForUpdate()->first();
        }
    }

    /** リアルタイム印刷 activa (tb_base_system_grandchild 9/105) */
    private function realtimePrintEnabled(): bool
    {
        return DB::table('tb_base_system_grandchild')
            ->where('tb_base_system_cd', 9)
            ->where('tb_base_system_child_cd', 105)
            ->where('system_name', 'リアルタイム印刷')
            ->where('system_flg', '1')
            ->exists();
    }

    /**
     * Fila de la tabla `log` como Log::addLog() del legacy.
     * TODO(S1): modelo Log (`log`).
     */
    private function log(int $type3, string $message, array $data, int $line, string $type2 = 'tb_yoyaku'): void
    {
        DB::table('log')->insert([
            'cd' => $this->hatuban->next('log'),
            'record_date' => Carbon::now('Asia/Tokyo')->getTimestamp(),
            'type1' => '管理画面',
            'type2' => $type2,
            'type3' => $type3,
            'log_message' => $message,
            'log_data' => json_encode($data + ['staff' => Auth::user()?->cd ?? null], JSON_UNESCAPED_UNICODE),
            'referer' => (string) Request::header('referer'),
            'script_name' => static::class,
            'execution_row' => $line,
            'variable' => json_encode(Request::except(['_token', 'sess']), JSON_UNESCAPED_UNICODE),
        ]);
    }
}
