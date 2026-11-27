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

// --- C4-a ---
// $("#tb_sejutsu_cate_cd").load("ajax_category.php") del formulario カルテ (inc/ y customer/) y ajax_staff.php de customer/
Route::match(['get', 'post'], 'system/category1/inc/ajax_category.php', [\App\Http\Controllers\Admin\Customers\OptionController::class, 'categories'])->name('clientes.ajax-category-inc');
Route::match(['get', 'post'], 'system/category1/customer/ajax_category.php', [\App\Http\Controllers\Admin\Customers\OptionController::class, 'categories'])->name('clientes.ajax-category');
Route::match(['get', 'post'], 'system/category1/customer/ajax_staff.php', [\App\Http\Controllers\Admin\Customers\OptionController::class, 'staff'])->name('clientes.ajax-staff');
// 顧客検索 (selector de cliente de la カルテ; el form_search se envía a la misma URL)
Route::match(['get', 'post'], 'system/category1/customer/karute_customer_search.php', [\App\Http\Controllers\Admin\Customers\CustomerLookupController::class, 'index'])->name('clientes.karute-customer-search');
// --- fin C4-a ---

// --- C4-b ---
// 美容 (sin enlace en el menú): URL guardadas de 施術カテゴリー (?cd= edita) y カルテ詳細 (?cd=)
Route::get('system/category1/biyou/sejutsu_cate.php', [\App\Http\Controllers\Admin\Customers\ServiceCategoryController::class, 'index'])->name('clientes.sejutsu-cate');
Route::get('system/category1/biyou/karute_detail.php', [\App\Http\Controllers\Admin\Customers\KarteController::class, 'legacyShow'])->name('clientes.karute-detail');
// --- fin C4-b ---

// --- C4-c ---
// マイアルバム: subida AjaxUpload de inc/js/pic_upload.js (POST ?up=true) y su miniatura (GET ?f=)
Route::match(['get', 'post'], 'system/category1/inc/ajax_picupload.php', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'upload'])->name('clientes.picupload');
// Temporales de la subida, en su URL legacy (disco public de Storage)
Route::get('system/category1/customer/uploads/{file}', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'tempImage'])->where('file', '[A-Za-z0-9_.-]+')->name('clientes.picupload-temp');
// --- fin C4-c ---

// --- C5 ---
// CSVインポート (menú lateral) y su plantilla (POST del botón テンプレートファイルをダウンロード)
Route::get('system/category1/customer/csv_import.php', [\App\Http\Controllers\Admin\Customers\CustomerImportController::class, 'create'])->name('clientes.import');
Route::post('system/category1/customer/csv_import.php', [\App\Http\Controllers\Admin\Customers\CustomerImportController::class, 'store'])->name('clientes.import.store');
Route::match(['get', 'post'], 'system/category1/customer/csv_download_template.php', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'export'])
    ->defaults('format', 'template')->name('clientes.csv-template');
// CSVエクスポート (URL guardadas; el formulario hace POST a la misma URL)
Route::get('system/category1/customer/csv_export.php', [\App\Http\Controllers\Admin\Customers\TableExportController::class, 'index'])->name('clientes.table-export');
Route::post('system/category1/customer/csv_export.php', [\App\Http\Controllers\Admin\Customers\TableExportController::class, 'export'])->name('clientes.table-export.download');
// --- fin C5 ---

