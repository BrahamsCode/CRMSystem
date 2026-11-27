<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de plantilla (テンプレートカテゴリ管理); orden por rank.
 * Tabla legacy `tb_mail_template_category` (dueño: S3 Promoción).
 */
class MailTemplateCategory extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_mail_template_category';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(MailTemplate::class, 'tb_mail_template_category_cd', 'cd');
    }
}
