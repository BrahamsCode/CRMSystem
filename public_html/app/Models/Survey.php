<?php

namespace App\Models;

use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends BaseModel
{
    use HasUid;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
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

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /** Activa y dentro de sus fechas */
    public function isOpen(): bool
    {
        $today = today();

        return $this->status === 1
            && ($this->starts_on === null || $this->starts_on->lte($today))
            && ($this->ends_on === null || $this->ends_on->gte($today));
    }
}
