<?php

namespace App\Models\Senalizacion;

use Illuminate\Database\Eloquent\Model;

/**
 * Base de las tablas de la BD `admin` de la señalización web (`signage/` del legacy), en la conexión
 * aparte `signage` (CONVENCIONES §BD). Clave `cd` serial, fechas `insert_date`/`update_date` y borrado
 * real (el legacy hace DELETE; no hay disp_flg).
 */
abstract class SignageWebModel extends Model
{
    public const CREATED_AT = 'insert_date';

    public const UPDATED_AT = 'update_date';

    protected $connection = 'signage';

    protected $primaryKey = 'cd';

    protected $guarded = [];
}
