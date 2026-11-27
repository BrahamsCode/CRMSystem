<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cola del envío programado (antes la leía el crontab de cSendSchedule; ahora el Scheduler).
 * Tabla legacy `send_schedule` (dueño: S3 Promoción).
 */
class SendSchedule extends LegacyModel
{
    use HasHatuban;

    protected $table = 'send_schedule';

    protected $casts = [
        'cd' => 'integer',
        'mag_cd' => 'integer',
    ];

    public function mail(): BelongsTo
    {
        return $this->belongsTo(MailmagMaster::class, 'mag_cd', 'cd');
    }
}
