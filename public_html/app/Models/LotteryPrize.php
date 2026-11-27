<?php

namespace App\Models;

use App\Enums\LotteryRank;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Premio de la ガチャリン: cantidad y probabilidad por rango */
class LotteryPrize extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'rank' => LotteryRank::class,
            'quantity' => 'integer',
            'rate' => 'decimal:2',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(LotterySetting::class, 'lottery_setting_id');
    }
}
