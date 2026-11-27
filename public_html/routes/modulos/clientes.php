<?php

/*
 * 顧客管理 (S2). Se carga dentro del grupo admin (prefijo /admin, auth:admin, nombre 'admin.').
 * Ver Doc/diseno/clientes-ventas-reservas.md §1.1.
 */

use Illuminate\Support\Facades\Route;

Route::prefix('clientes')->name('clientes.')->group(function () {
    // --- C1-a: 顧客一覧・検索, CSV ---
    Route::get('/', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'index'])->name('index');
    Route::match(['get', 'post'], 'search', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'search'])->name('search');
    Route::get('export', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'export'])->name('export');
    // --- fin C1-a ---

    // --- C1-c: alta, edición, confirmación ---
    Route::get('create', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'create'])->name('create');
    Route::post('confirm', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'confirm'])->name('confirm');
    Route::post('rewrite', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'rewrite'])->name('rewrite');
    Route::post('/', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'store'])->name('store');
    Route::get('{customer}/edit', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'edit'])->whereNumber('customer')->name('edit');
    Route::put('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'update'])->whereNumber('customer')->name('update');
    // --- fin C1-c ---

    // --- C1-b: ficha y pestañas, borrar, recuperar (al final: captura /{customer}) ---
    Route::get('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'show'])->whereNumber('customer')->name('show');
    Route::delete('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
    Route::post('{customer}/restore', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'restore'])->whereNumber('customer')->name('restore');
    Route::delete('{customer}/felica', [\App\Http\Controllers\Admin\Customers\CustomerFelicaController::class, 'destroy'])->whereNumber('customer')->name('felica.destroy');
    Route::delete('{customer}/coupons', [\App\Http\Controllers\Admin\Customers\CustomerCouponController::class, 'destroy'])->whereNumber('customer')->name('coupons.destroy');
    // --- fin C1-b ---
});
