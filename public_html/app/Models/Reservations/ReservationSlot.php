<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Turno de atención de un empleado en un día (受付日, `tb_yoyaku_uketsuke_day`).
 * `moushikomi_date` 'Y/m/d'; `start_time`/`end_time` (y descanso) en segundos desde las 00:00.
 * La tienda sale de `tb_staff.shop_cd`.
 */
class ReservationSlot extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    /** `status_flg` del legacy */
    public const STATUS_OPEN = '';           // 受付中 ('' o '0')

    public const STATUS_PHONE_ONLY = '1';    // 電話受付

    public const STATUS_CLOSED = '2';        // 受付終了

    public const STATUS_CLOSING_SOON = '3';  // 締切間近

    protected $table = 'tb_yoyaku_uketsuke_day';

    protected $casts = [
        'cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'people' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tb_staff_cd', 'cd');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'tb_yoyaku_uketsuke_day_cd', 'cd');
    }

    /** 00:00 del día de la franja (Asia/Tokyo); null si `moushikomi_date` no es una fecha */
    public function dayStart(): ?Carbon
    {
        $parts = explode('/', (string) $this->moushikomi_date);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo');
    }

    public function startsAt(): ?Carbon
    {
        return $this->dayStart()?->addSeconds((int) $this->start_time);
    }

    /** Puede caer al día siguiente (`end_time` > 86400) */
    public function endsAt(): ?Carbon
    {
        return $this->dayStart()?->addSeconds((int) $this->end_time);
    }
}
