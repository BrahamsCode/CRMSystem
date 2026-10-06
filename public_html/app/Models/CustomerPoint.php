<?php

namespace App\Models;

use App\Enums\PointMovement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Movimiento de puntos */
class CustomerPoint extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => PointMovement::class,
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
