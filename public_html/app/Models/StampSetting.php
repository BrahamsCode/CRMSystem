<?php

namespace App\Models;

use App\Enums\StampDisplayMode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tarjeta de sellos de una tienda */
class StampSetting extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'display_mode' => StampDisplayMode::class,
            'design' => 'array',
            'visit_stamp_flg' => 'integer',
            'expiry_flg' => 'integer',
            'notice_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Configuración de la tienda, con los valores por defecto si aún no se guardó */
    public static function forShop(Shop $shop): self
    {
        return static::firstOrNew(['shop_id' => $shop->id], [
            'card_size' => 10,
            'signup_bonus' => 0,
            'visit_stamp_flg' => 1,
            'interval_seconds' => 3600,
            'display_mode' => StampDisplayMode::ByShop,
            'design' => ['icon' => 'star', 'color' => '#c8343a'],
        ]);
    }
}
