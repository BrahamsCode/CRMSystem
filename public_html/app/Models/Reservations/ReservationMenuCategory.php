<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de menú reservable (予約項目カテゴリ; global, sin tienda).
 * Tabla del legacy `tb_yoyaku_koumoku_category` (CONVENCIONES §0).
 */
class ReservationMenuCategory extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_yoyaku_koumoku_category';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];

    /** `hyouji_flg`: '1' 表示, '9' 非表示 */
    public const VISIBLE = '1';

    public const HIDDEN = '9';

    public function menus(): HasMany
    {
        return $this->hasMany(ReservationMenu::class, 'tb_yoyaku_koumoku_category_cd', 'cd');
    }
}
