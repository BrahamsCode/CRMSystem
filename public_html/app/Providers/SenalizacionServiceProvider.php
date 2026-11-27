<?php

namespace App\Providers;

use App\Models\Staff;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Señalización web (S4, `signage/` del legacy).
 */
class SenalizacionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * El legacy entraba con `?s=md5(email01_pass)` de la tabla `shop` de la BD `admin` (una cuenta
         * aparte, sin relación con el personal del CRM). En Laravel la gestión va dentro del admin y
         * queda solo para el usuario master: es la configuración del sistema de cartelería de todo el
         * cliente, no de una tienda, y ningún menú del CRM enlazaba a ella.
         */
        Gate::define('senalizacion.manage', fn (Staff $staff) => $staff->isMaster());
    }
}
