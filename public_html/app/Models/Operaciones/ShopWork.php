<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;
use App\Models\Operaciones\Concerns\HasDayColumns;
use App\Models\Shop;
use App\Services\Operaciones\LegacyMonth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 営業日設定 (`tb_shop_work`): una fila por tienda y mes, `syukkinbi` = 'Y/n', y
 * `syukkin_time_1..31` = '1' 営業日 / '0' 休日. La lee también el calendario de reservas (S2).
 */
class ShopWork extends LegacyModel
{
    use HasDayColumns, HasHatuban;

    public const DAY_PREFIXES = ['syukkin_time'];

    public const OPEN = '1';

    public const CLOSED = '0';

    protected $table = 'tb_shop_work';

    protected $casts = [
        'cd' => 'integer',
        'shop_cd' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_cd', 'cd');
    }

    public function scopeForMonth(Builder $query, int $shopCd, LegacyMonth $month): void
    {
        $query->where('shop_cd', $shopCd)->where('syukkinbi', $month->slash());
    }

    /** La fila del mes; la crea vacía si no existe (shop_work_calendar.php hace lo mismo al guardar) */
    public static function forMonthOrNew(int $shopCd, LegacyMonth $month): self
    {
        return static::query()->forMonth($shopCd, $month)->first()
            ?? new static(['shop_cd' => $shopCd, 'syukkinbi' => $month->slash()]);
    }

    public function month(): LegacyMonth
    {
        return LegacyMonth::fromSlash((string) $this->syukkinbi);
    }

    /** Sin dato cuenta como 営業日, como pinta el calendario del legacy */
    public function isOpen(int $day): bool
    {
        return $this->getDayValue('syukkin_time', $day) !== self::CLOSED;
    }

    public function setOpen(int $day, bool $open): static
    {
        $this->month()->day($day); // valida que el día exista en el mes

        return $this->setDayValue('syukkin_time', $day, $open ? self::OPEN : self::CLOSED);
    }

    /** Días 営業日 del mes */
    public function openDays(): array
    {
        return array_values(array_filter(
            range(1, $this->month()->daysInMonth()),
            fn (int $day) => $this->isOpen($day),
        ));
    }
}
