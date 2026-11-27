<?php

namespace App\Providers;

use App\MyApp;
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
        // Cada módulo registra sus observers, bindings y tareas del Scheduler
        // en su propio App\Providers\<Modulo>ServiceProvider (Doc/contratos/CONVENCIONES.md).
    }
}
