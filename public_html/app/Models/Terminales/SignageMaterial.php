<?php

namespace App\Models\Terminales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use App\Models\Terminales\Concerns\HasLegacyLists;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 放映素材 (`tb_screensaver`). `filetype` guarda el texto japonés (lo recibe el
 * reproductor tal cual); `pic_flg` y `pic_extension` son listas `<>` por hueco de
 * imagen; `rank` ordena los materiales de un display (先頭に追加 = 0, 最後に追加 = max + 1).
 */
class SignageMaterial extends LegacyModel
{
    use HasHatuban, HasLegacyLists, LegacySoftDeletes, LegacyTimestamps;

    public const FILETYPE_IMAGE = '画像ファイル';

    public const FILETYPE_FLV = 'FLVファイル';

    public const FILETYPE_SWF = 'SWFファイル';

    protected $table = 'tb_screensaver';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_display_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    public function display(): BelongsTo
    {
        return $this->belongsTo(SignageDisplay::class, 'tb_display_cd', 'cd');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('rank')->orderBy('cd');
    }

    /** Huecos de imagen: [['flg' => '1', 'ext' => 'jpg'], …] en el orden del legacy */
    public function imageSlots(): array
    {
        $flags = $this->legacySlots('pic_flg');
        $extensions = $this->legacySlots('pic_extension');
        $slots = [];
        for ($i = 0, $n = max(count($flags), count($extensions)); $i < $n; $i++) {
            $slots[] = ['flg' => $flags[$i] ?? '', 'ext' => $extensions[$i] ?? ''];
        }

        return $slots;
    }

    /** `pic_flg` por posición (contrato de las vistas: `$material->picFlags`) */
    public function getPicFlagsAttribute(): array
    {
        return $this->legacySlots('pic_flg');
    }

    /** `pic_extension` por posición (contrato de las vistas: `$material->picExtensions`) */
    public function getPicExtensionsAttribute(): array
    {
        return $this->legacySlots('pic_extension');
    }

    /** `rank` para un alta: 0 al principio (先頭に追加) o max + 1 del display (最後に追加) */
    public static function rankForNew(?int $displayCd, bool $first): int
    {
        if ($first) {
            return 0;
        }

        return (int) static::withDeleted()->where('tb_display_cd', $displayCd)->max('rank') + 1;
    }
}
