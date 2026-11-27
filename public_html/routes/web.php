<?php

use App\MyApp;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Sistema del personal: cada módulo define sus rutas en routes/modulos/<modulo>.php
 * (Doc/contratos/CONVENCIONES.md). El login contra el personal del legacy lo hace S1 (núcleo).
 */
Route::prefix(MyApp::ADMINS_SUBDIR)->middleware('auth:admin')->name('admin.')->group(function () {
    foreach (glob(base_path('routes/modulos/*.php')) ?: [] as $archivo) {
        require $archivo;
    }
});

/*
 * Rutas públicas o de dispositivos (sin el grupo admin): routes/publico/<modulo>.php
 */
foreach (glob(base_path('routes/publico/*.php')) ?: [] as $archivo) {
    require $archivo;
}
