<?php

namespace App\Models;

use App\Enums\CustomCategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de información adicional (ej. «Mascota», «カルテ», «家族情報»).
 */
class CustomCategory extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => CustomCategoryType::class,
            'columns' => 'integer',
            'search_flg' => 'integer',
            'display_flg' => 'integer',
        ];
    }

    /**
     * El ON DELETE CASCADE de la base no se dispara con borrado lógico (es un
     * UPDATE, no un DELETE), así que los campos hijos hay que arrastrarlos a mano
     * o quedan huérfanos apuntando a una categoría que ya no existe.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $categoria) {
            $categoria->fields->each->delete();
        });

        static::restoring(function (self $categoria) {
            $categoria->fields()->onlyTrashed()->restore();
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CustomField::class)->orderBy('sort');
    }

    /** Solo tienen filas propias las categorías de tipo múltiple */
    public function values(): HasMany
    {
        return $this->hasMany(CustomValue::class);
    }

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('search_flg', 1);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('display_flg', 1);
    }

    public function isMultiple(): bool
    {
        return $this->type === CustomCategoryType::Multiple;
    }
}
