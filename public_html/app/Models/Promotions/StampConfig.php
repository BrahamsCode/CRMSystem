<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de la tarjeta de sellos por tienda (スタンプ仕様管理).
 * Tabla legacy `tb_stamp_config` (dueño: S3 Promoción).
 */
class StampConfig extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_stamp_config';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'stamp_max' => 'integer',
        'touroku_bonus' => 'integer',
    ];

    protected ?string $legacyCreatedColumn = null;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
