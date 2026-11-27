<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * Punto predefinido de ポイント管理 (legacy: category1/customer/point_setting*.php).
 * Tabla del legacy `tb_point_setting` (cd, name, point, rank, disp_flg, touroku_date, update_date en epoch texto);
 * `cd` con hatuban `tb_point_setting`, borrado lógico `disp_flg = '9'`.
 */
class PointSetting extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_point_setting';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'point' => 'integer',
    ];
}
