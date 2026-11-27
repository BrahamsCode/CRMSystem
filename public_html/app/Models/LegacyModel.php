<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base de los modelos sobre tablas del legacy (`tb_*`, `admin_master`…): clave `cd`
 * no autoincremental (se numera con `hatuban`: trait HasHatuban), sin `created_at`/`updated_at`
 * (las fechas del legacy van con el trait LegacyTimestamps) y columnas con su nombre original.
 */
abstract class LegacyModel extends Model
{
    protected $primaryKey = 'cd';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}
