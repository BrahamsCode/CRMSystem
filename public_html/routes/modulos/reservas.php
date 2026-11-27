<?php

/*
 * 予約管理 (S2). Se carga dentro del grupo admin (prefijo, auth:admin, nombre 'admin.').
 * Ver Doc/diseno/clientes-ventas-reservas.md §1.3.
 */

use Illuminate\Support\Facades\Route;

Route::prefix('reservas')->name('reservas.')->group(function () {
    // --- R1-A: 予約設定 (tienda, formulario, funciones, 注釈) ---
    Route::controller(\App\Http\Controllers\Admin\Reservations\ReservationSettingController::class)
        ->prefix('settings')->name('settings.')->group(function () {
            Route::get('shop', 'shop')->name('shop');
            Route::post('shop', 'updateShop')->name('shop.update');
            // ajax_yoyaku_config.php (el JS inline de la pantalla llama a esta ruta)
            Route::get('shop/data', 'shopData')->name('shop.data');
            Route::get('form', 'form')->name('form');
            Route::post('form', 'updateForm')->name('form.update');
            Route::get('features', 'features')->name('features');
            Route::post('features', 'updateFeatures')->name('features.update');
            Route::get('comment', 'comment')->name('comment');
            Route::post('comment', 'updateComment')->name('comment.update');
        });
    // --- fin R1-A ---

    // --- R1-B: 予約項目, 予約項目カテゴリ, 設備 ---
    // 予約項目管理 (yoyaku_koumoku_new.php / _register.php; la lista de ajax_yoyaku_koumoku_new.php → menus.list y menus.sort)
    Route::get('menus', [\App\Http\Controllers\Admin\Reservations\ReservationMenuController::class, 'index'])->name('menus.index');
    Route::post('menus', [\App\Http\Controllers\Admin\Reservations\ReservationMenuController::class, 'store'])->name('menus.store');
    Route::post('menus/list', [\App\Http\Controllers\Admin\Reservations\ReservationMenuController::class, 'list'])->name('menus.list');
    Route::post('menus/sort', [\App\Http\Controllers\Admin\Reservations\ReservationMenuController::class, 'sort'])->name('menus.sort');
    Route::put('menus/{menu}', [\App\Http\Controllers\Admin\Reservations\ReservationMenuController::class, 'update'])->name('menus.update');
    Route::delete('menus/{menu}', [\App\Http\Controllers\Admin\Reservations\ReservationMenuController::class, 'destroy'])->name('menus.destroy');

    // 予約項目カテゴリ管理 (yoyaku_koumoku_category.php / _register.php; ajax_yoyaku_koumoku_category.php → list, sort, toggle, destroy)
    Route::get('menu-categories', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'index'])->name('menu-categories.index');
    Route::post('menu-categories', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'store'])->name('menu-categories.store');
    Route::post('menu-categories/list', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'list'])->name('menu-categories.list');
    Route::put('menu-categories/{category}', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'update'])->name('menu-categories.update');
    Route::post('menu-categories/{category}/sort', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'sort'])->name('menu-categories.sort');
    Route::post('menu-categories/{category}/toggle', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'toggle'])->name('menu-categories.toggle');
    Route::delete('menu-categories/{category}', [\App\Http\Controllers\Admin\Reservations\ReservationMenuCategoryController::class, 'destroy'])->name('menu-categories.destroy');

    // 設備管理 (setsubi_new.php / setsubi_register.php)
    Route::get('facilities', [\App\Http\Controllers\Admin\Reservations\FacilityController::class, 'index'])->name('facilities.index');
    Route::post('facilities', [\App\Http\Controllers\Admin\Reservations\FacilityController::class, 'store'])->name('facilities.store');
    Route::put('facilities/{facility}', [\App\Http\Controllers\Admin\Reservations\FacilityController::class, 'update'])->name('facilities.update');
    Route::delete('facilities/{facility}', [\App\Http\Controllers\Admin\Reservations\FacilityController::class, 'destroy'])->name('facilities.destroy');
    // --- fin R1-B ---

    // --- R1-C: スタッフ施術管理, フリー予約 ---
    // staff_works.php (+ su AJAX ajax_menu.php, solo lo usa esta pantalla)
    Route::get('staff-works', [\App\Http\Controllers\Admin\Reservations\StaffWorkController::class, 'edit'])->name('staff-works');
    Route::post('staff-works', [\App\Http\Controllers\Admin\Reservations\StaffWorkController::class, 'update'])->name('staff-works.update');
    Route::match(['get', 'post'], 'staff-works/menu', [\App\Http\Controllers\Admin\Reservations\StaffWorkController::class, 'menu'])->name('staff-works.menu');
    // free_yoyaku.php, free_yoyaku_register.php y ajax_free_yoyaku_rank.php
    Route::get('free', [\App\Http\Controllers\Admin\Reservations\FreeReservationController::class, 'edit'])->name('free');
    Route::post('free', [\App\Http\Controllers\Admin\Reservations\FreeReservationController::class, 'update'])->name('free.update');
    Route::match(['get', 'post'], 'free/ranks', [\App\Http\Controllers\Admin\Reservations\FreeReservationController::class, 'ranks'])->name('free.ranks');
    // --- fin R1-C ---

    // --- R2: 受付日設定 (franjas) ---
    // yoyaku_uketsuke_day.php (?cd= | ?staff=&yoyaku_date=), _register.php y _delete.php
    Route::get('slots/edit', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'edit'])->name('slots.edit');
    Route::post('slots', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'store'])->name('slots.store');
    Route::delete('slots/{slot}', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'destroy'])->whereNumber('slot')->name('slots.destroy');
    // yoyaku_multi_uketsuke_day.php y yoyaku_multi_uketsuke_register.php
    Route::get('slots/bulk', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'bulkCreate'])->name('slots.bulk');
    Route::post('slots/bulk', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'bulkStore'])->name('slots.bulk.store');
    // ajax_uketsuke_status.php: el JS del 予約表 llama a la URL legacy /system/category9/yoyaku/ajax_uketsuke_status.php (la publica otra sesión → esta acción)
    Route::get('slots/status', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'status'])->name('slots.status');
    // --- fin R2 ---
    // --- R3 ---
    // Flujo de reserva: yoyaku.php → yoyaku_calendar.php → yoyaku_customer_select.php → yoyaku_register.php
    Route::controller(\App\Http\Controllers\Admin\Reservations\ReservationController::class)->group(function () {
        Route::get('create', 'create')->name('create');
        Route::get('calendar-select', 'calendar')->name('calendar-select');
        Route::get('customer-select', 'customerSelect')->name('customer-select');
        Route::post('/', 'store')->name('store');
        // ajax_yoyaku_register.php (cambio desde el popup del 予約表; misma respuesta de texto)
        Route::put('{reservation}', 'update')->whereNumber('reservation')->name('update');
        // ajax_move_objs.php (mover a otro empleado / cambiar día y hora)
        Route::post('move', 'move')->name('move');
        // 顧客関連づけ: related_customer.php y ajax_related_customer.php
        Route::get('related-customer', 'relatedCustomer')->name('related-customer');
        Route::post('related-customer', 'storeRelatedCustomer')->name('related-customer.store');
    });
    // AJAX de apoyo: ajax_yoyaku_menu.php, ajax_staff.php, ajax_search_kaiin.php
    Route::controller(\App\Http\Controllers\Admin\Reservations\ReservationOptionController::class)
        ->prefix('options')->name('options.')->group(function () {
            Route::match(['get', 'post'], 'menus', 'menus')->name('menus');
            Route::match(['get', 'post'], 'staff', 'staff')->name('staff');
            Route::match(['get', 'post'], 'members', 'members')->name('members');
        });
    // --- fin R3 ---

    // --- R4 ---
    // 予約表: time_table.php (día, 週間, 月間) y time_table_staff.php (スタッフ別予約表), con sus AJAX
    Route::controller(\App\Http\Controllers\Admin\Reservations\TimetableController::class)->group(function () {
        Route::get('timetable', 'index')->name('timetable');
        Route::get('timetable/staff', 'staff')->name('timetable.staff');
        // ajax_time_table.php (popup de edición de una reserva)
        Route::match(['get', 'post'], 'timetable/data', 'data')->name('timetable.data');
        // ajax_loadNewYoyakuList.php y ajax_loadNewRaitenList.php (listas laterales)
        Route::match(['get', 'post'], 'timetable/recent', 'recent')->name('timetable.recent');
        Route::match(['get', 'post'], 'timetable/visits', 'visits')->name('timetable.visits');
        // ajax_getYoyakuDatetimeForm.php (cambio de fecha y hora)
        Route::match(['get', 'post'], 'datetime-form', 'datetimeForm')->name('datetime-form');
    });
    // --- fin R4 ---

    // --- R5 ---
    // 予約一覧: view_shop.php (店舗毎予約数), yoyaku_list_calendar.php (tb_reserve), pop_calendar.php (年間カレンダー)
    Route::controller(\App\Http\Controllers\Admin\Reservations\ReservationListController::class)->group(function () {
        Route::get('by-shop', 'byShop')->name('by-shop');
        Route::match(['get', 'post'], 'list-calendar', 'calendar')->name('list-calendar');
        Route::get('pop-calendar', 'popCalendar')->name('pop-calendar');
        // print/yoyaku_list_print.php y su CSV (print/csv_download.php; sin los archivos temporales del Flash)
        Route::get('print', 'print')->name('print');
        Route::match(['get', 'post'], 'csv', 'csv')->name('csv');
    });
    // 予約プリント: yoyakuPrint.php (iframe del 予約表) y su sondeo ajaxYoyakuPrint.php
    Route::get('printer', [\App\Http\Controllers\Admin\Reservations\PrinterController::class, 'index'])->name('printer');
    Route::match(['get', 'post'], 'printer/poll', [\App\Http\Controllers\Admin\Reservations\PrinterController::class, 'poll'])->name('printer.poll');
    // --- fin R5 ---

    // --- R6 ---
    // 予約管理 simple (category3/reservation, tb_reserve): alta en pasos, 予約・来店一覧, lista del día, detalle y estados
    Route::controller(\App\Http\Controllers\Admin\Reservations\SimpleReservationController::class)
        ->prefix('simple')->name('simple.')->group(function () {
            Route::match(['get', 'post'], 'customer-search', 'customerSearch')->name('customer-search');
            Route::match(['get', 'post'], 'create', 'create')->name('create');
            Route::post('confirm', 'confirm')->name('confirm');
            Route::post('/', 'store')->name('store');
            Route::get('{reservation}/complete', 'complete')->whereNumber('reservation')->name('complete');
            Route::match(['get', 'post'], 'calendar', 'calendar')->name('calendar');
            Route::match(['get', 'post'], 'list', 'index')->name('index');
            Route::get('{reservation}', 'show')->whereNumber('reservation')->name('show');
            Route::post('{reservation}/status', 'status')->whereNumber('reservation')->name('status');
            Route::post('{reservation}/delete', 'destroy')->whereNumber('reservation')->name('destroy');
            // 予約項目設定 (tb_koumoku, tb_name = 'tb_reserve')
            Route::get('config', 'config')->name('config');
            Route::post('config', 'updateConfig')->name('config.update');
            // ajax_category.php / ajax_staff.php
            Route::match(['get', 'post'], 'options/categories', 'categoryOptions')->name('options.categories');
            Route::match(['get', 'post'], 'options/staff', 'staffOptions')->name('options.staff');
        });
    // 予約カテゴリー登録・変更 (tb_reserve_name)
    Route::controller(\App\Http\Controllers\Admin\Reservations\SimpleCategoryController::class)
        ->prefix('simple-categories')->name('simple-categories.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('{category}/delete', 'destroy')->whereNumber('category')->name('destroy');
        });
    // 予約機種一覧 (tb_change_model, solo lectura) y su CSV (csv_print=1)
    Route::match(['get', 'post'], 'model-changes', [\App\Http\Controllers\Admin\Reservations\ModelChangeController::class, 'index'])->name('model-changes');
    // --- fin R6 ---
});
