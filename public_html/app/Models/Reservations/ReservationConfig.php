<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de reservas por tienda (予約設定 各店舗設定). `start_time`/`end_time` en 'HH:MM'.
 * Tabla del legacy `tb_yoyaku_config` (CONVENCIONES §0).
 */
class ReservationConfig extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_yoyaku_config';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'setsubi_capacity' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
