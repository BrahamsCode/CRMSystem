<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de sellos.
 * Tabla legacy `tb_stamp_log` (dueño: S3 Promoción).
 */
class StampLog extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_stamp_log';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_terminal_cd' => 'integer',
        'shori_mae_stamp' => 'integer',
        'kaikei' => 'integer',
        'percentage' => 'float',
        'jidou_stamp' => 'integer',
        'sounyu_stamp' => 'integer',
        'shouhi_stamp' => 'integer',
    ];

    protected ?string $legacyUpdatedColumn = null;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class, 'tb_customer_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
