<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;
use Illuminate\Database\Eloquent\Builder;

/**
 * 店舗グループ (`tb_shop_category`): `title`, `flg = 1` activa, orden `sort`.
 * Fechas `record_date`/`update_date` como timestamp (esta tabla no usa epoch).
 */
class ShopCategory extends LegacyModel
{
    use HasHatuban;

    protected $table = 'tb_shop_category';

    protected $casts = ['cd' => 'integer', 'flg' => 'integer', 'sort' => 'integer'];

    public function scopeActive(Builder $query): void
    {
        $query->where('flg', 1)->orderBy('sort');
    }

    /** [cd => title] de las activas, como el `$shop_category_ary` del legacy */
    public static function titles(): array
    {
        return static::query()->active()->pluck('title', 'cd')->all();
    }
}
