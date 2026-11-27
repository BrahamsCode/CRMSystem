<?php

namespace App\Models\Terminales\Beacon;

use Illuminate\Database\Eloquent\Builder;

/**
 * 受信端末 (`terminal` de la BD de beacon, terminal_list.php / terminal_detail.php / ajax_terminalSave.php):
 * uuid, major, minor, domain, shop_cd, name. El legacy solo muestra los de la tienda de la sesión.
 */
class BeaconReceiver extends BeaconModel
{
    protected $table = 'terminal';

    public function scopeForShop(Builder $query, int $shopCd): void
    {
        $query->where('shop_cd', $shopCd);
    }

    /** Orden de terminal_list.php */
    public function scopeListOrder(Builder $query): void
    {
        $query->orderByDesc('cd');
    }
}
