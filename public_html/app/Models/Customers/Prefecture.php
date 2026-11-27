<?php

namespace App\Models\Customers;

use App\Models\LegacyModel;

/** Prefecturas (`tb_pref`, solo lectura; 顧客情報集計 por prefectura). */
class Prefecture extends LegacyModel
{
    protected $table = 'tb_pref';

    protected $casts = ['cd' => 'integer', 'rank' => 'integer'];
}
