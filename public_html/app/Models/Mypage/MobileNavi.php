<?php

namespace App\Models\Mypage;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menú de la mypage/app (`tb_mobile_navi`): S4 lo usa para el nombre del menú de オススメ. Sin columnas de fecha. Orden en `rank`.
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

    /** cd de la fila de オススメ en tb_mobile_navi (fijo en el legacy: left_navi6.php y recommend.php) */
    public const RECOMMEND_CD = 9;

    /**
     * Título del menú de オススメ como left_navi6.php: nombre de la fila 9 (mb_convert_kana 'KV') + 管理.
     * Vacío si no hay nombre: el menú usa entonces su etiqueta fija (label_from de NUCLEO.md §6).
     */
    public static function recommendTitle(): string
    {
        $name = (string) static::query()->withDeleted()->whereKey(self::RECOMMEND_CD)->value('name');

        return $name === '' ? '' : mb_convert_kana($name, 'KV').'管理';
    }
}
