<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'visited_at' => 'datetime',
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

    public function visitMotive(): BelongsTo
    {
        return $this->belongsTo(VisitMotive::class);
    }
}
