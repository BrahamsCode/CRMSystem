<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/*
 * Ayudas de prueba de S4 mientras S1 no entregue el layout ni el login del personal.
 * Se incluyen con require_once desde cada prueba de pantallas de S4.
 *
 * - s4UseTestLayout(): usa el layout mínimo de fixtures/views si no existe layouts.legacy.
 * - s4LoadLegacyRoutes('mypage', 'legacy.mypage.apli-file-upload'): carga routes/legacy/<modulo>.php
 *   con el middleware `web` y el prefijo de nombre `legacy.` (como routes/web.php de S1) si aún no está cargado.
 * - Las pruebas de pantallas usan withoutMiddleware() para auth:admin hasta que exista el guard.
 */
function s4UseTestLayout(): void
{
    if (! View::exists('layouts.legacy')) {
        View::addLocation(base_path('tests/Feature/Terminales/fixtures/views'));
    }
}

function s4LoadLegacyRoutes(string $modulo, string $routeName): void
{
    $file = base_path("routes/legacy/{$modulo}.php");
    if (is_file($file) && ! Route::has($routeName)) {
        Route::middleware('web')->name('legacy.')->group($file);
        app('router')->getRoutes()->refreshNameLookups();
    }
}
