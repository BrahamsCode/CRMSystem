<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ubicación enviada por la app (filtro «estuvo cerca de una tienda») */
class CustomerLocation extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'recorded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
