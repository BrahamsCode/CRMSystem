<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * 学年設定 (academias; sin enlace en el menú del legacy).
 * Tabla del legacy `tb_schoolyear_config` (CONVENCIONES §0).
 */
class SchoolYearConfig extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_schoolyear_config';

    protected $casts = [
        'cd' => 'integer',
        'cate_order' => 'integer',
    ];
}
