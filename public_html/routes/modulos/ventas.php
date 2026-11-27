<?php

use Illuminate\Support\Facades\Route;

/*
 * 販売管理 (S4). Se carga dentro del grupo admin: URL /admin/ventas/..., nombre admin.ventas.*
 * Cada bloque es independiente (productos de category2, ventas internas…): añadir el propio sin tocar los demás.
 */
Route::prefix('ventas')->name('ventas.')->group(function () {
    // 販売管理トップ (category2/index.php)
    Route::get('/', [\App\Http\Controllers\Admin\Ventas\HomeController::class, 'index'])->name('home');

    // 商品管理 (category2/item/*.php). Las URL del legacy que llama el JS están en routes/legacy/ventas.php
    Route::prefix('products')->name('products.')->controller(\App\Http\Controllers\Admin\Ventas\ProductController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::match(['get', 'post'], 'validate', 'validateForm')->name('validate');
        Route::get('{cd}/edit', 'edit')->whereNumber('cd')->name('edit');
        Route::post('{cd}', 'update')->whereNumber('cd')->name('update');
        Route::delete('{cd}', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // ── Ventas internas (category1/karute_menu). Permisos: kengen 1033 (ventas.view|create|update|delete),
    // como los enlaces de left_navi1. Las URL del legacy y los AJAX están en routes/legacy/ventas.php
    // 売上データ作成 / 売上データ削除
    Route::prefix('sales')->name('sales.')->controller(\App\Http\Controllers\Admin\Ventas\SaleController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index')->can('ventas.delete');
        Route::post('destroy', 'destroy')->name('destroy')->can('ventas.delete');
        Route::get('create', 'create')->name('create')->can('ventas.create');
        Route::get('form', 'form')->name('form')->can('ventas.create');
        Route::post('store', 'store')->name('store')->can('ventas.create');
    });

    // メニュー管理 / 割引管理 (alta y edición: create/update según act, dentro del controlador)
    Route::prefix('menus')->name('menus.')->controller(\App\Http\Controllers\Admin\Ventas\ServiceMenuController::class)->group(function () {
        Route::get('/', 'index')->name('index')->can('ventas.create');
        Route::post('store', 'store')->name('store');
        Route::post('destroy', 'destroy')->name('destroy')->can('ventas.delete');
    });
    Route::prefix('discounts')->name('discounts.')->controller(\App\Http\Controllers\Admin\Ventas\DiscountController::class)->group(function () {
        Route::get('/', 'index')->name('index')->can('ventas.create');
        Route::post('store', 'store')->name('store');
        Route::post('destroy', 'destroy')->name('destroy')->can('ventas.delete');
    });

    // 経費管理
    Route::prefix('expenses')->name('expenses.')->controller(\App\Http\Controllers\Admin\Ventas\ExpenseController::class)->group(function () {
        Route::get('/', 'index')->name('index')->can('ventas.create');
        Route::post('store', 'store')->name('store');
        Route::post('destroy', 'destroy')->name('destroy')->can('ventas.delete');
    });

    // スタッフ別集計 / 日報・週報・月報 (+ CSV SJIS-win)
    Route::prefix('reports')->name('reports.')->controller(\App\Http\Controllers\Admin\Ventas\SalesReportController::class)->group(function () {
        Route::get('staff', 'staff')->name('staff')->can('ventas.view');
        Route::get('staff/csv', 'staffCsv')->name('staff.csv')->can('ventas.view');
        Route::get('period', 'period')->name('period')->can('ventas.view');
        Route::get('period/csv', 'periodCsv')->name('period.csv')->can('ventas.view');
    });
});
