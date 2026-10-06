<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valores de las categorías de tipo múltiple, donde un cliente puede tener
 * varios registros de la misma categoría (por ejemplo, varias mascotas).
 *
 * Las categorías de tipo normal no usan esta tabla: su valor vive en
 * customers.custom_data.
 */
class CustomValue extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'values' => 'array',
            'row_no' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomCategory::class, 'custom_category_id');
    }
}
