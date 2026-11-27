<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * Rango por número de visitas (来店回数ランク), de jyouken1 a jyouken2.
 * Tabla legacy `tb_raiten_rank_master` (dueño: S3 Promoción).
 */
class RaitenRankMaster extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_raiten_rank_master';

    protected $casts = [
        'cd' => 'integer',
        'jyouken1' => 'integer',
        'jyouken2' => 'integer',
        'rank' => 'integer',
    ];
}
