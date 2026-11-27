<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intentos del sorteo del cliente (int_data), contados desde update_date.
 * Tabla legacy `tb_kujibiki_count` (dueño: S3 Promoción).
 */
class KujibikiCount extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_kujibiki_count';

    protected $casts = [
        'cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'int_data' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'tb_customer_cd', 'cd');
    }
}
