<?php

namespace App\Models;

use App\Enums\PointExtendMode;
use App\Enums\SignupPointTiming;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reglas de puntos de una tienda */
class PointSetting extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'expiry_flg' => 'integer',
            'extend_mode' => PointExtendMode::class,
            'signup_timing' => SignupPointTiming::class,
            'notices' => 'array',
            'cash_rate' => 'decimal:2',
            'card_rate' => 'decimal:2',
            'include_used_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public static function forShop(Shop $shop): self
    {
        return static::firstOrNew(['shop_id' => $shop->id], [
            'extend_mode' => PointExtendMode::AnyMovement,
            'signup_timing' => SignupPointTiming::OnSignup,
            'cash_rate' => 1,
            'card_rate' => 1,
            'notices' => [],
        ]);
    }
}
