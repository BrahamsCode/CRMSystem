<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de información adicional (顧客追加情報設定). `record_type`: '' un registro por cliente, '1' varios (複数タイプ).
 * Tabla del legacy `tb_search_cate` (CONVENCIONES §0).
 */
class CustomCategory extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_search_cate';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'disp_column' => 'integer',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(CustomField::class, 'tb_search_cate_cd', 'cd');
    }

    /** Las del módulo de clientes (el legacy guarda la carpeta dueña en `category`) */
    public function scopeForCustomers(Builder $query): void
    {
        $query->where('category', 'category1');
    }

    public function isMultiple(): bool
    {
        return (string) $this->record_type === '1';
    }
}
