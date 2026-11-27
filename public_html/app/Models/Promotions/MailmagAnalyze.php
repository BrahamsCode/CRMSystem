<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acceso a Mi página desde un correo (クリックカウンタ), con el perfil del momento.
 * Tabla legacy `tb_mailmag_analyze` (dueño: S3 Promoción).
 */
class MailmagAnalyze extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    protected $table = 'tb_mailmag_analyze';

    protected $casts = [
        'cd' => 'integer',
        'mailmag_master_cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'customer_age' => 'integer',
    ];

    protected ?string $legacyUpdatedColumn = null;

    public function mail(): BelongsTo
    {
        return $this->belongsTo(MailmagMaster::class, 'mailmag_master_cd', 'cd');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class, 'tb_customer_cd', 'cd');
    }
}
