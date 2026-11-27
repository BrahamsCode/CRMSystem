<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada del álbum del cliente (マイアルバム). Las fotos son archivos (`pic_flg` marca qué huecos tienen foto); `hyouji_flg` '1' = visible en Mi página.
 * Tabla del legacy `tb_photo_album` (CONVENCIONES §0).
 */
class CustomerPhoto extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_photo_album';

    protected $casts = [
        'cd' => 'integer',
        'tb_customer_cd' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }
}
