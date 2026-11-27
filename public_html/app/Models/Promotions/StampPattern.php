<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * Diseño de sello del catálogo (79); imagen en hp/uploads/c4/stamp/<cd>.gif.
 * Tabla legacy `tb_stamp_pattern` (dueño: S3 Promoción).
 */
class StampPattern extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_stamp_pattern';

    protected $casts = [
        'cd' => 'integer',
    ];

    protected ?string $legacyCreatedColumn = null;
}
