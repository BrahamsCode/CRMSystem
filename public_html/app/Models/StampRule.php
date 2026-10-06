<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampRule extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'notify_flg' => 'integer',
            'reset_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
