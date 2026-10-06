<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestMailAddress extends BaseModel
{
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
