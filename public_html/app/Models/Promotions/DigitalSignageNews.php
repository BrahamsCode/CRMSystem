<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * Noticia del panel (新着情報 de category3).
 * Tabla legacy `tb_digital_signage` (dueño: S3 Promoción).
 */
class DigitalSignageNews extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_digital_signage';

    protected $casts = [
        'cd' => 'integer',
        'play_time' => 'integer',
        'rank' => 'integer',
    ];
}
