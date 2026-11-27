<?php

namespace App\Models\Sales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gasto (経費管理, category1/karute_menu/keihi_register.php) sobre `tb_customer_sub` con
 * `tb_search_cate_cd = -2` (scope global) y `tb_search_form_cd = -10`. Una fila por gasto:
 *
 * | Columna       | Significado                                                     |
 * |---------------|-----------------------------------------------------------------|
 * | float_data    | 使用金額 (円)                                                     |
 * | text_data     | 使用目的 (form2Db: LegacyText::db)                                |
 * | update_date   | 使用日 'Y/m/d' (no un epoch)                                      |
 * | touroku_date  | epoch de la última alta o edición (el legacy lo pisa al editar)   |
 * | int_data      | tienda (tb_shop.cd)                                              |
 *
 * `cd` con el contador `hatuban` 'tb_customer_sub' (cHatuban), no con createHatsuban como las ventas.
 * Sin LegacyTimestamps: `update_date` es el 使用日.
 */
class Expense extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_customer_sub';

    public const CATEGORY = -2;

    public const FORM = -10;

    protected $casts = [
        'cd' => 'integer',
        'tb_search_cate_cd' => 'integer',
        'tb_search_form_cd' => 'integer',
        'float_data' => 'float',
        'int_data' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('gastos', function (Builder $query) {
            $query->where($query->qualifyColumn('tb_search_cate_cd'), self::CATEGORY);
        });

        static::creating(function (Expense $expense) {
            $expense->tb_search_cate_cd = self::CATEGORY;
            $expense->tb_search_form_cd = self::FORM;
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'int_data', 'cd');
    }

    /** Gastos de una tienda (`int_data`) */
    public function scopeOfShop(Builder $query, int $shopCd): void
    {
        $query->where('int_data', $shopCd);
    }
}
