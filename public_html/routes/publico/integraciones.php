<?php

use App\Http\Controllers\Publico\Integraciones\Appview;
use App\Http\Controllers\Publico\Integraciones\CtiController;
use App\Http\Controllers\Publico\Integraciones\MobileShopController;
use App\Http\Controllers\Publico\Integraciones\MpaController;
use App\Http\Controllers\Publico\Integraciones\ToolApi;
use App\Http\Controllers\Publico\Integraciones\UtilityController;
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

            // API de la app マイページ (api/mpa_*.php), sin autenticación como el legacy
            Route::any('mpa_corresponding.php', [MpaController::class, 'corresponding'])->name('mpa.corresponding');
            Route::any('mpa_getTopBanner.php', [MpaController::class, 'topBanner'])->name('mpa.top-banner');
            Route::any('mpa_getCoverFlow.php', [MpaController::class, 'coverFlow'])->name('mpa.cover-flow');
            Route::any('mpa_setraiten.php', [MpaController::class, 'setRaiten'])->name('mpa.set-raiten');
            Route::any('mpa_albumUpdate.php', [MpaController::class, 'albumUpdate'])->name('mpa.album-update');

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
            Route::any('category1/setRaitenDetail.php', [ToolApi\RaitenController::class, 'update'])->name('raiten-set');
            Route::any('category5/getStaffList.php', [ToolApi\StaffController::class, 'index'])->name('staff-list');
        });

        // Utilitarios (§1.10): versión, enlaces de seguimiento y cliente Adobe AIR (los .xml de /air son estáticos en public/air)
        Route::any('version/crm_version.php', [UtilityController::class, 'crmVersion'])->name('version.crm');
        Route::any('ptag/{id?}', [UtilityController::class, 'ptag'])->where('id', '.*')->name('ptag');
        Route::any('air/air_info_out.php', [UtilityController::class, 'airInfo'])->name('air.info');

        // WebView de la app del socio (appview/*.php, §1.8): sin autenticación, como el legacy; estáticos en public/appview
        Route::prefix('appview')->name('appview.')->group(function () {
            Route::any('memberId.php', [Appview\MemberController::class, 'memberId'])->name('member-id');
            Route::any('qr_code.php', [Appview\MemberController::class, 'qrCode'])->name('qr-code');
            Route::any('member_init.php', [Appview\MemberController::class, 'init'])->name('member-init');
            Route::any('member_comp.php', [Appview\MemberController::class, 'link'])->name('member-comp');
            Route::any('member_login.php', [Appview\MemberController::class, 'login'])->name('member-login');
            Route::any('photo_gallery.php', [Appview\MemberController::class, 'photoGallery'])->name('photo-gallery');
            Route::any('visit_history.php', [Appview\MemberController::class, 'visitHistory'])->name('visit-history');
            Route::any('member_kiyaku.php', [Appview\MemberRegistrationController::class, 'terms'])->name('member-kiyaku');
            Route::any('member_comfirm.php', [Appview\MemberRegistrationController::class, 'confirm'])->name('member-comfirm');
            Route::any('member_complete.php', [Appview\MemberRegistrationController::class, 'store'])->name('member-complete');
            Route::any('privacyPage.php', [Appview\PageController::class, 'privacy'])->name('privacy');
            Route::any('coupon.php', [Appview\CouponController::class, 'index'])->name('coupon');
            Route::any('coupon_detail.php', [Appview\CouponController::class, 'show'])->name('coupon-detail');
            Route::any('coupon_control.php', [Appview\CouponController::class, 'use'])->name('coupon-control');
            Route::any('coupon_use.php', [Appview\CouponController::class, 'used'])->name('coupon-use');
            Route::any('used_coupon_list.php', [Appview\CouponController::class, 'usedList'])->name('used-coupons');
            Route::any('stamp.php', [Appview\StampController::class, 'show'])->name('stamp');
            Route::any('anketo2.php', [Appview\SurveyController::class, 'show'])->name('anketo2');
        });

        // Ventana CTI (system/desktop_request): sin autenticación, como el legacy
        Route::prefix('system/desktop_request')->name('cti.')->group(function () {
            Route::any('ctiWindow.php', [CtiController::class, 'window'])->name('window');
            Route::any('customerDetailHtml.php', [CtiController::class, 'customerHtml'])->name('customer-html');
            Route::any('jGetCustomerData.php', [CtiController::class, 'customerJson'])->name('customer-json');
        });
    });
