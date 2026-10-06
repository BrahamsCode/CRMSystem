<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Visit;
use App\MyApp;
use App\Observers\LoyaltyObserver;
use App\Observers\VisitObserver;
use App\Services\Promotions\Push\LogPushSender;
use App\Services\Promotions\Push\PushSender;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Notificaciones push: al log hasta que exista la app de Mi página
        $this->app->bind(PushSender::class, LogPushSender::class);

        $requestUri = $this->app->request->getRequestUri();
        Request::macro('routeType', function () use ($requestUri) {
            if (preg_match("#^/" . MyApp::ADMINS_SUBDIR . "/#", $requestUri)) {
                return MyApp::ADMINS_SUBDIR;
            
            } else {
                return null;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Recalcula las estadísticas del cliente al crear, editar o borrar visitas
        Visit::observe(VisitObserver::class);

        // Sellos, puntos y cupones de alta (módulo de promociones). Va después de
        // VisitObserver para que la visita ya haya actualizado al cliente.
        Visit::observe(LoyaltyObserver::class);
        Customer::observe(LoyaltyObserver::class);
    }
}
