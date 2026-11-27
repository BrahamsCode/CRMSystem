<?php

/*
 * URL literales del legacy de ventas (system/category1/karute_menu/*.php, system/category2/*) (S2).
 * S1 las carga sin prefijo, con web + auth:admin. Nombre: legacy.ventas.*  (NUCLEO.md §3)
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

// --- V1-V2 ---
// Menú lateral de 顧客管理 (bloque 売上管理) y URL que llaman los JS de karute_menu (mismas pantallas que admin.ventas.*)
Route::prefix('system/category1/karute_menu')->name('ventas.')->group(function () {
    $sales = SaleController::class;
    $reports = SalesReportController::class;
    $options = OptionController::class;
    Route::get('karute_new.php', [$sales, 'create'])->name('karute-new');
    Route::get('karute_new_frm.php', [$sales, 'form'])->name('karute-new-frm');
    Route::post('karute_new_register.php', [$sales, 'store'])->name('karute-new-register')->can('ventas.create');
    Route::match(['get', 'post'], 'karute_delete_list.php', [$sales, 'index'])->name('karute-delete-list');
    Route::get('karute_menu.php', [ServiceMenuController::class, 'index'])->name('karute-menu');
    Route::get('karute_waribiki.php', [DiscountController::class, 'index'])->name('karute-waribiki');
    Route::get('keihi.php', [ExpenseController::class, 'index'])->name('keihi');
    Route::get('staff_total.php', [$reports, 'staff'])->name('staff-total-page');
    Route::get('report.php', [$reports, 'period'])->name('report-page');
    // AJAX ($(...).load / $.ajax del legacy)
    Route::match(['get', 'post'], 'ajax_karute_menu_list.php', [ServiceMenuController::class, 'list'])->name('menu-list');
    Route::match(['get', 'post'], 'ajax_karute_waribiki_list.php', [DiscountController::class, 'list'])->name('discount-list');
    Route::match(['get', 'post'], 'ajax_search_kaiin.php', [$options, 'customers'])->name('search-kaiin');
    Route::match(['get', 'post'], 'ajax_staff.php', [$options, 'staff'])->name('staff-options');
    Route::match(['get', 'post'], 'ajax_staffTotal.php', [$reports, 'staffData'])->name('staff-total');
    Route::match(['get', 'post'], 'ajax_report.php', [$reports, 'periodData'])->name('report');
});
// --- fin V1-V2 ---

// --- V3 ---
// Pestaña 販売管理 de la cabecera, menú lateral (config/menu/ventas.php) y JS de category2/item
Route::get('system/category2', [ModuleController::class, 'index'])->name('ventas.index');
Route::get('system/category2/index.php', [ModuleController::class, 'index'])->name('ventas.index-php');
Route::prefix('system/category2/item')->name('ventas.products.')->controller(ProductController::class)->group(function () {
    Route::get('list.php', 'index')->name('list');
    Route::get('edit.php', 'legacyEdit')->name('edit');
    Route::post('edit.php', 'legacySave')->name('save');
    Route::match(['get', 'post'], 'ajax_edit.php', 'validateForm')->name('validate');
    Route::post('ajax_list_delete.php', 'destroy')->name('delete');
});
// --- fin V3 ---
