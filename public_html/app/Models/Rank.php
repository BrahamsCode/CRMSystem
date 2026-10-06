<?php

namespace App\Models;

use App\Enums\RankType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => RankType::class,
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Dos relaciones explícitas en vez de una que elija la clave según el `type`:
     * la clave foránea se fija al definir la relación, así que una dinámica
     * devolvería lo que no es en cuanto se usara eager loading.
     */
    public function customersByAmount(): HasMany
    {
        return $this->hasMany(Customer::class, 'amount_rank_id');
    }

    public function customersByVisits(): HasMany
    {
        return $this->hasMany(Customer::class, 'visit_rank_id');
    }

    public function scopeOfType(Builder $query, RankType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * ¿Entra este importe (o nº de visitas) en el rango?
     *
     * El máximo es EXCLUSIVO: el legacy lo rotula «◯◯円以上 ～ ◯◯円未満»
     * (mayor o igual que el mínimo, menor que el máximo).
     */
    public function covers(int $value): bool
    {
        return $value >= $this->min_value
            && ($this->max_value === null || $value < $this->max_value);
    }
}
