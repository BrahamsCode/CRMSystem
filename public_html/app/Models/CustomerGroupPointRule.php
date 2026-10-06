<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGroupPointRule extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'use_flg' => 'integer',
            'display_flg' => 'integer',
            'rate' => 'decimal:2',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }
}
