<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * 勤怠一覧/集計 (category5/working) hecho por S2 dentro del módulo `operaciones` de S5.
 * Separado de los proveedores de S5 para no tocar archivos de otra sesión.
 */
class OperacionesWorkingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 帳票管理: en lugar de `SERVER_ADDR == 202.218.51.245` (termina en .enabled: el master no lo salta)
        Gate::define('operaciones.working-report.enabled', fn ($staff = null) => (bool) config('working.report_enabled'));
    }
}
