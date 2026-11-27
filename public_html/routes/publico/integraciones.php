<?php

use App\Http\Controllers\Publico\Integraciones\CtiController;
use App\Http\Controllers\Publico\Integraciones\MobileShopController;
use App\Http\Controllers\Publico\Integraciones\ToolApi;
use App\Http\Middleware\Integraciones\CheckCrmApiKey;
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
                Route::any('point_use', [MobileShopController::class, 'pointUse'])->name('point-use');
                Route::any('point_exchange_item', [MobileShopController::class, 'pointExchangeItem'])->name('point-exchange');
            });

            // Cualquier otra ruta /api/* (salvo los mpa_*.php): 404 JSON (debe quedar al final del grupo api)
            Route::any('{any}', [MobileShopController::class, 'notFound'])
                ->where('any', '(?!mpa_).*')
                ->name('not-found');
        });

        // tool/api del legacy (aplicación de escritorio): clave `key` = CRM_API_KEY (checkKey.php)
        Route::prefix('system/tool/api')->name('tool.')->middleware(CheckCrmApiKey::class)->group(function () {
            Route::any('category1/searchCustomerList.php', [ToolApi\CustomerController::class, 'search'])->name('search-customer-list');
            Route::any('category1/getCustomerDetail.php', [ToolApi\CustomerController::class, 'show'])->name('customer-detail');
            Route::any('category1/getSearchCateFormData.php', [ToolApi\CustomerController::class, 'searchForm'])->name('search-form');
            Route::any('category1/getRaitenList.php', [ToolApi\RaitenController::class, 'index'])->name('raiten-list');
            Route::any('category1/getRaitenDetail.php', [ToolApi\RaitenController::class, 'show'])->name('raiten-detail');
            Route::any('category1/addRaitenRecord.php', [ToolApi\RaitenController::class, 'store'])->name('raiten-add');
            Route::any('category5/getStaffList.php', [ToolApi\StaffController::class, 'index'])->name('staff-list');
        });

        // Ventana CTI (system/desktop_request): sin autenticación, como el legacy
        Route::prefix('system/desktop_request')->name('cti.')->group(function () {
            Route::any('ctiWindow.php', [CtiController::class, 'window'])->name('window');
            Route::any('customerDetailHtml.php', [CtiController::class, 'customerHtml'])->name('customer-html');
            Route::any('jGetCustomerData.php', [CtiController::class, 'customerJson'])->name('customer-json');
        });
    });
