<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ficha de servicio del cliente (カルテ). `uketsuke_date`: epoch de la fecha del servicio.
 * Tabla del legacy `tb_karute` (CONVENCIONES §0).
 */
class Karte extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_karute';

    protected $casts = [
        'cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_sejutsu_cate_cd' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'tb_sejutsu_cate_cd', 'cd');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'tb_staff_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
