<?php

/*
 * URL literales del legacy que llaman los JS de 予約管理 (S2, system/category9/yoyaku/*.php).
 * S1 las carga sin prefijo, con web + auth:admin. Nombre: legacy.reservas.*  (NUCLEO.md §3)
 */

use Illuminate\Support\Facades\Route;

// --- R3 ---
// Popup y arrastre del 予約表 (timetable_js: $.get con sess / cd / newStaff / objs / reserveDate / startTime)
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_yoyaku_register.php', [\App\Http\Controllers\Admin\Reservations\ReservationController::class, 'ajaxRegister'])->name('reservas.ajax-register');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_move_objs.php', [\App\Http\Controllers\Admin\Reservations\ReservationController::class, 'move'])->name('reservas.move');
// 顧客関連づけ (ventana y su AJAX, que responde '1')
Route::get('system/category9/yoyaku/related_customer.php', [\App\Http\Controllers\Admin\Reservations\ReservationController::class, 'relatedCustomer'])->name('reservas.related-customer');
Route::post('system/category9/yoyaku/ajax_related_customer.php', [\App\Http\Controllers\Admin\Reservations\ReservationController::class, 'storeRelatedCustomer'])->name('reservas.related-customer.store');
// AJAX de apoyo con la misma respuesta HTML
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_yoyaku_menu.php', [\App\Http\Controllers\Admin\Reservations\ReservationOptionController::class, 'menus'])->name('reservas.options.menus');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_staff.php', [\App\Http\Controllers\Admin\Reservations\ReservationOptionController::class, 'staff'])->name('reservas.options.staff');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_search_kaiin.php', [\App\Http\Controllers\Admin\Reservations\ReservationOptionController::class, 'members'])->name('reservas.options.members');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_menu.php', [\App\Http\Controllers\Admin\Reservations\StaffWorkController::class, 'menu'])->name('reservas.options.staff-works-menu');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_yoyaku_koumoku_category.php', [\App\Http\Controllers\Admin\Reservations\ReservationOptionController::class, 'menuCategories'])->name('reservas.options.menu-categories');
// --- fin R3 ---

// --- R4 ---
Route::get('system/category9/yoyaku/time_table.php', [\App\Http\Controllers\Admin\Reservations\TimetableController::class, 'index'])->name('reservas.timetable');
Route::get('system/category9/yoyaku/time_table_staff.php', [\App\Http\Controllers\Admin\Reservations\TimetableController::class, 'staff'])->name('reservas.timetable.staff');
// AJAX del 予約表 (timetable_js y time_table.php: $.get / .load con las mismas respuestas)
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_time_table.php', [\App\Http\Controllers\Admin\Reservations\TimetableController::class, 'data'])->name('reservas.timetable.data');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_loadNewYoyakuList.php', [\App\Http\Controllers\Admin\Reservations\TimetableController::class, 'recent'])->name('reservas.timetable.recent');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_loadNewRaitenList.php', [\App\Http\Controllers\Admin\Reservations\TimetableController::class, 'visits'])->name('reservas.timetable.visits');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_getYoyakuDatetimeForm.php', [\App\Http\Controllers\Admin\Reservations\TimetableController::class, 'datetimeForm'])->name('reservas.datetime-form');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajax_uketsuke_status.php', [\App\Http\Controllers\Admin\Reservations\ReservationSlotController::class, 'status'])->name('reservas.slots.status');
// --- fin R4 ---

// --- R5 ---
// 予約一覧 y 年間カレンダー (enlaces y window.open del legacy)
Route::get('system/category9/yoyaku/view_shop.php', [\App\Http\Controllers\Admin\Reservations\ReservationListController::class, 'byShop'])->name('reservas.by-shop');
Route::match(['get', 'post'], 'system/category9/yoyaku/yoyaku_list_calendar.php', [\App\Http\Controllers\Admin\Reservations\ReservationListController::class, 'calendar'])->name('reservas.list-calendar');
Route::get('system/category9/yoyaku/pop_calendar.php', [\App\Http\Controllers\Admin\Reservations\ReservationListController::class, 'popCalendar'])->name('reservas.pop-calendar');
// 予約一覧出力 y su CSV
Route::get('system/category9/yoyaku/print/yoyaku_list_print.php', [\App\Http\Controllers\Admin\Reservations\ReservationListController::class, 'print'])->name('reservas.print');
Route::match(['get', 'post'], 'system/category9/yoyaku/print/csv_download.php', [\App\Http\Controllers\Admin\Reservations\ReservationListController::class, 'csv'])->name('reservas.csv');
// 予約プリント (página del iframe y su sondeo cada 3 s)
Route::get('system/category9/yoyaku/yoyakuPrint.php', [\App\Http\Controllers\Admin\Reservations\PrinterController::class, 'index'])->name('reservas.printer');
Route::match(['get', 'post'], 'system/category9/yoyaku/ajaxYoyakuPrint.php', [\App\Http\Controllers\Admin\Reservations\PrinterController::class, 'poll'])->name('reservas.printer.poll');
// --- fin R5 ---
