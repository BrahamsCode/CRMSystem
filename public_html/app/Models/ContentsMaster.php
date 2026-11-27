<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pares categoría/campo/valor del legacy (`contents_master`): banderas y textos por función
 * (`features_config.php`, `Base.php`). Valor en `text_data`, `int_data` o `float_data`;
 * el legacy solo lee filas con `disp_flg = '1'`. Fechas en epoch (columnas bigint).
 *
 * Uso: `ContentsMaster::text('mycolle', 'app_layout_mode')`, `ContentsMaster::put('mycolle', 'app_layout_mode', '2')`.
 */
class ContentsMaster extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'contents_master';

    protected $casts = [
        'cd' => 'integer',
        'int_data' => 'integer',
        'float_data' => 'float',
    ];

    public function scopeEntry(Builder $query, string $category, string $field): void
    {
        $query->where('category', $category)->where('field', $field)->where('disp_flg', '1');
    }

    public static function entryRow(string $category, string $field): ?static
    {
        return static::query()->entry($category, $field)->orderBy('cd')->first();
    }

    public static function text(string $category, string $field, ?string $default = null): ?string
    {
        return static::entryRow($category, $field)?->text_data ?? $default;
    }

    public static function int(string $category, string $field, ?int $default = null): ?int
    {
        return static::entryRow($category, $field)?->int_data ?? $default;
    }

    /** Crea o actualiza el valor de texto (como features_config_register: UPDATE si existe, si no INSERT) */
    public static function put(string $category, string $field, ?string $text): static
    {
        $row = static::entryRow($category, $field)
            ?? new static(['category' => $category, 'field' => $field, 'disp_flg' => '1', 'hyouji_flg' => '1']);
        $row->text_data = $text;
        $row->save();

        return $row;
    }
}
