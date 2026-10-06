<?php

namespace App\Models;

use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shop extends BaseModel
{
    use HasUid;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'online_flg' => 'integer',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    public function terminals(): HasMany
    {
        return $this->hasMany(Terminal::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    // --- Configuración del módulo de clientes, toda por tienda ---

    public function customerGroups(): HasMany
    {
        return $this->hasMany(CustomerGroup::class)->orderBy('sort');
    }

    public function defaultCustomerGroup(): HasOne
    {
        return $this->hasOne(CustomerGroup::class)->where('default_flg', 1);
    }

    public function ranks(): HasMany
    {
        return $this->hasMany(Rank::class)->orderBy('sort');
    }

    public function rankSchedules(): HasMany
    {
        return $this->hasMany(RankSchedule::class);
    }

    public function visitMotives(): HasMany
    {
        return $this->hasMany(VisitMotive::class)->orderBy('sort');
    }

    public function fieldSettings(): HasMany
    {
        return $this->hasMany(FieldSetting::class)->orderBy('sort');
    }

    public function customCategories(): HasMany
    {
        return $this->hasMany(CustomCategory::class)->orderBy('sort');
    }
}
