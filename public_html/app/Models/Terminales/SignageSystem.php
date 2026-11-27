<?php

namespace App\Models\Terminales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sistemas integrados que puede mostrar una pantalla (`tb_dispaly_systems`, sic). Solo `touroku_date`. */
class SignageSystem extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_dispaly_systems';

    protected ?string $legacyUpdatedColumn = null;

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'rank' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }
}
