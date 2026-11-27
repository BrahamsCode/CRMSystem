<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plantilla de correo (テンプレート管理).
 * Tabla legacy `tb_mail_template` (dueño: S3 Promoción).
 */
class MailTemplate extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_mail_template';

    protected $casts = [
        'cd' => 'integer',
        'tb_mail_template_category_cd' => 'integer',
        'rank' => 'integer',
    ];

    public const MAIL_TYPE_NORMAL = '';

    public const MAIL_TYPE_DECO = 'deco';

    public function category(): BelongsTo
    {
        return $this->belongsTo(MailTemplateCategory::class, 'tb_mail_template_category_cd', 'cd');
    }
}
