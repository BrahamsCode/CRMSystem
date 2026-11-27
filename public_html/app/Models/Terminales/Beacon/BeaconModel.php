<?php

namespace App\Models\Terminales\Beacon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base de ビーコン管理 (category7/beacon, PROVISIONAL: en pausa hasta que Japón confirme). Tablas de la BD externa
 * de g-api (`g_api_b_develop` en beacon_db.php) en la conexión `beacon`, que configura S1. Sin pantallas todavía.
 * Clave `cd` (nextval('<tabla>_cd_seq')), fechas `registration_date`/`modified_date`, filas activas con flg = 1
 * y separadas por dominio del CRM (`domain` = HTTP_HOST).
 */
abstract class BeaconModel extends Model
{
    public const CREATED_AT = 'registration_date';

    public const UPDATED_AT = 'modified_date';

    protected $connection = 'beacon';

    protected $primaryKey = 'cd';

    protected $guarded = ['cd'];

    protected $casts = [
        'cd' => 'integer',
        'shop_cd' => 'integer',
        'flg' => 'integer',
    ];

    protected $attributes = ['flg' => 1];

    /** UUID en minúsculas y major/minor a 4 cifras, como ajax_*Save.php */
    public function setUuidAttribute(?string $value): void
    {
        $this->attributes['uuid'] = strtolower((string) $value);
    }

    public function setMajorAttribute(mixed $value): void
    {
        $this->attributes['major'] = sprintf('%04s', $value);
    }

    public function setMinorAttribute(mixed $value): void
    {
        $this->attributes['minor'] = sprintf('%04s', $value);
    }

    /** flg = 1 del dominio actual (el legacy filtra siempre por HTTP_HOST) */
    public function scopeActiveFor(Builder $query, string $domain): void
    {
        $query->where('flg', 1)->where('domain', $domain);
    }
}
