<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/** ガチャリン: configuración del sorteo (fila única) */
class LotterySetting extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'enabled_flg' => 'integer',
            'max_tries' => 'integer',
            'limit_flg' => 'integer',
            'prize_count' => 'integer',
            'enabled_on_enter_flg' => 'integer',
            'max_tries_on_enter' => 'integer',
            'limit_on_enter_flg' => 'integer',
            'rate_on_enter' => 'decimal:2',
            'limit_on_form_flg' => 'integer',
            'rate_on_form' => 'decimal:2',
        ];
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(LotteryPrize::class)->orderBy('rank');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /** Probabilidad de ハズレ: lo que queda hasta 100 % (no se guarda) */
    public function missRate(): float
    {
        $sum = $this->prizes->sum(fn (LotteryPrize $prize) => (float) $prize->rate);

        return max(0.0, round(100 - $sum, 2));
    }
}
