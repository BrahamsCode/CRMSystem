<?php

namespace App\Models\Customers;

use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * Enlace de la ventana 集計ツール.
 * Tabla del legacy `tb_shukei_link` (CONVENCIONES §0).
 */
class ReportLink extends LegacyModel
{
    use LegacySoftDeletes;

    protected $table = 'tb_shukei_link';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];
}
