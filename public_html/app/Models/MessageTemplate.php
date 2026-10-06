<?php

namespace App\Models;

use App\Enums\TemplateCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'category' => TemplateCategory::class,
            'html_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
