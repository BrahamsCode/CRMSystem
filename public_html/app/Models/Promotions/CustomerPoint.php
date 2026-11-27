<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo de puntos del cliente (service_point) y su caducidad (point_yukoukigen, epoch).
 * Tabla legacy `tb_customer_point` (dueño: S3 Promoción).
 */
class CustomerPoint extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_customer_point';

    protected $casts = [
        'cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'service_point' => 'integer',
        'point_yukoukigen' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'tb_customer_cd', 'cd');
    }
}
