<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cupón y aviso al llegar a N sellos (クーポンお知らせ機能).
 * Tabla legacy `tb_stamp_oshirase_coupon` (dueño: S3 Promoción).
 */
class StampOshiraseCoupon extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_stamp_oshirase_coupon';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'tb_shop_cd' => 'integer',
        'stamp_count' => 'integer',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(MycouponMaster::class, 'tb_mycoupon_master_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
