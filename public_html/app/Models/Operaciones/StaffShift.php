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
 * Turnos (`tb_staff_shift`): misma forma que tb_work_time. Por día, listas paralelas `<>`:
 * `gaisyutu_time_N` (inicios, epoch), `kisha_time_N` (fines), `syukkin_kubun_N` (cd de シフト区分)
 * y `komment_N`. Varios turnos por día.
 */
class StaffShift extends LegacyModel
{
    use HasDayColumns, HasHatuban;

    public const DAY_PREFIXES = [
        'syukkin_time', 'taikin_time', 'gaisyutu_time', 'kisha_time',
        'syukkin_flg', 'syukkin_kubun', 'komment', 'etc1',
    ];

    protected $table = 'tb_staff_shift';

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

    /**
     * Turnos del día: [['start' => epoch, 'end' => epoch, 'kind_cd' => ?int, 'comment' => string], …]
     * Las posiciones van en paralelo en las cuatro listas.
     */
    public function shifts(int $day): array
    {
        $starts = $this->getDayList('gaisyutu_time', $day);
        $ends = $this->getDayList('kisha_time', $day);
        $kinds = $this->getDayList('syukkin_kubun', $day);
        $comments = $this->getDayList('komment', $day);

        $shifts = [];
        foreach ($starts as $i => $start) {
            if ($start === '') {
                continue;
            }
            $kind = $kinds[$i] ?? '';
            $shifts[] = [
                'start' => (int) $start,
                'end' => ($ends[$i] ?? '') === '' ? null : (int) $ends[$i],
                'kind_cd' => $kind === '' ? null : (int) $kind,
                'comment' => $comments[$i] ?? '',
            ];
        }

        return $shifts;
    }

    /** Guarda los turnos del día en las cuatro listas paralelas */
    public function setShifts(int $day, array $shifts): static
    {
        $this->month()->day($day); // valida que el día exista en el mes

        $join = fn (string $key) => implode('<>', array_map(fn (array $s) => (string) ($s[$key] ?? ''), $shifts));

        return $this->setDayValue('gaisyutu_time', $day, $join('start'))
            ->setDayValue('kisha_time', $day, $join('end'))
            ->setDayValue('syukkin_kubun', $day, $join('kind_cd'))
            ->setDayValue('komment', $day, $join('comment'));
    }
}
