<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Regla de push programado / follow / auto (disp_flg 1 activo, 7 pausa, 9 borrado).
 * Tabla legacy `push_follow_master` (dueño: S3 Promoción).
 */
class PushFollowMaster extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'push_follow_master';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'post_point' => 'integer',
        'point_expiration_days' => 'integer',
        'next_send_date' => 'integer',
    ];

    public const PAUSED_FLAG = '7';

    public function pushes(): HasMany
    {
        return $this->hasMany(PushMaster::class, 'push_follow_master_cd', 'cd');
    }
}
