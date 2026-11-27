<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Regla de sellos por tramo (スタンプルール). stamp_count = sellos de la visita.
 * Sin disp_flg: el legacy la borra físicamente; hyouji_flg = 9 la oculta.
 * Tabla legacy `tb_stamp_rules` (dueño: S3 Promoción).
 */
class StampRule extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_stamp_rules';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'day_of_week' => 'integer',
        'stamp_count' => 'integer',
    ];

    // type_flg (ルール区分); el número es también la prioridad
    public const TYPE_TIME = '1';
    public const TYPE_WEEKDAY = '2';
    public const TYPE_PERIOD = '3';
    public const TYPE_MONTH_DAY = '4';
    public const TYPE_DATE = '5';

    /** start_time = 32 en el tipo 4: último día del mes */
    public const LAST_DAY = 32;

    /** tb_shop_cd = -1: todas las tiendas */
    public const ALL_SHOPS = -1;

    public const HIDDEN_FLAG = '9';

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
