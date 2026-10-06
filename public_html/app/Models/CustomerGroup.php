<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'default_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
