<?php

namespace App\Models\Sales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public const TYPE_SERVICE = '1';  // 技術

    public const TYPE_RETAIL = '2';   // 物販

    public const TYPE_OTHER = '3';    // その他

    /** 区分 del listado (ajax_karute_menu_list.php $kubun_display) */
    public const TYPE_LABELS = ['1' => '技術', '2' => '物販', '3' => 'その他'];

    /** Menús de una tienda */
    public function scopeOfShop(Builder $query, int $shopCd): void
    {
        $query->where('tb_shop_cd', $shopCd);
    }

    /** Orden del legacy: rank, cd */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('rank')->orderBy('cd');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[(string) $this->type] ?? '';
    }
}
