<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Equipo o sala (設備管理). `quantity` = unidades.
 * Tabla del legacy `tb_setsubi` (CONVENCIONES §0).
 */
class Facility extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_setsubi';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'quantity' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
