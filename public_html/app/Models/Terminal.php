<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Terminal CRM (`tb_terminal`: tablets, felica, quiosco…). Orden en `rank`.
 * Sin `disp_flg`: el legacy la borra de verdad (`category7/felica/ajax_del.php`).
 */
class Terminal extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_terminal';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
        'gps_raiten_tb_customer_cd' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }
}
