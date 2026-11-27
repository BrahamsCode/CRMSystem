<?php

namespace App\Models\Sales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Descuento de ventas internas (割引管理). `type`: 1 %, 2 円; `buai_flg`: 1 cuenta para la comisión, 2 no.
 * Tabla del legacy `tb_karute_waribiki` (CONVENCIONES §0).
 */
class Discount extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_karute_waribiki';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }

    public const TYPE_PERCENT = '1';

    public const TYPE_YEN = '2';
}
