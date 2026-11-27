<?php

namespace App\Models\Customers;

use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * Ocupación (catálogo de solo lectura).
 * Tabla del legacy `tb_job` (CONVENCIONES §0).
 */
class Job extends LegacyModel
{
    use LegacySoftDeletes;

    protected $table = 'tb_job';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];
}
