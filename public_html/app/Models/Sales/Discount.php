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
 * Descuento de ventas internas (割引管理). `type`: 1 %, 2 円; `buai_flg`: 1 cuenta para la comisión, 2 no.
 * Tabla del legacy `tb_karute_waribiki` (CONVENCIONES §0).
 */
class Discount extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_karute_waribiki';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public const TYPE_PERCENT = '1';

    public const TYPE_YEN = '2';

    public const BUAI_INCLUDED = '1';   // 割引額を歩合に含む

    public const BUAI_EXCLUDED = '2';   // 割引額を歩合に含まない

    /** Unidad del 割引額 (ajax_karute_waribiki_list.php $type_display) */
    public const TYPE_LABELS = ['1' => '％', '2' => '円'];

    /** 歩合適用 ($buai_flg_display) */
    public const BUAI_LABELS = ['1' => '割引額を歩合に含む', '2' => '割引額を歩合に含まない'];

    /** Descuentos de una tienda */
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

    public function buaiLabel(): string
    {
        return self::BUAI_LABELS[(string) $this->buai_flg] ?? '';
    }
}
