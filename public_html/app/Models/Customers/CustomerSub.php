<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valores de campos personalizados del cliente; también ventas (`tb_search_cate_cd = -1`) y gastos (`-2`) de karute_menu.
 * Tabla del legacy `tb_customer_sub` (CONVENCIONES §0).
 */
class CustomerSub extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_customer_sub';

    protected $casts = [
        'cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_search_cate_cd' => 'integer',
        'tb_search_form_cd' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomCategory::class, 'tb_search_cate_cd', 'cd');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'tb_search_form_cd', 'cd');
    }

    public const SALE_CATEGORY = -1;

    public const EXPENSE_CATEGORY = -2;

    /** Valores de campos personalizados (excluye ventas y gastos) */
    public function scopeCustomValues(Builder $query): void
    {
        $query->where('tb_search_cate_cd', '>', 0);
    }

    /** Ventas de karute_menu (売上データ) */
    public function scopeSales(Builder $query): void
    {
        $query->where('tb_search_cate_cd', self::SALE_CATEGORY);
    }

    /** Gastos de karute_menu (経費) */
    public function scopeExpenses(Builder $query): void
    {
        $query->where('tb_search_cate_cd', self::EXPENSE_CATEGORY);
    }

    /** Valores de una casilla múltiple: el legacy los guarda como 'a<>b<>' */
    public function checkedValues(): array
    {
        return array_values(array_filter(explode('<>', (string) $this->text_data), fn ($v) => $v !== ''));
    }
}
