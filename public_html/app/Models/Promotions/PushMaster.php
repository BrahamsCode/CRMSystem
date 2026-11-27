<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Notificación push emitida. La lee el servidor externo (g-api) por su cd.
 * Tabla legacy `push_master` (dueño: S3 Promoción).
 */
class PushMaster extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'push_master';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'version' => 'integer',
        'post_point' => 'integer',
        'point_expiration_date' => 'integer',
        'push_follow_master_cd' => 'integer',
    ];

    // post_type
    public const POST_MESSAGE = '1';

    public const POST_WITH_POINTS = '2';

    public function followMaster(): BelongsTo
    {
        return $this->belongsTo(PushFollowMaster::class, 'push_follow_master_cd', 'cd');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(PushNotification::class, 'type_master_cd', 'cd')->where('type', PushNotification::TYPE_PUSH);
    }
}
