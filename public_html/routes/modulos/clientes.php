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

    // --- C2-a: 表示・検索・CSV項目設定, 顧客追加情報設定 (categorías y campos) ---
    Route::get('field-settings', [\App\Http\Controllers\Admin\Customers\FieldSettingController::class, 'index'])->name('field-settings');
    Route::post('field-settings', [\App\Http\Controllers\Admin\Customers\FieldSettingController::class, 'update'])->name('field-settings.update');

    Route::get('custom-categories', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'index'])->name('custom-categories');
    Route::post('custom-categories/flags', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'updateFlags'])->name('custom-categories.flags');
    Route::post('custom-categories/sort', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'sort'])->name('custom-categories.sort');
    Route::get('custom-categories/create', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'create'])->name('custom-categories.create');
    Route::post('custom-categories', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'store'])->name('custom-categories.store');
    Route::get('custom-categories/{category}/edit', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'edit'])->whereNumber('category')->name('custom-categories.edit');
    Route::put('custom-categories/{category}', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'update'])->whereNumber('category')->name('custom-categories.update');
    Route::delete('custom-categories/{category}', [\App\Http\Controllers\Admin\Customers\CustomCategoryController::class, 'destroy'])->whereNumber('category')->name('custom-categories.destroy');

    Route::get('custom-categories/{category}/fields', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'index'])->whereNumber('category')->name('custom-fields');
    Route::post('custom-categories/{category}/fields/flags', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'updateFlags'])->whereNumber('category')->name('custom-fields.flags');
    Route::post('custom-categories/{category}/fields/sort', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'sort'])->whereNumber('category')->name('custom-fields.sort');
    Route::get('custom-categories/{category}/fields/create', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'create'])->whereNumber('category')->name('custom-fields.create');
    Route::post('custom-categories/{category}/fields', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'store'])->whereNumber('category')->name('custom-fields.store');
    Route::get('custom-fields/{field}/edit', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'edit'])->whereNumber('field')->name('custom-fields.edit');
    Route::put('custom-fields/{field}', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'update'])->whereNumber('field')->name('custom-fields.update');
    Route::delete('custom-fields/{field}', [\App\Http\Controllers\Admin\Customers\CustomFieldController::class, 'destroy'])->whereNumber('field')->name('custom-fields.destroy');
    // --- fin C2-a ---

    // --- C2-b ---
    // 顧客グループ設定 / 初回来店動機項目設定 / 顧客レベル: formulario (?cd= edita) + lista con ▲ y 削除
    foreach ([
        'groups' => \App\Http\Controllers\Admin\Customers\CustomerGroupController::class,
        'visit-motives' => \App\Http\Controllers\Admin\Customers\VisitMotiveController::class,
        'levels' => \App\Http\Controllers\Admin\Customers\CustomerLevelController::class,
    ] as $uri => $controller) {
        Route::get($uri, [$controller, 'index'])->name($uri);
        Route::post($uri, [$controller, 'store'])->name("{$uri}.store");
        Route::put("{$uri}/{cd}", [$controller, 'update'])->whereNumber('cd')->name("{$uri}.update");
        Route::delete("{$uri}/{cd}", [$controller, 'destroy'])->whereNumber('cd')->name("{$uri}.destroy");
        Route::post("{$uri}/{cd}/sort", [$controller, 'sort'])->whereNumber('cd')->name("{$uri}.sort");
    }

    // 顧客ランク設定 ({type} = kingaku | raiten)
    Route::get('ranks', [\App\Http\Controllers\Admin\Customers\RankController::class, 'index'])->name('ranks');
    Route::post('ranks/display', [\App\Http\Controllers\Admin\Customers\RankController::class, 'updateDisplay'])->name('ranks.display');
    Route::get('ranks/{type}/create', [\App\Http\Controllers\Admin\Customers\RankController::class, 'create'])->whereIn('type', ['kingaku', 'raiten'])->name('ranks.create');
    Route::post('ranks/{type}', [\App\Http\Controllers\Admin\Customers\RankController::class, 'store'])->whereIn('type', ['kingaku', 'raiten'])->name('ranks.store');
    Route::post('ranks/{type}/sort', [\App\Http\Controllers\Admin\Customers\RankController::class, 'sort'])->whereIn('type', ['kingaku', 'raiten'])->name('ranks.sort');
    Route::get('ranks/{type}/{cd}/edit', [\App\Http\Controllers\Admin\Customers\RankController::class, 'edit'])->whereIn('type', ['kingaku', 'raiten'])->whereNumber('cd')->name('ranks.edit');
    Route::put('ranks/{type}/{cd}', [\App\Http\Controllers\Admin\Customers\RankController::class, 'update'])->whereIn('type', ['kingaku', 'raiten'])->whereNumber('cd')->name('ranks.update');
    Route::delete('ranks/{type}/{cd}', [\App\Http\Controllers\Admin\Customers\RankController::class, 'destroy'])->whereIn('type', ['kingaku', 'raiten'])->whereNumber('cd')->name('ranks.destroy');
    Route::post('ranks/{type}/{cd}/status', [\App\Http\Controllers\Admin\Customers\RankController::class, 'updateStatus'])->whereIn('type', ['kingaku', 'raiten'])->whereNumber('cd')->name('ranks.status');

    // 顧客ランク振り分け設定
    Route::get('rank-schedules', [\App\Http\Controllers\Admin\Customers\RankScheduleController::class, 'edit'])->name('rank-schedules');
    Route::post('rank-schedules', [\App\Http\Controllers\Admin\Customers\RankScheduleController::class, 'update'])->name('rank-schedules.update');
    // --- fin C2-b ---

    // --- C3 ---
    // 来店処理 (customer_search.php)
    Route::get('visits', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'index'])->name('visits');
    Route::post('visits', [\App\Http\Controllers\Admin\Customers\VisitController::class, 'store'])->name('visits.store');
    // 来店履歴の追加・修正 (pestaña 9: POST con kaiin_cd, act, related_features_cd… como el legacy)
    Route::match(['get', 'post'], 'visit-records/create', [\App\Http\Controllers\Admin\Customers\VisitRecordController::class, 'create'])->name('visit-records.create');
    Route::post('visit-records', [\App\Http\Controllers\Admin\Customers\VisitRecordController::class, 'store'])->name('visit-records.store');
    Route::post('visit-records/delete', [\App\Http\Controllers\Admin\Customers\VisitRecordController::class, 'destroy'])->name('visit-records.destroy');
    // Puntos y sellos manuales de la ficha (los JS llaman a las URL legacy; mismos métodos)
    Route::match(['get', 'post'], 'points', [\App\Http\Controllers\Admin\Customers\CustomerLoyaltyController::class, 'points'])->name('points.store');
    Route::match(['get', 'post'], 'stamps', [\App\Http\Controllers\Admin\Customers\CustomerLoyaltyController::class, 'stamps'])->name('stamps.store');
    Route::match(['get', 'post'], 'stamps/use', [\App\Http\Controllers\Admin\Customers\CustomerLoyaltyController::class, 'useStamps'])->name('stamps.use');
    // ポイント利用一覧 (point_history.php) y su CSV
    Route::match(['get', 'post'], 'point-history', [\App\Http\Controllers\Admin\Customers\PointHistoryController::class, 'index'])->name('point-history');
    Route::match(['get', 'post'], 'point-history/csv', [\App\Http\Controllers\Admin\Customers\PointHistoryController::class, 'csv'])->name('point-history.csv');
    // --- fin C3 ---

    // --- C4-a ---
    // 追加情報 de varios registros (pestaña custom: add_record_new/_confirm/_register.php y del_record.php; POST como el legacy)
    Route::post('custom-records/create', [\App\Http\Controllers\Admin\Customers\CustomRecordController::class, 'create'])->name('custom-records.create');
    Route::post('custom-records/confirm', [\App\Http\Controllers\Admin\Customers\CustomRecordController::class, 'confirm'])->name('custom-records.confirm');
    Route::post('custom-records', [\App\Http\Controllers\Admin\Customers\CustomRecordController::class, 'store'])->name('custom-records.store');
    Route::post('custom-records/delete', [\App\Http\Controllers\Admin\Customers\CustomRecordController::class, 'destroy'])->name('custom-records.destroy');
    // 顧客検索: selector de cliente para la カルテ (karute_customer_search.php)
    Route::match(['get', 'post'], 'lookup', [\App\Http\Controllers\Admin\Customers\CustomerLookupController::class, 'index'])->name('lookup');
    // <option> de ajax_category.php (施術カテゴリー) y ajax_staff.php (担当者)
    Route::match(['get', 'post'], 'options/categories', [\App\Http\Controllers\Admin\Customers\OptionController::class, 'categories'])->name('options.categories');
    Route::match(['get', 'post'], 'options/staff', [\App\Http\Controllers\Admin\Customers\OptionController::class, 'staff'])->name('options.staff');
    // --- fin C4-a ---

    // --- C4-b ---
    // カルテ de la pestaña 5 (inc/karute_new.php, karute_update.php, karute_frm.php, karute_register.php)
    Route::get('{customer}/kartes/create', [\App\Http\Controllers\Admin\Customers\KarteController::class, 'create'])->whereNumber('customer')->name('kartes.create');
    Route::post('{customer}/kartes', [\App\Http\Controllers\Admin\Customers\KarteController::class, 'store'])->whereNumber('customer')->name('kartes.store');
    Route::get('{customer}/kartes/{karte}/edit', [\App\Http\Controllers\Admin\Customers\KarteController::class, 'edit'])->whereNumber(['customer', 'karte'])->name('kartes.edit');
    Route::put('{customer}/kartes/{karte}', [\App\Http\Controllers\Admin\Customers\KarteController::class, 'update'])->whereNumber(['customer', 'karte'])->name('kartes.update');
    // 美容: カルテ詳細 (biyou/karute_detail.php) y 予約カテゴリー管理 / 施術カテゴリー (biyou/sejutsu_cate*.php)
    Route::get('kartes/{karte}', [\App\Http\Controllers\Admin\Customers\KarteController::class, 'show'])->whereNumber('karte')->name('kartes.show');
    Route::get('service-categories', [\App\Http\Controllers\Admin\Customers\ServiceCategoryController::class, 'index'])->name('service-categories.index');
    Route::post('service-categories', [\App\Http\Controllers\Admin\Customers\ServiceCategoryController::class, 'store'])->name('service-categories.store');
    Route::put('service-categories/{cd}', [\App\Http\Controllers\Admin\Customers\ServiceCategoryController::class, 'update'])->whereNumber('cd')->name('service-categories.update');
    Route::delete('service-categories/{cd}', [\App\Http\Controllers\Admin\Customers\ServiceCategoryController::class, 'destroy'])->whereNumber('cd')->name('service-categories.destroy');
    // --- fin C4-b ---

    // --- C4-c ---
    // マイアルバム (pestaña 7: photoalbum_new/_update/_register/_delete.php) y la subida AjaxUpload (ajax_picupload.php)
    Route::post('photos/upload', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'upload'])->name('photos.upload');
    Route::get('{customer}/photos/create', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'create'])->whereNumber('customer')->name('photos.create');
    Route::post('{customer}/photos', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'store'])->whereNumber('customer')->name('photos.store');
    Route::get('{customer}/photos/{photo}/edit', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'edit'])->whereNumber(['customer', 'photo'])->name('photos.edit');
    Route::put('{customer}/photos/{photo}', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'update'])->whereNumber(['customer', 'photo'])->name('photos.update');
    Route::delete('{customer}/photos/{photo}', [\App\Http\Controllers\Admin\Customers\CustomerPhotoController::class, 'destroy'])->whereNumber(['customer', 'photo'])->name('photos.destroy');
    // --- fin C4-c ---

    // --- C5 ---
    // 顧客管理トップ (category1/index.php): la portada es del núcleo (Nucleo\CategoryTopController, NUCLEO.md §3)
    // CSVインポート (csv_import.php; la plantilla es export?format=template)
    Route::get('import', [\App\Http\Controllers\Admin\Customers\CustomerImportController::class, 'create'])->name('import');
    Route::post('import', [\App\Http\Controllers\Admin\Customers\CustomerImportController::class, 'store'])->name('import.store');
    // CSVエクスポート de maestras (csv_export.php, sin enlace en el menú)
    Route::get('table-export', [\App\Http\Controllers\Admin\Customers\TableExportController::class, 'index'])->name('table-export');
    Route::post('table-export', [\App\Http\Controllers\Admin\Customers\TableExportController::class, 'export'])->name('table-export.download');
    // --- fin C5 ---

    // --- C6 ---
    // --- C6-a: 集計ツール (shukei_list, model1/2, point1/2, stamp1) ---
    // Los enlaces de tb_shukei_link usan las URL legacy (routes/legacy/clientes.php); aquí, las mismas pantallas con nombre
    Route::get('reports', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'index'])->can('clientes.view')->name('reports.index');
    foreach (['model1', 'model2', 'point1', 'point2', 'stamp1'] as $report) {
        Route::get("reports/{$report}", [\App\Http\Controllers\Admin\Customers\ReportController::class, $report])->can('clientes.view')->name("reports.{$report}");
    }
    Route::post('reports/model1/select', [\App\Http\Controllers\Admin\Customers\ReportController::class, 'model1Select'])->can('clientes.view')->name('reports.model1.select');
    // --- fin C6-a ---
    // --- C6-b: 顧客情報集計 (customer_sumup_list) ---
    // 顧客情報集計 (customer_sumup_list.php): el buscador hace POST a la misma pantalla
    Route::match(['get', 'post'], 'statistics', [\App\Http\Controllers\Admin\Customers\StatisticController::class, 'index'])->can('clientes.view')->name('statistics');
    // --- fin C6-b ---
    // --- C6-c: csv出力管理画面 (customerDataOutPut) ---
    // csv出力管理画面 (shukei/customerDataOutPut.php): buscador (mode=search|search_clear) y descarga por bloques
    Route::match(['get', 'post'], 'reports/visit-csv', [\App\Http\Controllers\Admin\Customers\VisitCsvController::class, 'index'])->can('clientes.view')->name('reports.visit-csv');
    Route::post('reports/visit-csv/download', [\App\Http\Controllers\Admin\Customers\VisitCsvController::class, 'download'])->can('clientes.view')->name('reports.visit-csv.download');
    // --- fin C6-c ---
    // --- fin C6 ---

    // --- C7 ---
    // 初期設定管理: カードリーダーモード設定 (config/felica_mode.php) y カード管理 (config/card_new.php)
    Route::get('reader-mode', [\App\Http\Controllers\Admin\Customers\ReaderModeController::class, 'edit'])->name('reader-mode');
    Route::post('reader-mode', [\App\Http\Controllers\Admin\Customers\ReaderModeController::class, 'update'])->name('reader-mode.update');
    Route::get('cards', [\App\Http\Controllers\Admin\Customers\CardController::class, 'index'])->name('cards');
    Route::match(['get', 'post'], 'cards/select', [\App\Http\Controllers\Admin\Customers\CardController::class, 'select'])->name('cards.select');
    Route::post('cards', [\App\Http\Controllers\Admin\Customers\CardController::class, 'store'])->name('cards.store');
    Route::post('cards/mode', [\App\Http\Controllers\Admin\Customers\CardController::class, 'mode'])->name('cards.mode');
    // メールコメント (config/mail_comment.php, mail_comment_register.php)
    Route::get('mail-comment', [\App\Http\Controllers\Admin\Customers\MailCommentController::class, 'edit'])->name('mail-comment');
    Route::post('mail-comment', [\App\Http\Controllers\Admin\Customers\MailCommentController::class, 'update'])->name('mail-comment.update');
    // 学年設定 / クラス設定 (config/schoolyear_*.php, schoolclass_*.php): mismo controlador, 'kind' por defecto
    foreach (['school-years' => 'years', 'school-classes' => 'classes'] as $prefix => $kind) {
        Route::get($prefix, [\App\Http\Controllers\Admin\Customers\SchoolConfigController::class, 'index'])->defaults('kind', $kind)->name("{$prefix}.index");
        Route::post($prefix, [\App\Http\Controllers\Admin\Customers\SchoolConfigController::class, 'store'])->defaults('kind', $kind)->name("{$prefix}.store");
        Route::put("{$prefix}/{cd}", [\App\Http\Controllers\Admin\Customers\SchoolConfigController::class, 'update'])->whereNumber('cd')->defaults('kind', $kind)->name("{$prefix}.update");
        Route::delete("{$prefix}/{cd}", [\App\Http\Controllers\Admin\Customers\SchoolConfigController::class, 'destroy'])->whereNumber('cd')->defaults('kind', $kind)->name("{$prefix}.destroy");
    }
    // 入退室一覧 (entrance/working_calendar.php y work_list.php)
    Route::get('entries/calendar', [\App\Http\Controllers\Admin\Customers\EntryController::class, 'calendar'])->name('entries.calendar');
    Route::get('entries', [\App\Http\Controllers\Admin\Customers\EntryController::class, 'index'])->name('entries.index');
    Route::post('entries', [\App\Http\Controllers\Admin\Customers\EntryController::class, 'update'])->name('entries.update');
    // --- fin C7 ---

    // --- C8: ポイント管理 (point_setting) ---
    // Solo con isExtShopDomain() (mycolle/app_layout_mode = '1'), como el menú. Formulario (?cd= edita) + lista ▲▼ y 削除
    Route::middleware('can:clientes.ext-shop.enabled')->group(function () {
        Route::get('point-settings', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'index'])->name('point-settings');
        Route::post('point-settings', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'store'])->name('point-settings.store');
        Route::put('point-settings/{cd}', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'update'])->whereNumber('cd')->name('point-settings.update');
        Route::delete('point-settings/{cd}', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'destroy'])->whereNumber('cd')->name('point-settings.destroy');
        Route::post('point-settings/{cd}/sort', [\App\Http\Controllers\Admin\Customers\PointSettingController::class, 'sort'])->whereNumber('cd')->name('point-settings.sort');
    });
    // --- fin C8 ---

    // --- C1-b: ficha y pestañas, borrar, recuperar (al final: captura /{customer}) ---
    Route::get('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'show'])->whereNumber('customer')->name('show');
    Route::delete('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
    Route::post('{customer}/restore', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'restore'])->whereNumber('customer')->name('restore');
    Route::delete('{customer}/felica', [\App\Http\Controllers\Admin\Customers\CustomerFelicaController::class, 'destroy'])->whereNumber('customer')->name('felica.destroy');
    Route::delete('{customer}/coupons', [\App\Http\Controllers\Admin\Customers\CustomerCouponController::class, 'destroy'])->whereNumber('customer')->name('coupons.destroy');
    // --- fin C1-b ---
});
