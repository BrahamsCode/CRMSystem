<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Cada módulo registra sus observers, bindings y tareas del Scheduler
        // en su propio App\Providers\<Modulo>ServiceProvider (Doc/contratos/CONVENCIONES.md).
        // El núcleo (login, permisos, UI legacy) está en NucleoServiceProvider.
    }
}
