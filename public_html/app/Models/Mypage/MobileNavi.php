<?php

namespace App\Models\Mypage;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menú de la mypage/app (`tb_mobile_navi`): S4 lo usa para el nombre del menú de
 * オススメ y 新着情報. Sin columnas de fecha. Orden en `rank`.
 */
class MobileNavi extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_mobile_navi';

    protected $casts = [
        'cd' => 'integer',
        'tb_master_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('rank');
    }

    /** Nombre del menú cuyo enlace contiene el texto dado (p. ej. 'recommend'), o el valor por defecto */
    public static function nameForLink(string $needle, string $default): string
    {
        return static::query()->where('link', 'like', '%'.$needle.'%')->value('name') ?: $default;
    }
}
