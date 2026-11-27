<?php

namespace App\Models;

use App\Enums\Mypage\AppImageSection;
use App\Enums\Mypage\BannerMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/** Imagen de la app de la マイコレ (portada, banner, imagen HOME o miniatura) */
class AppImage extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'section' => AppImageSection::class,
            'banner_mode' => BannerMode::class,
            'position' => 'integer',
            'all_shops_flg' => 'integer',
        ];
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class)->withTimestamps();
    }

    public function scopeInSection(Builder $query, AppImageSection $section): Builder
    {
        return $query->where('section', $section)->orderBy('position');
    }

    /**
     * Ruta en Storage con el nombre de archivo del legacy:
     * hp/uploads/c6/<carpeta>/<archivo>, para que la URL vieja que devuelven
     * mpa_getCoverFlow / mpa_getTopBanner siga resolviendo.
     */
    public static function storagePathFor(AppImageSection $section, string $fileName): string
    {
        return 'hp/uploads/c6/'.$section->legacyFolder().'/'.$fileName;
    }

    /** URL pública con el `?r=<mtime>` que añadía el legacy para saltar la caché */
    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk('public')->url($this->path).'?r='.($this->mtime() ?? 0);
    }

    /** Fecha de modificación del archivo (Unix), o null si no existe */
    public function mtime(): ?int
    {
        $disk = Storage::disk('public');

        return $this->path && $disk->exists($this->path) ? $disk->lastModified($this->path) : null;
    }

    /** Visibles para un cliente de la tienda dada (全店舗 o asignadas a la tienda) */
    public function scopeVisibleForShop(Builder $query, ?int $shopId): Builder
    {
        return $query->active()->where(function (Builder $q) use ($shopId) {
            $q->where('all_shops_flg', 1);
            if ($shopId) {
                $q->orWhereHas('shops', fn (Builder $s) => $s->where('shops.id', $shopId));
            }
        });
    }
}
