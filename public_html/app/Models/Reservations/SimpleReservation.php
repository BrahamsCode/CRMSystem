<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\Customers\Customer;
use App\Models\LegacyModel;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reserva simple de category3/reservation (`tb_reserve`): sistema independiente de `tb_yoyaku`.
 * Todo es texto: `reserve_time` (epoch), `user_cd`/`shop_cd`/`reserve_nomination` (cd en texto).
 * `reserve_flg`: '' 受付, '2' 確定, '9' キャンセル; `raiten_flg = '1'` 来店.
 * Sin `disp_flg` ni `touroku_date`: solo `update` (epoch).
 */
class SimpleReservation extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    public const FLG_PENDING = '';

    public const FLG_CONFIRMED = '2';

    public const FLG_CANCELED = '9';

    protected $table = 'tb_reserve';

    protected ?string $legacyCreatedColumn = null;

    protected ?string $legacyUpdatedColumn = 'update';

    protected $casts = ['cd' => 'integer'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_cd', 'cd');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reserve_nomination', 'cd');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SimpleReservationCategory::class, 'reserve_cate', 'cd');
    }

    public function reservedAt(): ?Carbon
    {
        return is_numeric($this->reserve_time) ? Carbon::createFromTimestamp((int) $this->reserve_time, 'Asia/Tokyo') : null;
    }
}
