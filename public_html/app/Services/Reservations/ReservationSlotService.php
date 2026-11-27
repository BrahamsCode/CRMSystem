<?php

namespace App\Services\Reservations;

use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationSlot;
use App\Services\Nucleo\Hatuban;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * 受付日設定: franja de atención de un empleado en un día (`tb_yoyaku_uketsuke_day`).
 * Legacy: category9/yoyaku/yoyaku_uketsuke_register.php, yoyaku_uketsuke_delete.php,
 * yoyaku_multi_uketsuke_register.php y ajax_uketsuke_status.php.
 *
 * - `start_time`/`end_time`: segundos desde las 00:00 (las horas 24+ pasan de 86400).
 * - `start_break_time`/`end_break_time`: 'HH:MM' unidos por '<>' (texto, como los guarda el legacy).
 * - Cada operación deja su fila en la tabla `log` como la clase Log del legacy.
 */
class ReservationSlotService
{
    /** Separador de los descansos del legacy */
    public const BREAK_SEPARATOR = '<>';

    public function __construct(private readonly Hatuban $hatuban)
    {
    }

    /** Configuración de reservas de la tienda (horario 'HH:MM' de `tb_yoyaku_config`) */
    public function shopConfig(?int $shopCd): ?ReservationConfig
    {
        return $shopCd === null ? null : ReservationConfig::query()->where('tb_shop_cd', $shopCd)->first();
    }

    /** 'Y/m/d' a partir del `yoyaku_date` del legacy ('Ymd' o 'YmdHi') */
    public static function dateFromYmd(?string $ymd): string
    {
        $ymd = (string) $ymd;

        return substr($ymd, 0, 4) . '/' . substr($ymd, 4, 2) . '/' . substr($ymd, 6, 2);
    }

    /** Hora y minutos del formulario → segundos desde las 00:00 (hora 24+ = día siguiente, como el legacy) */
    public static function toSeconds(int|string $hour, int|string $min): int
    {
        return (int) $hour * 3600 + (int) $min * 60;
    }

    /** Segundos → ['HH', 'MM'] para los select (la hora sigue pasando de 23 si la franja termina después de medianoche) */
    public static function toHourMin(?int $seconds): ?array
    {
        if ($seconds === null) {
            return null;
        }

        return [sprintf('%02d', intdiv($seconds, 3600)), sprintf('%02d', intdiv($seconds % 3600, 60))];
    }

