<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intervalo del lector de tarjetas por tienda (カードリーダ設定).
 * Tabla legacy `tb_touch_mode` (dueño: S3 Promoción).
 */
class TouchMode extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_touch_mode';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
    ];

    protected ?string $legacyCreatedColumn = null;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
