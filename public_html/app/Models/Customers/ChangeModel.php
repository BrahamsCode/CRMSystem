<?php

namespace App\Models\Customers;

use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reserva de cambio de modelo de móvil (予約機種一覧; solo lectura: nadie del repositorio la escribe).
 * Tabla del legacy `tb_change_model` (CONVENCIONES §0).
 */
class ChangeModel extends LegacyModel
{
    use LegacySoftDeletes;

    protected $table = 'tb_change_model';

    protected $casts = [
        'cd' => 'integer',
    ];

    /** `tb_customer_cd` es texto en esta tabla */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }
}
