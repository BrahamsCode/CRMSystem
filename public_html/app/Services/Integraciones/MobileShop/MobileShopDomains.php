<?php

namespace App\Services\Integraciones\MobileShop;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Dominios de la tienda móvil (MOBILESHOP_LIST del legacy, src/MobileShop/Constant.php):
 * lista separada por comas, numerada desde 1 (el número es el prefijo de user_id).
 * Cada dominio es otro CRM: misma BD y credenciales, otro host (DbConnect::connectOtherDb);
 * el dominio «test» es la BD de pruebas del propio servidor (connectTestDb).
 */
class MobileShopDomains
{
    /** @return array<int, string> [1 => 'dominio', …], con vacíos incluidos como el legacy */
    public function list(): array
    {
        $domains = explode(',', (string) config('services.mobileshop.domain_list'));
        $list = [];
        foreach ($domains as $i => $domain) {
            $list[$i + 1] = $domain;
        }

        return $list;
    }

    /** Número del dominio de este sistema (admin_master.domain), o null si no está en la lista */
    public function systemKey(): ?int
    {
        $domain = (string) DB::table('admin_master')->where('cd', 1)->value('domain');
        $key = array_search($domain, $this->list(), true);

        return $key === false ? null : $key;
    }

    public function connection(string $domain): ConnectionInterface
    {
        $base = config('database.connections.'.config('database.default'));

        if ($domain === 'test') {
            $database = config('services.mobileshop.test_database');

            return $database ? DB::build(array_merge($base, ['database' => $database])) : DB::connection();
        }

        return DB::build(array_merge($base, ['host' => $domain]));
    }
}
