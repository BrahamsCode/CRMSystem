<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * 予約カテゴリ de la reserva simple (category3/reservation), por tienda.
 * Tabla del legacy `tb_reserve_name` (CONVENCIONES §0).
 */
class SimpleReservationCategory extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_reserve_name';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
    ];
}
