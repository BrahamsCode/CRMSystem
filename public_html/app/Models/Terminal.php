<?php

namespace App\Models;

use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Terminal extends BaseModel
{
    use HasUid;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
