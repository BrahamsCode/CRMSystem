<?php

namespace App\Models\Sales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Menú de ventas internas (メニュー管理). `type`: 1 技術, 2 物販, 3 その他; `kingaku` = precio.
 * Tabla del legacy `tb_karute_menu` (CONVENCIONES §0).
 */
class ServiceMenu extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_karute_menu';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }

    public const TYPE_SERVICE = '1';  // 技術

    public const TYPE_RETAIL = '2';   // 物販

    public const TYPE_OTHER = '3';    // その他
}
