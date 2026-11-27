<?php

namespace App\Providers;

use App\Services\Integraciones\NullPosGateway;
use App\Services\Integraciones\PosGateway;
use Illuminate\Support\ServiceProvider;

/** Enlaces del módulo de integraciones externas */
class IntegracionesServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // POS fuera de alcance: implementación nula
        PosGateway::class => NullPosGateway::class,
    ];
}
