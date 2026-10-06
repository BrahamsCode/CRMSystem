<?php

namespace App\Models;

use App\Enums\RankScheduleMode;
use App\Enums\RankType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankSchedule extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => RankType::class,
            'mode' => RankScheduleMode::class,
            'enabled_flg' => 'integer',
            'months' => 'array',
            'days' => 'array',
            'weekdays' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Los rangos que esta programación recalcula.
     *
     * No es una relación de Eloquent a propósito: dependería de $this->type, y
     * eso se evalúa al definir la relación, así que el eager loading devolvería
     * los rangos del tipo equivocado.
     */
    public function ranks(): \Illuminate\Database\Eloquent\Collection
    {
        return Rank::where('shop_id', $this->shop_id)
            ->where('type', $this->type)
            ->ordered()
            ->get();
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled_flg', 1);
    }

    public function isEnabled(): bool
    {
        return $this->enabled_flg === 1;
    }
}
