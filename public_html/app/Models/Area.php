<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;

/**
 * エリア (`tb_area`): árbol padre/hijo (`pid`, raíz = NULL o < 1), orden `row_lis`, `flg = '1'` visible,
 * `disp_flg = '9'` borrada. Las tiendas cuelgan por `tb_shop.area_cd`.
 */
class Area extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_area';

    protected $casts = ['cd' => 'integer', 'pid' => 'integer', 'row_lis' => 'integer', 'tb_shop_cd' => 'integer'];
}
