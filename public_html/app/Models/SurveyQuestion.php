<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestion extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => CustomFieldType::class,
            'options' => 'array',
            'required_flg' => 'integer',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
