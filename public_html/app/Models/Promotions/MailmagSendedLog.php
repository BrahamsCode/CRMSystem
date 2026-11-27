<?php

namespace App\Models\Promotions;

use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Destinatarios de un envío (hasta el tope de admin_master.mailmag_sended_log). Sin clave propia.
 * Tabla legacy `mailmag_sended_log` (dueño: S3 Promoción).
 */
class MailmagSendedLog extends LegacyModel
{
    protected $table = 'mailmag_sended_log';

    protected $primaryKey = null;

    protected $casts = [
        'mailmag_master_cd' => 'integer',
        'tb_customer_cd' => 'integer',
    ];

    public function mail(): BelongsTo
    {
        return $this->belongsTo(MailmagMaster::class, 'mailmag_master_cd', 'cd');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'tb_customer_cd', 'cd');
    }
}
