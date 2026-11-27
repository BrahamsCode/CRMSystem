<?php

namespace App\Models\Customers;

use App\Models\LegacyModel;

/** Códigos postales (tabla `zip` del legacy, solo lectura; la usa 住所検索). Sin `cd`. */
class Zip extends LegacyModel
{
    protected $table = 'zip';

    protected $primaryKey = 'zip';

    protected $keyType = 'string';
}
