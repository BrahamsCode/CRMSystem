<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** オススメ: contenido recomendado que muestra la app */
class Recommendation extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'published_on' => 'date',
            'link_type' => 'integer',
            'image_type' => 'integer',
            'push_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Las de la tienda del cliente más las de 全店舗, como appview/Util */
    public function scopeForShop(Builder $query, ?int $shopId): Builder
    {
        return $query->where(function (Builder $q) use ($shopId) {
            $q->whereNull('shop_id');
            if ($shopId) {
                $q->orWhere('shop_id', $shopId);
            }
        });
    }
}
