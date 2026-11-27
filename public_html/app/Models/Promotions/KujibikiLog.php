<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tirada del sorteo (kujibiki_name: ダイヤ…ハズレ) con plazo de canje (time_limit).
 * Tabla legacy `tb_kujibiki_log` (dueño: S3 Promoción).
 */
class KujibikiLog extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_kujibiki_log';

    protected $casts = [
        'cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_mycoupon_master_cd' => 'integer',
        'time_limit' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'tb_customer_cd', 'cd');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(MycouponMaster::class, 'tb_mycoupon_master_cd', 'cd');
    }
}
