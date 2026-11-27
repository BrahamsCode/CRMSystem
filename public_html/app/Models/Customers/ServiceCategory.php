<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * Categoría de servicio (施術カテゴリー). `tb_shop_cd` null = todas las tiendas.
 * Tabla del legacy `tb_sejutsu_cate` (CONVENCIONES §0).
 */
class ServiceCategory extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    protected $table = 'tb_sejutsu_cate';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
    ];
}
