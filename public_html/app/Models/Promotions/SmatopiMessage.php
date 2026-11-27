<?php

namespace App\Models\Promotions;

use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de envíos de スマトピ連携. cd serial (sin hatuban).
 * Tabla legacy `smatopi_message` (dueño: S3 Promoción).
 */
class SmatopiMessage extends LegacyModel
{
    protected $table = 'smatopi_message';

    protected $casts = [
        'cd' => 'integer',
        'shop_cd' => 'integer',
        'record_date' => 'datetime',
    ];

    public $incrementing = true;

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'shop_cd', 'cd');
    }
}
