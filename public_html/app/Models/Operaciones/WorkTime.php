<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;
use App\Models\Operaciones\Concerns\HasDayColumns;
use App\Models\Shop;
use App\Models\Staff;
use App\Services\Operaciones\LegacyMonth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asistencia de la タイムカード (`tb_work_time`): una fila por empleado y mes,
 * `syukkinbi` = epoch del día 1 (0:00 JST) y por día N: entrada, salida, salidas y regresos
 * (listas `<>`), 出勤/遅刻, 退社/早退, comentario. ⏸ 勤怠
 */
class WorkTime extends LegacyModel
{
    use HasDayColumns, HasHatuban;

    public const DAY_PREFIXES = [
        'syukkin_time', 'taikin_time', 'gaisyutu_time', 'kisha_time',
        'syukkin_flg', 'syukkin_kubun', 'komment', 'etc1',
    ];

    protected $table = 'tb_work_time';

    protected $casts = [
        'cd' => 'integer',
        'shop_cd' => 'integer',
        'staff_cd' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_cd', 'cd');
    }

    public function scopeForMonth(Builder $query, LegacyMonth $month): void
    {
        $query->where('syukkinbi', $month->epoch());
    }

    public function month(): LegacyMonth
    {
        return LegacyMonth::fromEpoch((string) $this->syukkinbi);
    }

    /** Las columnas de un día, con el prefijo como clave */
    public function day(int $day): array
    {
        $values = [];
        foreach (self::DAY_PREFIXES as $prefix) {
            $values[$prefix] = $this->getDayValue($prefix, $day);
        }

        return $values;
    }
}
