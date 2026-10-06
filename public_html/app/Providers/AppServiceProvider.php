<?php

namespace App\Providers;

use App\Models\Visit;
use App\MyApp;
use App\Observers\VisitObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
    }
}
