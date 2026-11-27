<?php

use Illuminate\Support\Facades\Route;

/*
 * 販売管理 (S4): URL literales del legacy. S1 las carga sin prefijo de URL, con el guard del personal
 * y el prefijo de nombre `legacy.` (routes/web.php): nombres legacy.ventas.*
 * Cada bloque es independiente: añadir el propio sin tocar los demás.
 */
Route::name('ventas.')->group(function () {
    // 販売管理トップ: pestaña de la cabecera (/system/category2/) e index.php → portada nueva
    Route::get('system/category2', [\App\Http\Controllers\Admin\Ventas\HomeController::class, 'legacy'])->name('home');
    Route::get('system/category2/index.php', [\App\Http\Controllers\Admin\Ventas\HomeController::class, 'legacy'])->name('home-index');

    // 商品管理 (category2/item): pantallas → nuevas; AJAX del JS de list.php y edit.php
    Route::prefix('system/category2/item')->name('products.')->controller(\App\Http\Controllers\Admin\Ventas\ProductController::class)->group(function () {
        Route::get('list.php', 'legacyList')->name('list');
        Route::get('edit.php', 'legacyEdit')->name('edit');
        Route::match(['get', 'post'], 'ajax_edit.php', 'validateForm')->name('validate');
        // El legacy borraba por GET: solo POST (CSRF)
        Route::post('ajax_list_delete.php', 'destroy')->name('delete');
    });

    // ── Ventas internas (category1/karute_menu): las pantallas del menú de 顧客管理 (config/menu/clientes.php)
    // redirigen a las nuevas; los AJAX que llama el JS de esas pantallas conservan su URL.
    Route::prefix('system/category1/karute_menu')->group(function () {
        $sales = \App\Http\Controllers\Admin\Ventas\SaleController::class;
        $menus = \App\Http\Controllers\Admin\Ventas\ServiceMenuController::class;
        $discounts = \App\Http\Controllers\Admin\Ventas\DiscountController::class;
        $expenses = \App\Http\Controllers\Admin\Ventas\ExpenseController::class;
        $reports = \App\Http\Controllers\Admin\Ventas\SalesReportController::class;
        $options = \App\Http\Controllers\Admin\Ventas\SaleOptionController::class;

        Route::get('karute_new.php', [$sales, 'legacyCreate'])->name('karute-new');
        Route::get('karute_new_frm.php', [$sales, 'legacyForm'])->name('karute-new-frm');
        Route::get('karute_delete_list.php', [$sales, 'legacyIndex'])->name('karute-delete-list');
        Route::get('karute_menu.php', [$menus, 'legacy'])->name('karute-menu');
        Route::get('karute_waribiki.php', [$discounts, 'legacy'])->name('karute-waribiki');
        Route::get('keihi.php', [$expenses, 'legacy'])->name('keihi');
        Route::get('staff_total.php', [$reports, 'legacyStaff'])->name('staff-total');
        Route::get('report.php', [$reports, 'legacyPeriod'])->name('report');

        // AJAX ($(…).load / $.ajax del legacy: POST)
        Route::post('ajax_karute_menu_list.php', [$menus, 'list'])->name('ajax-menu-list')->can('ventas.create');
        Route::post('ajax_karute_waribiki_list.php', [$discounts, 'list'])->name('ajax-waribiki-list')->can('ventas.create');
        Route::post('ajax_search_kaiin.php', [$options, 'searchMembers'])->name('ajax-search-kaiin')->can('ventas.create');
        Route::post('ajax_staff.php', [$options, 'staffOptions'])->name('ajax-staff')->can('ventas.view');
        Route::post('ajax_staffTotal.php', [$reports, 'staffData'])->name('ajax-staff-total')->can('ventas.view');
        Route::post('ajax_report.php', [$reports, 'periodData'])->name('ajax-report')->can('ventas.view');
    });
});
