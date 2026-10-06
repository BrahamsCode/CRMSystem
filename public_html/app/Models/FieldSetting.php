<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de los campos estándar de customers: qué se pide en el registro
 * móvil, qué es obligatorio, qué sirve de filtro y qué se exporta a CSV.
 */
class FieldSetting extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'mobile_display_flg' => 'integer',
            'mobile_required_flg' => 'integer',
            'search_flg' => 'integer',
            'csv_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('search_flg', 1);
    }

    public function scopeExportable(Builder $query): Builder
    {
        return $query->where('csv_flg', 1);
    }
}
