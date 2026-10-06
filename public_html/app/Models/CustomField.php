<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use App\Enums\DisplayScope;
use App\Enums\FieldSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => CustomFieldType::class,
            'display_scope' => DisplayScope::class,
            'size' => FieldSize::class,
            'required_flg' => 'integer',
            'search_flg' => 'integer',
            'mail_magazine_flg' => 'integer',
            'config' => 'array',
        ];
    }

    /** Mismo motivo que en CustomCategory: el cascade de la base no cubre el borrado lógico */
    protected static function booted(): void
    {
        static::deleting(function (self $campo) {
            $campo->options->each->delete();
        });

        static::restoring(function (self $campo) {
            $campo->options()->onlyTrashed()->restore();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomCategory::class, 'custom_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)->orderBy('sort');
    }

    public function shop(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Shop::class,
            CustomCategory::class,
            'id',               // custom_categories.id
            'id',               // shops.id
            'custom_category_id',
            'shop_id',
        );
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('search_flg', 1);
    }

    /** Solo select, radio y checkbox usan la tabla de opciones */
    public function usesOptions(): bool
    {
        return $this->type?->hasOptions() ?? false;
    }
}
