<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Encuesta (お客様アンケート管理): cabecera.
 * Tabla legacy `tb_c3_anketo2_cate` (dueño: S3 Promoción).
 */
class Anketo2Cate extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_c3_anketo2_cate';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'start_date' => 'integer',
        'end_date' => 'integer',
        'attached_cd' => 'integer',
    ];

    public function forms(): HasMany
    {
        return $this->hasMany(Anketo2Form::class, 'tb_c3_anketo_cate_cd', 'cd');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Anketo2Sub::class, 'tb_c3_anketo_cate_cd', 'cd');
    }

    /** Cupón de premio */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(MycouponMaster::class, 'attached_cd', 'cd');
    }
}
