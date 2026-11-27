<?php

/*
 * 販売管理 y ventas internas (S2). Se carga dentro del grupo admin (prefijo /admin, auth:admin, nombre 'admin.').
 * Ver Doc/diseno/clientes-ventas-reservas.md §1.1 (karute_menu) y §1.2 (category2).
 */

use App\Http\Controllers\Admin\Sales\DiscountController;
use App\Http\Controllers\Admin\Sales\ExpenseController;
use App\Http\Controllers\Admin\Sales\ModuleController;
use App\Http\Controllers\Admin\Sales\OptionController;
use App\Http\Controllers\Admin\Sales\ProductController;
use App\Http\Controllers\Admin\Sales\SaleController;
use App\Http\Controllers\Admin\Sales\SalesReportController;
use App\Http\Controllers\Admin\Sales\ServiceMenuController;
use Illuminate\Support\Facades\Route;

Route::prefix('ventas')->name('ventas.')->group(function () {
    // --- V1-V2: ventas internas (karute_menu) ---
    // 売上データ作成 / 削除 (karute_new*.php, karute_delete_*.php)
    Route::controller(SaleController::class)->prefix('sales')->name('sales.')->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('form', 'form')->name('form');
        Route::post('store', 'store')->name('store')->can('ventas.create');
        Route::post('delete', 'destroy')->name('destroy')->can('ventas.delete');
    });
    // メニュー管理 / 割引管理 (karute_menu.php, karute_waribiki.php y sus *_register.php)
    foreach (['menus' => ServiceMenuController::class, 'discounts' => DiscountController::class] as $prefix => $controller) {
        Route::controller($controller)->prefix($prefix)->name($prefix.'.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store')->can('ventas.create');
            Route::post('list', 'list')->name('list');
            Route::post('{cd}', 'update')->whereNumber('cd')->name('update')->can('ventas.update');
            Route::post('{cd}/delete', 'destroy')->whereNumber('cd')->name('destroy')->can('ventas.delete');
        });
    }
    // 経費管理 (keihi.php, keihi_register.php, keihi_delete.php)
    Route::controller(ExpenseController::class)->prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->can('ventas.create');
        Route::post('{cd}', 'update')->whereNumber('cd')->name('update')->can('ventas.update');
        Route::post('{cd}/delete', 'destroy')->whereNumber('cd')->name('destroy')->can('ventas.delete');
    });
    // スタッフ別集計 / 日報・週報・月報 (staff_total.php, report.php y sus ajax_*.php)
    Route::controller(SalesReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('staff', 'staff')->name('staff');
        Route::post('staff/data', 'staffData')->name('staff.data');
        Route::get('period', 'period')->name('period');
        Route::post('period/data', 'periodData')->name('period.data');
    });
    // AJAX de apoyo (ajax_search_kaiin.php, ajax_staff.php)
    Route::controller(OptionController::class)->prefix('options')->name('options.')->group(function () {
        Route::post('customers', 'customers')->name('customers');
        Route::post('staff', 'staff')->name('staff');
    });
    // --- fin V1-V2 ---

    // --- V3: productos (category2/item) ---
    Route::get('/', [ModuleController::class, 'index'])->name('home');
    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::match(['get', 'post'], 'validate', 'validateForm')->name('validate');
        Route::post('delete', 'destroy')->name('destroy');
        Route::get('{cd}/edit', 'edit')->whereNumber('cd')->name('edit');
        Route::post('{cd}', 'update')->whereNumber('cd')->name('update');
    });
    // --- fin V3 ---
});
