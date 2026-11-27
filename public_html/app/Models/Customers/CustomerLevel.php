<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * Nivel de cliente (顧客レベル; sin enlace en el menú del legacy).
 * Tabla del legacy `tb_customer_level` (CONVENCIONES §0).
 */
class CustomerLevel extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_customer_level';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];
}
