<?php

namespace App\Models;

use App\Enums\StampMovement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Movimiento de sellos */
class CustomerStamp extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => StampMovement::class,
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
