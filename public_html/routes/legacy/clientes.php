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
// 有料会員 (pestaña 14): aviso de baja a MobileShop (cliente de S5)
Route::post('system/category1/inc/ajax_changePaidMemberFlg.php', \App\Http\Controllers\Admin\Customers\PaidMemberController::class)->name('clientes.paid-member');
// --- fin C1-b ---

// --- C1-c ---
// 顧客新規登録 (menú lateral, ctiWindow.php ?tel=, karute_new_frm.php) y botón 修正 de la ficha (POST kaiin_cd)
Route::match(['get', 'post'], 'system/category1/customer/customer_new.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'create'])->name('clientes.new');
Route::match(['get', 'post'], 'system/category1/inc/customer_new.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'create'])->name('clientes.new-inc');
// 紹介者の会員検索 (inc/js/search_kaiin.js)
Route::match(['get', 'post'], 'system/category1/inc/ajax_search_kaiin.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'searchMember'])->name('clientes.search-kaiin');
// --- fin C1-c ---

// --- C2-a ---
// Menú lateral: 登録・検索項目設定 y 顧客追加情報設定 (y los enlaces guardados a la lista de campos ?pcd=)
Route::get('system/category1/customer/form_config.php', [\App\Http\Controllers\Admin\Customers\FieldSettingController::class, 'index'])->name('clientes.form-config');
Route::get('system/category1/customer/search_cate_list.php', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'index'])->name('clientes.search-cate-list');
Route::get('system/category1/customer/search_form_list.php', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'legacyList'])->name('clientes.search-form-list');
// --- fin C2-a ---

// --- C2-b ---
// Menú lateral (config/menu/clientes.php) y enlaces ?cd= del legacy
Route::get('system/category1/customer/customer_group_category_new.php', [\App\Http\Controllers\Admin\Customers\CustomerGroupController::class, 'index'])->name('clientes.groups');
Route::get('system/category1/customer/raiten_douki.php', [\App\Http\Controllers\Admin\Customers\VisitMotiveController::class, 'index'])->name('clientes.visit-motives');
Route::get('system/category1/customer/customer_level_category_new.php', [\App\Http\Controllers\Admin\Customers\CustomerLevelController::class, 'index'])->name('clientes.levels');
Route::get('system/category1/rank/customer_rank_list.php', [\App\Http\Controllers\Admin\Customers\RankController::class, 'index'])->name('clientes.ranks');
Route::get('system/category1/rank/customer_rank_schedule.php', [\App\Http\Controllers\Admin\Customers\RankScheduleController::class, 'edit'])->name('clientes.rank-schedules');
// JS de customer_rank_list.php: orden (type=rank) y casilla 表示 (type=hyouji)
Route::post('system/category1/rank/ajax_customer_rank_list.php', [\App\Http\Controllers\Admin\Customers\RankController::class, 'legacyAjax'])->name('clientes.ranks-ajax');
// --- fin C2-b ---

// --- C3 ---
// 来店処理 (menú lateral)
Route::get('system/category1/customer/customer_search.php', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'index'])->name('clientes.visits');
Route::post('system/category1/customer/customer_search.php', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'store'])->name('clientes.visits.store');
// Pestaña 来店履歴: alta manual / borrado ($.get de raiten_submitChk/raiten_delete)
Route::match(['get', 'post'], 'system/category1/inc/raiten_register.php', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'legacyRegister'])->name('clientes.raiten-register');
// 来店履歴の追加・修正: lista de visitas (inc/js/raiten_list.js) y selectores de fecha
Route::match(['get', 'post'], 'system/category1/inc/ajax_raiten_list.php', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'raitenList'])->name('clientes.raiten-list');
Route::match(['get', 'post'], 'system/category1/inc/ajax_raiten_time_select.php', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'timeSelect'])->name('clientes.raiten-time-select');
Route::match(['get', 'post'], 'system/category1/inc/ajax_shop_point_config.php', [\App\Http\Controllers\Admin\Customers\VisitRecordController::class, 'shopPointConfig'])->name('clientes.shop-point-config');
Route::match(['get', 'post'], 'system/category1/inc/ajax_shop_stamp_config.php', [\App\Http\Controllers\Admin\Customers\VisitRecordController::class, 'shopStampConfig'])->name('clientes.shop-stamp-config');
// Pestañas ポイント y スタンプ: $.get de point_submitChk, stamp_submitChk2 y stamp_submitChk
Route::match(['get', 'post'], 'system/category1/inc/point_register.php', [\App\Http\Controllers\Admin\Customers\CustomerLoyaltyController::class, 'points'])->name('clientes.point-register');
Route::match(['get', 'post'], 'system/category1/inc/stamp_register.php', [\App\Http\Controllers\Admin\Customers\CustomerLoyaltyController::class, 'stamps'])->name('clientes.stamp-register');
Route::match(['get', 'post'], 'system/category1/inc/stamp_use_register.php', [\App\Http\Controllers\Admin\Customers\CustomerLoyaltyController::class, 'useStamps'])->name('clientes.stamp-use-register');
// ポイント利用一覧 (menú lateral; el CSV era un POST csv_print=1 a la misma URL)
Route::match(['get', 'post'], 'system/category1/customer/point_history.php', [\App\Http\Controllers\Admin\Customers\PointHistoryController::class, 'index'])->name('clientes.point-history');
// --- fin C3 ---
