<?php

namespace App\Models\Reservations;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menú reservable (予約項目). `type_flg`: 1 ベースメニュー, 2 オプション; `shoyou_time`: minutos; `menu_end_time`: segundos desde las 00:00.
 * Tabla del legacy `tb_yoyaku_koumoku_master` (CONVENCIONES §0).
 */
class ReservationMenu extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_yoyaku_koumoku_master';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'shoyou_time' => 'integer',
        'rank' => 'integer',
        'tb_yoyaku_koumoku_category_cd' => 'integer',
        'tb_setsubi_cd' => 'integer',
        'menu_end_time' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationMenuCategory::class, 'tb_yoyaku_koumoku_category_cd', 'cd');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'tb_setsubi_cd', 'cd');
    }

    public function staffWorks(): HasMany
    {
        return $this->hasMany(StaffWork::class, 'tb_yoyaku_koumoku_master_cd', 'cd');
    }

    public const TYPE_BASE = '1';

    public const TYPE_OPTION = '2';

    /** `tb_shop_cd = -1`: menú de 全店舗 (todas las tiendas) */
    public const ALL_SHOPS = -1;

    /** 最終受付時間 del formulario (時 00-29, 分 00/30) → segundos desde las 00:00 (más de 86400 si pasa de las 24) */
    public static function endTimeSeconds(mixed $hour, mixed $minute): int
    {
        return (int) $hour * 3600 + (int) $minute * 60;
    }

    /** `menu_end_time` → ['HH', 'MM'] para los selectores (como el legacy: 24時 en adelante incluidos) */
    public function endTimeParts(): array
    {
        if ($this->menu_end_time === null) {
            return [null, null];
        }

        return [
            sprintf('%02d', intdiv($this->menu_end_time, 3600)),
            sprintf('%02d', intdiv($this->menu_end_time % 3600, 60)),
        ];
    }
}
