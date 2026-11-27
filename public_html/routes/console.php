<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Las tareas programadas las registra cada módulo en App\Providers\<Modulo>ServiceProvider (Doc/contratos/CONVENCIONES.md).
