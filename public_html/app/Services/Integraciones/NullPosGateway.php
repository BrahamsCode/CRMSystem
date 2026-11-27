<?php

namespace App\Services\Integraciones;

use Illuminate\Support\Facades\Log;

/** POS fuera de alcance: no sincroniza nada, solo deja rastro en el log */
class NullPosGateway implements PosGateway
{
    public function syncCustomers(string $scope): void
    {
        Log::debug('PosGateway (integraciones) sin implementar: syncCustomers', ['scope' => $scope]);
    }

    public function setShopDomain(int $shopCd, string $domain, ?string $package): void
    {
        Log::debug('PosGateway (integraciones) sin implementar: setShopDomain', [
            'shop_cd' => $shopCd,
            'domain' => $domain,
            'package' => $package,
        ]);
    }
}
