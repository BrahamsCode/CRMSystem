<?php

namespace App\Providers;

use App\Services\Customers\NullPosGateway;
use App\Services\Customers\NullVisitLoyalty;
use App\Services\Customers\PosGateway;
use App\Services\Customers\VisitLoyalty;
use App\Models\ContentsMaster;
use Illuminate\Support\Facades\Gate;
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

    public function boot(): void
    {
        // cUtility::isExtShopDomain(): ポイント管理/ポイント履歴 del menú de clientes
        Gate::define('clientes.ext-shop', fn ($staff = null) => ContentsMaster::text('mycolle', 'app_layout_mode') === '1');
    }
}
