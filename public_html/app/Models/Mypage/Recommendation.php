<?php

namespace App\Models\Mypage;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * オススメ (`tb_hp_recommend`). La lee la app (appview/Util): últimos 20 de la
 * tienda del cliente o de 全店舗 (`tb_shop_cd` 0/null). `disp_date` es texto
 * `Y/m/d`; la fecha de modificación va en `update` (epoch), sin columna de alta.
 */
class Recommendation extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    public const VISIBLE = '1';    // hyouji_flg: 表示

    public const HIDDEN_APP = '9'; // hyouji_flg 9: la app la excluye

    protected $table = 'tb_hp_recommend';

    protected ?string $legacyCreatedColumn = null;

    protected ?string $legacyUpdatedColumn = 'update';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    /** De la tienda dada o de 全店舗, como appview/Util */
    public function scopeForShop(Builder $query, ?int $shopCd): void
    {
        $query->where(function (Builder $q) use ($shopCd) {
            $q->whereNull('tb_shop_cd')->orWhere('tb_shop_cd', 0);
            if ($shopCd) {
                $q->orWhere('tb_shop_cd', $shopCd);
            }
        });
    }

    /** Orden de la lista del legacy */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('disp_date')->orderByDesc('update');
    }
}
