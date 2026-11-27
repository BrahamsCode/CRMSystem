<?php

namespace App\Models\Promotions;

use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrega de un push (o del push espejo de un correo) a un cliente, con aperturas.
 * Tabla legacy `push_notifications` (dueño: S3 Promoción).
 */
class PushNotification extends LegacyModel
{
    use LegacyTimestamps;

    protected $table = 'push_notifications';

    protected $casts = [
        'cd' => 'integer',
        'type_master_cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'open_cnt' => 'integer',
        'open_date' => 'integer',
    ];

    protected ?string $legacyUpdatedColumn = null;

    public const TYPE_PUSH = 'push';

    public const TYPE_MAILMAGA = 'mailmaga';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'tb_customer_cd', 'cd');
    }
}
