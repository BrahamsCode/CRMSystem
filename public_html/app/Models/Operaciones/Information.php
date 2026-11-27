<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * インフォメーション (`tb_information`): aviso para el personal.
 * `shop_cd` NULL = 全店舗; `officialposition` = cargos separados por espacio; solo hay columna `update`.
 */
class Information extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_information';

    protected ?string $legacyCreatedColumn = null;

    protected ?string $legacyUpdatedColumn = 'update';

    protected $casts = [
        'cd' => 'integer',
        'shop_cd' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_cd', 'cd');
    }

    /** Cargos destinatarios (vacío = todos) */
    public function officialPositions(): array
    {
        return array_values(array_filter(preg_split('/[ 　]+/u', (string) $this->officialposition)));
    }

    /** Los de una tienda más los de todas las tiendas (information.php) */
    public function scopeVisibleForShop(Builder $query, ?int $shopCd): void
    {
        $query->where(function (Builder $q) use ($shopCd) {
            $q->whereNull('shop_cd');
            if ($shopCd !== null) {
                $q->orWhere('shop_cd', $shopCd);
            }
        });
    }

    /** Orden del legacy: los más recientes primero */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('update');
    }
}
