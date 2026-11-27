<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Respuesta a una pregunta (お客様アンケート管理); una respuesta completa = filas con el mismo category_inner_group.
 * Tabla legacy `tb_c3_anketo2_sub` (dueño: S3 Promoción).
 */
class Anketo2Sub extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_c3_anketo2_sub';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_c3_anketo_cate_cd' => 'integer',
        'tb_c3_anketo_form_cd' => 'integer',
        'float_data' => 'float',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Anketo2Cate::class, 'tb_c3_anketo_cate_cd', 'cd');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Anketo2Form::class, 'tb_c3_anketo_form_cd', 'cd');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class, 'tb_customer_cd', 'cd');
    }
}
