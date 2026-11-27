<?php

namespace App\Models\Promotions;

use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cupón usado: copia de la fila de tb_mycoupon con la tienda (tb_shop_cd) y la hora (used_date) del uso.
 * Tabla legacy `tb_mycoupon_used` (dueño: S3 Promoción).
 */
class MycouponUsed extends LegacyModel
{
    protected $table = 'tb_mycoupon_used';

    protected $casts = [
        'cd' => 'integer',
        'tb_mycoupon_master_cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_customer_cd' => 'integer',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MycouponMaster::class, 'tb_mycoupon_master_cd', 'cd');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'tb_customer_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
