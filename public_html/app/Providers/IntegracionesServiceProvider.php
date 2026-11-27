<?php

namespace App\Providers;

use App\Services\Integraciones\MobileShop\HttpMobileShopClient;
use App\Services\Integraciones\MobileShop\MobileShopClient;
use App\Services\Integraciones\NullPosGateway;
use App\Services\Integraciones\PosGateway;
use Illuminate\Support\ServiceProvider;

/** Enlaces del módulo de integraciones externas */
class IntegracionesServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // POS fuera de alcance: implementación nula
        PosGateway::class => NullPosGateway::class,
        // API extlink de la tienda móvil (有料会員); la usa también S2
        MobileShopClient::class => HttpMobileShopClient::class,
    ];
}
