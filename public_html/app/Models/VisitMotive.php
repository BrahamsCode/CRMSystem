<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitMotive extends BaseModel
{
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Clientes cuya primera visita se debió a este motivo */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
