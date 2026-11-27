<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cupón entregado a un cliente y todavía sin usar. print_flg = cd de la tienda donde está pendiente de imprimir.
 * Tabla legacy `tb_mycoupon` (dueño: S3 Promoción).
 */
class Mycoupon extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_mycoupon';

    protected $casts = [
        'cd' => 'integer',
        'tb_mycoupon_master_cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_customer_cd' => 'integer',
    ];

    protected ?string $legacyUpdatedColumn = null;

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
