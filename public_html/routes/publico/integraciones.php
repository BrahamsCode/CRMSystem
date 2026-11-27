<?php

use App\Http\Controllers\Publico\Integraciones\MobileShopController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
 * Integraciones externas (S5): URL literales del legacy, fuera del grupo admin y sin CSRF
 * (los clientes son apps y servidores externos). Doc/diseno/operaciones-integraciones.md §1.6-§1.10.
 */
Route::name('publico.integraciones.')
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->group(function () {
        // API JWT de la tienda móvil (api/.htaccess → api/index.php). Los GET llevan cuerpo JSON.
        Route::prefix('api')->name('api.')->group(function () {
            Route::any('auth', [MobileShopController::class, 'auth'])->name('auth');
            Route::any('shop', [MobileShopController::class, 'shop'])->name('shop');
            Route::any('customer', [MobileShopController::class, 'customer'])->name('customer');
            Route::any('point', [MobileShopController::class, 'point'])->name('point');

            Route::prefix('mobile_shop')->name('ms.')->group(function () {
                Route::any('customer/{parameter?}', [MobileShopController::class, 'mobileShopCustomer'])->name('customer');
                Route::any('passcode', [MobileShopController::class, 'passcode'])->name('passcode');
                Route::any('point', [MobileShopController::class, 'mobileShopPoint'])->name('point');
                Route::any('sales_items/{parameter?}', [MobileShopController::class, 'salesItems'])->name('sales-items');
                Route::any('fetch_paid_member', [MobileShopController::class, 'fetchPaidMember'])->name('fetch-paid-member');
                Route::any('send_unpaid_member_status', [MobileShopController::class, 'sendUnpaidMemberStatus'])->name('send-unpaid');
                // point_use y point_exchange_item: cuando S3 publique el servicio de puntos (setPoint)
            });

            // Cualquier otra ruta /api/* (salvo los mpa_*.php): 404 JSON
            Route::any('{any}', [MobileShopController::class, 'notFound'])
                ->where('any', '(?!mpa_).*')
                ->name('not-found');
        });
    });
