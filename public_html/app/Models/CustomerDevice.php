<?php

namespace App\Models;

use App\Enums\DevicePlatform;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dispositivo del cliente para notificaciones push */
class CustomerDevice extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'platform' => DevicePlatform::class,
            'last_seen_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
