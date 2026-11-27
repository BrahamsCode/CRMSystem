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

    // --- C1-b: ficha y pestañas, borrar, recuperar (al final: captura /{customer}) ---
    Route::get('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'show'])->whereNumber('customer')->name('show');
    Route::delete('{customer}', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
    Route::post('{customer}/restore', [\App\Http\Controllers\Admin\Customers\CustomerController::class, 'restore'])->whereNumber('customer')->name('restore');
    Route::delete('{customer}/felica', [\App\Http\Controllers\Admin\Customers\CustomerFelicaController::class, 'destroy'])->whereNumber('customer')->name('felica.destroy');
    Route::delete('{customer}/coupons', [\App\Http\Controllers\Admin\Customers\CustomerCouponController::class, 'destroy'])->whereNumber('customer')->name('coupons.destroy');
    // --- fin C1-b ---
});
