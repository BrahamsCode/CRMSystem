<?php

namespace App\Models\Terminales\Beacon;

use Illuminate\Database\Eloquent\Builder;

/**
 * ビーコン (`beacon`, beacon_list.php / beacon_detail.php / ajax_beaconSave.php): uuid, major, minor, domain, shop_cd,
 * title, body, link, image_link, interval, status, start_date, end_date (NULL si la fecha no es válida).
 */
class Beacon extends BeaconModel
{
    protected $table = 'beacon';

    protected $casts = [
        'cd' => 'integer',
        'shop_cd' => 'integer',
        'flg' => 'integer',
        'interval' => 'integer',
        'status' => 'integer',
    ];

    /** Orden de beacon_list.php */
    public function scopeListOrder(Builder $query): void
    {
        $query->orderByDesc('modified_date')->orderByDesc('cd');
    }
}