// --- C6 ---
// --- C6-a: 集計ツール (shukei_list, model1/2, point1/2, stamp1) ---
// 集計ツール (menú lateral 顧客情報集計) y sus ventanas (window.open con tb_shukei_link.url; formulario GET a la misma URL)
Route::get('system/category1/shukei/shukei_list.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'index'])->can('clientes.view')->name('clientes.reports');
Route::get('system/category1/shukei/model1.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'model1'])->can('clientes.view')->name('clientes.reports.model1');
Route::get('system/category1/shukei/model2.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'model2'])->can('clientes.view')->name('clientes.reports.model2');
Route::get('system/category1/shukei/point1.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'point1'])->can('clientes.view')->name('clientes.reports.point1');
Route::get('system/category1/shukei/point2.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'point2'])->can('clientes.view')->name('clientes.reports.point2');
Route::get('system/category1/shukei/stamp1.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'stamp1'])->can('clientes.view')->name('clientes.reports.stamp1');
// $.ajax de sendMailConfirm() en model1: guarda el filtro de メール y devuelve el search_uniqid
Route::match(['get', 'post'], 'system/category1/shukei/model1_select.php', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'model1Select'])->can('clientes.view')->name('clientes.reports.model1-select');
// --- fin C6-a ---
// --- C6-b: 顧客情報集計 (customer_sumup_list) ---
// 顧客情報集計: window.open desde 集計ツール (tb_shukei_link.url); list2 era la versión vieja (Flash) y abre la misma pantalla
Route::match(['get', 'post'], 'system/category1/customer/customer_sumup_list.php', [\App\Http\Controllers\Admin\Customers\StatisticController::class, 'index'])->can('clientes.view')->name('clientes.sumup');
Route::match(['get', 'post'], 'system/category1/customer/customer_sumup_list2.php', [\App\Http\Controllers\Admin\Customers\StatisticController::class, 'index'])->can('clientes.view')->name('clientes.sumup2');
// --- fin C6-b ---
// --- C6-c: csv出力管理画面 (customerDataOutPut) ---
// csv出力管理画面: window.open desde 集計ツール (tb_shukei_link.url); el legacy enviaba todos sus formularios (mode=search|search_clear|csv) a la misma URL
Route::match(['get', 'post'], 'system/category1/shukei/customerDataOutPut.php', [\App\Http\Controllers\Admin\Customers\VisitCsvController::class, 'index'])
    ->can('clientes.view')->name('clientes.visit-csv');
// --- fin C6-c ---
// --- fin C6 ---

// --- C7 ---
// Menú 初期設定管理 / 入退室管理 (left_navi1.php) y URL guardadas de las pantallas sin enlace (config/)
Route::get('system/category1/config/felica_mode.php', [\App\Http\Controllers\Admin\Customers\ReaderModeController::class, 'edit'])->name('clientes.felica-mode');
Route::get('system/category1/config/card_new.php', [\App\Http\Controllers\Admin\Customers\CardController::class, 'index'])->name('clientes.card-new');
Route::get('system/category1/config/mail_comment.php', [\App\Http\Controllers\Admin\Customers\MailCommentController::class, 'edit'])->name('clientes.mail-comment');
Route::get('system/category1/config/schoolyear_config.php', [\App\Http\Controllers\Admin\Customers\SchoolConfigController::class, 'index'])->defaults('kind', 'years')->name('clientes.schoolyear-config');
Route::get('system/category1/config/schoolclass_config.php', [\App\Http\Controllers\Admin\Customers\SchoolConfigController::class, 'index'])->defaults('kind', 'classes')->name('clientes.schoolclass-config');
Route::get('system/category1/entrance/working_calendar.php', [\App\Http\Controllers\Admin\Customers\EntryController::class, 'calendar'])->name('clientes.working-calendar');
Route::get('system/category1/entrance/work_list.php', [\App\Http\Controllers\Admin\Customers\EntryController::class, 'index'])->name('clientes.work-list');
// --- fin C7 ---

// --- C8: ポイント管理 (point_setting) ---
// Menú lateral (solo con isExtShopDomain()); el formulario del legacy hacía POST a la misma URL (act = new | up | del)
Route::middleware('can:clientes.ext-shop.enabled')->group(function () {
    Route::get('system/category1/customer/point_setting.php', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'index'])->name('clientes.point-setting');
    Route::post('system/category1/customer/point_setting.php', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'legacyRegister'])->name('clientes.point-setting.register');
    // JS de 『ポイントについて』用画像: subida temporal, borrado (delMain) y guardado (sendMainData); siempre JSON
    Route::post('system/category1/customer/ajax_apliFileUpload.php', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'apliFileUpload'])->name('clientes.apli-file-upload');
    // Temporales de la subida (./uploads/temp/<imageName>), disco public
    Route::get('system/category1/customer/uploads/temp/{file}', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'tempImage'])->where('file', '[A-Za-z0-9_.-]+')->name('clientes.apli-temp');
});
// --- fin C8 ---