    /** Hora de una reserva como `date('H:i', $t - 9h)` del legacy (vuelve a 00:00 después de medianoche) */
    public static function clock(?int $seconds): string
    {
        $seconds = ((int) $seconds % 86400 + 86400) % 86400;

        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    /**
     * Descansos enviados (startBreakTime[] / endBreakTime[] 'HH:MM') → [inicio, fin] unidos por '<>'.
     * Las filas vacías se saltan; sin descansos quedan en ''.
     *
     * @return array{0: string, 1: string}
     */
    public static function breakTimes(array $starts, array $ends): array
    {
        $startList = $endList = [];
        foreach ($starts as $key => $start) {
            $end = $ends[$key] ?? '';
            if ((string) $start === '' && (string) $end === '') {
                continue;
            }
            $startList[] = (string) $start;
            $endList[] = (string) $end;
        }

        return [implode(self::BREAK_SEPARATOR, $startList), implode(self::BREAK_SEPARATOR, $endList)];
    }

    /**
     * Alta o edición de la franja de un día (yoyaku_uketsuke_register.php).
     * En el alta, si el empleado ya tiene franja ese día se actualiza esa (como el legacy desde 2018).
     *
     * @param  array{start_time: int, end_time: int, people: int, start_break_time: string, end_break_time: string}  $times
     */
    public function save(?int $cd, int $staffCd, ?string $moushikomiDate, array $times): ReservationSlot
    {
        return DB::transaction(function () use ($cd, $staffCd, $moushikomiDate, $times) {
            $slot = null;
            if ($cd !== null) {
                $slot = ReservationSlot::withDeleted()->findOrFail($cd);
            } else {
                $slot = ReservationSlot::withDeleted()
                    ->where('tb_staff_cd', $staffCd)
                    ->where('moushikomi_date', $moushikomiDate)
                    ->first();
            }

            if ($slot === null) {
                $slot = new ReservationSlot(['tb_staff_cd' => $staffCd, 'moushikomi_date' => $moushikomiDate]);
            }

            $slot->fill($times + ['hyouji_flg' => '1', 'disp_flg' => ReservationSlot::ACTIVE_FLAG])->save();

            $this->log($slot->cd, '管理画面から受付日設定を登録しました。', ['slot' => $slot->getAttributes()], __LINE__);

            return $slot;
        });
    }

    /**
     * Alta de varios días a la vez (yoyaku_multi_uketsuke_register.php): crea los días que no tienen
     * franja y sobrescribe los que ya la tienen (sin tocar su `touroku_date`).
     *
     * @param  list<string>  $days  'Y/m/d'
     * @return list<int> cd de las franjas guardadas
     */
    public function bulkSave(int $staffCd, array $days, array $times): array
    {
        return DB::transaction(function () use ($staffCd, $days, $times) {
            $existing = ReservationSlot::withDeleted()
                ->where('tb_staff_cd', $staffCd)
                ->whereIn('moushikomi_date', $days)
                ->get()
                ->keyBy('moushikomi_date');

            $saved = [];
            foreach ($days as $day) {
                $slot = $existing->get($day) ?? new ReservationSlot(['tb_staff_cd' => $staffCd, 'moushikomi_date' => $day]);
                $slot->fill($times + ['hyouji_flg' => '1', 'disp_flg' => ReservationSlot::ACTIVE_FLAG])->save();
                $existing->put($day, $slot);
                $saved[] = $slot->cd;

                $this->log($slot->cd, '管理画面から選択した日付で一括受付日設定を登録しました。', ['slot' => $slot->getAttributes()], __LINE__);
            }

            return $saved;
        });
    }

    /** ¿La franja tiene reservas vigentes (`tb_yoyaku.disp_flg = '1'`)? */
    public function hasReservations(ReservationSlot $slot): bool
    {
        return Reservation::query()
            ->where('tb_yoyaku_uketsuke_day_cd', $slot->cd)
            ->where('disp_flg', '1')
            ->exists();
    }

    /**
     * Borra la franja (yoyaku_uketsuke_delete.php). El legacy hace un DELETE físico (no `disp_flg = '9'`):
     * se reproduce con forceDelete() para que ninguna consulta sin el scope vea la franja borrada.
     * La pantalla solo ofrece el borrado si no hay reservas; aquí además se rechaza si las hay.
     */
    public function destroy(ReservationSlot $slot): bool
    {
        if ($this->hasReservations($slot)) {
            return false;
        }

        $cd = $slot->cd;
        $slot->forceDelete();
        $this->log($cd, '管理画面から予約受付を削除しました。', ['deleted' => $cd], __LINE__);

        return true;
    }

    /**
     * ajax_uketsuke_status.php: cambia `status_flg` de las franjas del empleado ese día
     * ('' / '0' 受付中, '1' 電話受付, '2' 受付終了, '3' 締切間近). No toca `update_date`, como el legacy.
     *
     * @param  string  $td  'Ymd'
     */
    public function changeStatus(int $staffCd, string $td, string $statusFlg): int
    {
        $date = self::dateFromYmd($td);

        // El legacy registra el log antes del UPDATE, con type3 = 0
        $this->log(0, '管理画面から状況を変更しました。', ['tb_staff_cd' => $staffCd, 'moushikomi_date' => $date, 'status_flg' => $statusFlg], __LINE__);

        return ReservationSlot::withDeleted()
            ->where('tb_staff_cd', $staffCd)
            ->where('moushikomi_date', $date)
            ->toBase()
            ->update(['status_flg' => $statusFlg]);
    }

    /**
     * 予約者一覧 de la franja (reservas vigentes por hora de inicio), paginado como page_control.php.
     * Cada fila trae `customer_name` (tb_customer.name del socio o el `name` de la reserva).
     */
    public function reservationList(ReservationSlot $slot, int $kensu, int $page): LengthAwarePaginator
    {
        $query = Reservation::query()
            ->where('tb_yoyaku_uketsuke_day_cd', $slot->cd)
            ->where('disp_flg', '1')
            ->orderBy('start_time');

        $total = (clone $query)->count();
        $maxPage = max(1, (int) ceil($total / max(1, $kensu)));
        $page = min(max(1, $page), $maxPage); // ページオーバーを防ぐ

        $rows = $query->forPage($page, $kensu)->get();

        // El legacy busca el nombre sin mirar disp_flg del socio
        $names = DB::table('tb_customer')
            ->whereIn('cd', $rows->pluck('tb_customer_cd')->filter()->unique()->values())
            ->pluck('name', 'cd');
        $rows->each(function (Reservation $row) use ($names) {
            $row->setAttribute('customer_name', $row->tb_customer_cd ? ($names[$row->tb_customer_cd] ?? null) : $row->name);
        });

        return new LengthAwarePaginator($rows, $total, $kensu, $page);
    }

    /**
     * manageWord() del legacy: el texto de `tb_manage_words` o el de por defecto.
     * TODO(S1): modelo ManageWord (tb_manage_words)
     */
    public function manageWord(string $default, string $contentName): string
    {
        $naiyou = DB::table('tb_manage_words')->where('content_name', $contentName)->value('naiyou');

        return filled($naiyou) ? (string) $naiyou : $default;
    }

    /** Comprobaciones de ajax_uketsuke_status.php: el texto con el que el legacy cortaba (die), o null */
    public static function statusRequestError(mixed $staffCd, mixed $td, mixed $sess, string $sessionId): ?string
    {
        return match (true) {
            ! is_string($staffCd) || ! ctype_digit($staffCd) => '不正アクセス(staffCd)',
            ! is_string($td) || ! ctype_digit($td) => '不正アクセス(td)',
            (string) $sess !== $sessionId => '不正アクセス！！',
            default => null,
        };
    }

    /** Día de la semana en japonés de una fecha 'Ymd' (sin mktime); null si no es una fecha válida */
    public static function weekday(string $ymd): ?string
    {
        $y = (int) substr($ymd, 0, 4);
        $m = (int) substr($ymd, 4, 2);
        $d = (int) substr($ymd, 6, 2);
        if (! checkdate($m, $d, $y)) {
            return null;
        }

        return ['日', '月', '火', '水', '木', '金', '土'][Carbon::create($y, $m, $d, 0, 0, 0, 'Asia/Tokyo')->dayOfWeek];
    }

    /**
     * Fila de la tabla `log` como Log::addLog() del legacy.
     * TODO(S1): modelo Log (`log`) y su purga mensual (el legacy borraba en cada alta los de más de un mes).
     */
    private function log(int $type3, string $message, array $data, int $line): void
    {
        DB::table('log')->insert([
            'cd' => $this->hatuban->next('log'),
            'record_date' => Carbon::now('Asia/Tokyo')->getTimestamp(),
            'type1' => '管理画面',
            'type2' => 'tb_yoyaku_uketsuke_day',
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
