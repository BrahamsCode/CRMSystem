<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * Motivo de la primera visita (初回来店動機).
 * Tabla del legacy `tb_raiten_douki_master` (CONVENCIONES §0).
 */
class VisitMotive extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_raiten_douki_master';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];
}
