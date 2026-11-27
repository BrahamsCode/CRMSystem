<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * Grupo de cliente con su regla de puntos (顧客グループ). S2 lo usa en 顧客グループ設定 sin cambiarlo.
 * Tabla legacy `tb_customer_group` (dueño: S3 Promoción).
 */
class CustomerGroup extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_customer_group';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'shiyou_point_jyougen' => 'integer',
        'point_percentage_genkin' => 'float',
        'point_percentage_card' => 'float',
    ];
}
