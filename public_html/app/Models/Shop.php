<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tienda (`tb_shop`). Nombre en `kaishamei`; `disp_flg` 1 = activa, 9 = borrada
 * (`category0/shop/shop_delete.php`). `cd` con hatuban `tb_shop` (`shop_new_register.php`).
 */
class Shop extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_shop';

    protected $casts = [
        'cd' => 'integer',
        'master_cd' => 'integer',
        'shop_category_cd' => 'integer',
        'area_cd' => 'integer',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'shop_cd', 'cd');
    }

    public function terminals(): HasMany
    {
        return $this->hasMany(Terminal::class, 'tb_shop_cd', 'cd');
    }

    /** Nombre visible de la tienda (el legacy usa `kaishamei`) */
    public function getNameAttribute(): ?string
    {
        return $this->kaishamei;
    }
}
