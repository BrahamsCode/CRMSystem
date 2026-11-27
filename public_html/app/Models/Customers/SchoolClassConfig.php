<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * クラス設定 (academias; sin enlace en el menú del legacy).
 * Tabla del legacy `tb_schoolclass_config` (CONVENCIONES §0).
 */
class SchoolClassConfig extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_schoolclass_config';

    protected $casts = [
        'cd' => 'integer',
        'cate_order' => 'integer',
    ];
}
