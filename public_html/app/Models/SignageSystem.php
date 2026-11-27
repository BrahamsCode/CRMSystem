<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sistema integrado que puede mostrar una pantalla (tb_dispaly_systems) */
class SignageSystem extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'system_type' => 'integer',
            'system_flg' => 'integer',
            'non_coupon_flg' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
