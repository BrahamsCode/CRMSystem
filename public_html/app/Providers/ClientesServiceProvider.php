<?php

namespace App\Providers;

use App\Services\Customers\NullPosGateway;
use App\Services\Customers\NullVisitLoyalty;
use App\Services\Customers\PosGateway;
use App\Services\Customers\VisitLoyalty;
use Illuminate\Support\ServiceProvider;

/**
 * Módulos de S2 (clientes, ventas, reservas): enlaces de interfaces y, más adelante, tareas del Scheduler.
 */
class ClientesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // POS fuera de alcance (CONVENCIONES §4)
        $this->app->bindIf(PosGateway::class, NullPosGateway::class);

        // Sellos y puntos de la visita: S3 enlaza su implementación; hasta entonces, nula
        $this->app->bindIf(VisitLoyalty::class, NullVisitLoyalty::class);
    }
}
