<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\Customers\Customer;
use App\Models\LegacyModel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reserva (予約, `tb_yoyaku`): category9/yoyaku, `r/`, `reserve/` y クイック予約.
 *
 * La fecha, el empleado y la tienda están en la franja (`tb_yoyaku_uketsuke_day`):
 * `start_time`/`end_time` son segundos desde las 00:00 de `moushikomi_date` (pueden pasar de 86400).
 * `tb_customer_cd` null/0 = no socio (datos en `name`, `tel`, `mail`). `disp_flg = '9'` = cancelada.
 *
 * Contrato publicado para S3 (recordatorio, follow): scope remindable(), scope upcomingCustomerCds(),
 * reservedAt(), endsAt(), displayName(), contactMail(). Ver Doc/diseno/clientes-ventas-reservas.md §2.3.
 */
class Reservation extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    /** `entry_type` del legacy */
    public const ENTRY_ADMIN = '';

    public const ENTRY_PUBLIC_WEB = '100';       // r/ y reserve/

    public const ENTRY_PUBLIC_WEB_FREE = '101';  // r/ con フリー予約

    public const ENTRY_QUICK = '201';            // クイック予約 (appview)

    protected $table = 'tb_yoyaku';

    protected $casts = [
        'cd' => 'integer',
        'tb_yoyaku_uketsuke_day_cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'people' => 'integer',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ReservationSlot::class, 'tb_yoyaku_uketsuke_day_cd', 'cd');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }

    public function isMember(): bool
    {
        return (int) $this->tb_customer_cd > 0;
    }

    /**
     * Reservas a las que se envía el recordatorio de un día, igual que
     * `category3/reminder/reminder_new_confirm.php`: `disp_flg = '1'`, franja de ese día
     * (`moushikomi_date = 'YYYY/MM/DD'`), opcionalmente de los empleados de una tienda,
     * y sin el aviso desactivado en el socio. Incluye a los no socios (se avisa al `mail` de la reserva).
     */
    public function scopeRemindable(Builder $query, CarbonInterface $day, ?int $shopCd = null): void
    {
        $query->where('tb_yoyaku.disp_flg', '1')
            ->whereIn('tb_yoyaku.tb_yoyaku_uketsuke_day_cd', function ($sub) use ($day, $shopCd) {
                $sub->select('tb1.cd')
                    ->from('tb_yoyaku_uketsuke_day as tb1')
                    ->join('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
                    ->where('tb1.moushikomi_date', $day->copy()->setTimezone('Asia/Tokyo')->format('Y/m/d'));
                if ($shopCd !== null) {
                    $sub->where('tb2.shop_cd', $shopCd);
                }
            })
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('tb_customer as s2')
                    ->whereColumn('s2.cd', 'tb_yoyaku.tb_customer_cd')
                    ->whereRaw("COALESCE(s2.stop_yoyaku_reminder_mail_flg, '') <> ''");
            });
    }

    /**
     * `cd` de los socios con alguna reserva desde hoy, como el filtro de
     * `category3/follow/follow_new_confirm.php`. Devuelve un subquery para `whereIn`/`whereNotIn`.
     */
    public static function upcomingCustomerCds(?CarbonInterface $today = null): \Illuminate\Database\Query\Builder
    {
        $date = ($today ?? now('Asia/Tokyo'))->copy()->setTimezone('Asia/Tokyo')->toDateString();

        return \Illuminate\Support\Facades\DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->whereRaw('COALESCE(tb1.tb_customer_cd, 0) > 0')
            ->whereRaw('tb2.moushikomi_date::date >= ?::date', [$date])
            ->distinct()
            ->select('tb1.tb_customer_cd');
    }

    /** Inicio de la reserva (fecha de la franja + `start_time`), Asia/Tokyo */
    public function reservedAt(): ?Carbon
    {
        return $this->slot?->dayStart()?->addSeconds((int) $this->start_time);
    }

    public function endsAt(): ?Carbon
    {
        return $this->slot?->dayStart()?->addSeconds((int) $this->end_time);
    }

    /** Nombre como en el recordatorio: el del socio si lo es, si no el de la reserva */
    public function displayName(): string
    {
        return (string) ($this->isMember() && $this->customer ? $this->customer->name : $this->name);
    }

    /** Correo como en el recordatorio: `email1` del socio si lo es, si no `mail` de la reserva */
    public function contactMail(): ?string
    {
        $mail = $this->isMember() && $this->customer ? $this->customer->email1 : $this->mail;

        return filled($mail) ? $mail : null;
    }

    /** Códigos de menú de opción (`option_tb_yoyaku_koumoku_master_cd`, lista separada por comas) */
    public function optionMenuCds(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $this->option_tb_yoyaku_koumoku_master_cd))));
    }

    /** Campos libres 1..5 (`free_koumoku`: valores con rawurlencode unidos por '<>') */
    public function freeFields(): array
    {
        return array_map('rawurldecode', explode('<>', (string) $this->free_koumoku));
    }
}
