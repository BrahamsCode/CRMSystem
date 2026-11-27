<?php

use App\Http\Controllers\Admin\Nucleo\LoginController;
use App\Http\Controllers\Admin\Nucleo\SelectController;
use App\Http\Controllers\LegacyAssetController;
use App\Http\Controllers\Publico\Nucleo\HpUploadsController;
use App\Http\Middleware\ValidateStaffSession;
use App\MyApp;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

/*
 * Rutas del núcleo (S1) y carga de las rutas de los módulos. Contrato: Doc/contratos/NUCLEO.md.
 * Orden: login → sistema del personal (modulos + legacy) → publico → /hp/uploads genérico → estáticos legacy.
 */

Route::redirect('/', '/system/login.php');
Route::redirect('/system', '/system/select.php');

// Login y logout del personal, mismas URL que el legacy (logout.php es un enlace GET en la cabecera)
Route::get('system/login.php', [LoginController::class, 'show'])->name('login');
Route::post('system/login.php', [LoginController::class, 'login'])->middleware('throttle:staff-login');
Route::get('system/logout.php', [LoginController::class, 'logout'])->name('logout');

// Sistema del personal: guard admin (tb_staff) + revalidación de la sesión como session.php
Route::middleware(['auth:admin', ValidateStaffSession::class])->group(function () {
    Route::get('system/select.php', SelectController::class)->name('nucleo.select');

    // Pantallas de cada módulo: routes/modulos/<modulo>.php (prefijo admin, nombre admin.<modulo>.*)
    Route::prefix(MyApp::ADMINS_SUBDIR)->name('admin.')->group(function () {
        foreach (glob(base_path('routes/modulos/*.php')) ?: [] as $archivo) {
            require $archivo;
        }
    });

    // URL literales del legacy (/system/category<N>/…/*.php) que llaman los JS del legacy: routes/legacy/<modulo>.php
    Route::name('legacy.')->group(function () {
        foreach (glob(base_path('routes/legacy/*.php')) ?: [] as $archivo) {
            require $archivo;
        }
    });
});

// Dispositivos y contratos externos: routes/publico/<modulo>.php, sin login ni CSRF, URL literales
Route::withoutMiddleware([PreventRequestForgery::class])->group(function () {
    foreach (glob(base_path('routes/publico/*.php')) ?: [] as $archivo) {
        require $archivo;
    }
});

// Archivos subidos del legacy (Storage disco public, hp/uploads/…). Después de publico: ganan las rutas específicas
Route::get('hp/uploads/{path}', HpUploadsController::class)->where('path', '.+')->name('publico.nucleo.hp-uploads');

// Estáticos del legacy en sus URL absolutas (/system/css/…): respaldo de public/.htaccess. Siempre la última
Route::get('{path}', LegacyAssetController::class)
    ->where('path', '.+\.(' . LegacyAssetController::EXTENSIONS . ')')
    ->name('nucleo.legacy-asset');
