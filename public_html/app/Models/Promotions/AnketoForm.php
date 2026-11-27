<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pregunta de la encuesta (アンケート管理, 項目); orden por rank.
 * Tabla legacy `tb_c3_anketo_form` (dueño: S3 Promoción).
 */
class AnketoForm extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_c3_anketo_form';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'flds_row' => 'integer',
        'flds_col' => 'integer',
        'tb_c3_anketo_cate_cd' => 'integer',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(AnketoCate::class, 'tb_c3_anketo_cate_cd', 'cd');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AnketoSub::class, 'tb_c3_anketo_form_cd', 'cd');
    }
}
