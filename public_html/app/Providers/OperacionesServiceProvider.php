<?php

namespace App\Providers;

use App\Services\Operaciones\NullPosGateway;
use App\Services\Operaciones\PosGateway;
use Illuminate\Support\ServiceProvider;

/** Enlaces del módulo 業務支援管理 (operaciones) */
class OperacionesServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // POS fuera de alcance: implementación nula
        PosGateway::class => NullPosGateway::class,
    ];
}
