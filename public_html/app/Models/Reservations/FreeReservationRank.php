<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orden del personal en フリー予約.
 * Tabla del legacy `tb_free_yoyaku_rank` (CONVENCIONES §0).
 */
class FreeReservationRank extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_free_yoyaku_rank';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'tb_staff_cd', 'cd');
    }
}
