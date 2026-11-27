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
});
