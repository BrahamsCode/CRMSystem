<?php

/*
 * URL literales del legacy que llaman los JS de 顧客管理 (S2). S1 las carga sin prefijo, con web + auth:admin.
 * Nombre de ruta: legacy.clientes.*  (NUCLEO.md §3)
 */

use Illuminate\Support\Facades\Route;

// --- C1-a ---
// 顧客一覧・検索: menú, enlaces de otras pantallas (ctiWindow.php con act=up, customer_new_complete.php…)
Route::get('system/category1/customer/customer_list.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'index'])->name('clientes.list');
Route::post('system/category1/customer/customer_list.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'search'])->name('clientes.list.search');
// CSV (en el legacy, formularios POST de customer_list.php)
Route::match(['get', 'post'], 'system/category1/customer/csv_download.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'export'])
    ->defaults('format', 'standard')->name('clientes.csv');
Route::match(['get', 'post'], 'system/category1/customer/csv_download_ex02.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'export'])
    ->defaults('format', 'ex02')->name('clientes.csv-ex02');
// --- fin C1-a ---

// --- C1-b ---
// Ficha 顧客登録情報 enlazada desde otras pantallas: con menú (customer/) y ventana emergente (inc/)
Route::match(['get', 'post'], 'system/category1/customer/customer_detail.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'legacyDetail'])->name('clientes.detail');
Route::match(['get', 'post'], 'system/category1/inc/customer_detail.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'legacyDetailPopup'])->name('clientes.detail-popup');
// Pestaña 購買履歴: AJAX al POS (fuera de alcance: respuestas vacías vía PosGateway)
Route::match(['get', 'post'], 'system/category1/inc/ajax_connectPosData.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'posPurchaseHistory'])->name('clientes.pos-history');
Route::match(['get', 'post'], 'system/category1/inc/ajax_connectPosKoubaiData.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'posPurchaseDetail'])->name('clientes.pos-detail');
Route::match(['get', 'post'], 'system/category1/inc/ajax_sendPosKoubaiMemoData.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'posPurchaseMemo'])->name('clientes.pos-memo');
// --- fin C1-b ---

// --- C1-c ---
// 顧客新規登録 (menú lateral, ctiWindow.php ?tel=, karute_new_frm.php) y botón 修正 de la ficha (POST kaiin_cd)
Route::match(['get', 'post'], 'system/category1/customer/customer_new.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'create'])->name('clientes.new');
Route::match(['get', 'post'], 'system/category1/inc/customer_new.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'create'])->name('clientes.new-inc');
// 紹介者の会員検索 (inc/js/search_kaiin.js)
Route::match(['get', 'post'], 'system/category1/inc/ajax_search_kaiin.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'searchMember'])->name('clientes.search-kaiin');
// --- fin C1-c ---
