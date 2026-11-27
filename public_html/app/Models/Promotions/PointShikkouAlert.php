<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aviso múltiple antes de que venzan los puntos (失効前通知).
 * Tabla legacy `point_shikkou_alert` (dueño: S3 Promoción).
 */
class PointShikkouAlert extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'point_shikkou_alert';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'sort' => 'integer',
    ];

    protected ?string $legacyUpdatedColumn = null;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
