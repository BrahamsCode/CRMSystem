<?php

use App\Http\Controllers\Admin\Operaciones\InformationController;
use App\Http\Controllers\Admin\Operaciones\ShopWorkController;
use App\Http\Controllers\Admin\Operaciones\StaffController;
use App\Http\Controllers\Admin\Operaciones\StaffEntryController;
use App\Http\Controllers\Admin\Operaciones\StaffFieldController;
use App\Http\Controllers\Admin\Operaciones\StaffGroupController;
use App\Http\Controllers\Admin\Operaciones\StaffShiftController;
use App\Http\Controllers\Admin\Operaciones\StaffShiftKindController;
use Illuminate\Support\Facades\Route;

/*
 * 業務支援管理 (S5). Se carga dentro del grupo admin: URL /admin/operaciones/..., nombre admin.operaciones.*
 * Paridad con el legacy: Doc/diseno/operaciones-integraciones.md §1.1-§1.5.
 */
Route::prefix('operaciones')->name('operaciones.')->group(function () {
    // スタッフグループ設定 (staff_group.php): en el legacy el menú solo la muestra al master
    Route::middleware('can:nucleo.master')->group(function () {
        Route::get('staff-groups', [StaffGroupController::class, 'index'])->name('staff-groups.index');
        Route::post('staff-groups', [StaffGroupController::class, 'store'])->name('staff-groups.store');
        Route::get('staff-groups/sort', [StaffGroupController::class, 'sort'])->name('staff-groups.sort');
        Route::put('staff-groups/{cd}', [StaffGroupController::class, 'update'])->whereNumber('cd')->name('staff-groups.update');
        Route::delete('staff-groups/{cd}', [StaffGroupController::class, 'destroy'])->whereNumber('cd')->name('staff-groups.destroy');

        // シフト区分管理 (sección de kintai/config.php, solo master como 各種設定)
        Route::get('shift-kinds', [StaffShiftKindController::class, 'index'])->name('shift-kinds.index');
        Route::post('shift-kinds', [StaffShiftKindController::class, 'store'])->name('shift-kinds.store');
        Route::get('shift-kinds/sort', [StaffShiftKindController::class, 'sort'])->name('shift-kinds.sort');
        Route::put('shift-kinds/{cd}', [StaffShiftKindController::class, 'update'])->whereNumber('cd')->name('shift-kinds.update');
        Route::delete('shift-kinds/{cd}', [StaffShiftKindController::class, 'destroy'])->whereNumber('cd')->name('shift-kinds.destroy');
    });

    // スタッフ新規登録 / 修正 (staff_entry.php → _confirm → _register → _complete, foto de inc/upload.php).
    // confirm/rewrite/store comprueban el permiso según act (new: 1024/2, up: 1024/3).
    Route::get('staff/create', [StaffEntryController::class, 'create'])->middleware('can:kengen.1024.2')->name('staff.create');
    Route::get('staff/{cd}/edit', [StaffEntryController::class, 'edit'])->whereNumber('cd')->middleware('can:kengen.1024.3')->name('staff.edit');
    Route::post('staff/confirm', [StaffEntryController::class, 'confirm'])->name('staff.confirm');
    Route::post('staff/rewrite', [StaffEntryController::class, 'rewrite'])->name('staff.rewrite');
    Route::post('staff/store', [StaffEntryController::class, 'store'])->name('staff.store');
    Route::get('staff/{cd}/complete', [StaffEntryController::class, 'complete'])->whereNumber('cd')->name('staff.complete');
    Route::post('staff/photo', [StaffEntryController::class, 'uploadPhoto'])->name('staff.photo.upload');
    Route::get('staff/photo/{token}', [StaffEntryController::class, 'tempPhoto'])->whereNumber('token')->name('staff.photo.temp');

    // スタッフ検索・一覧 (staff_list.php + csv_download.php) y スタッフ登録情報 (staff_detail.php)
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff/search', [StaffController::class, 'search'])->name('staff.search');
    Route::post('staff/csv', [StaffController::class, 'csv'])->middleware('can:nucleo.master')->name('staff.csv');
    Route::get('staff/{cd}', [StaffController::class, 'show'])->whereNumber('cd')->name('staff.show');
    // 削除する / 削除を取り消す (staff_delete_register.php, staff_recover_confirm.php): permiso 削除 de スタッフ管理
    Route::middleware('can:kengen.1024.4')->group(function () {
        Route::delete('staff/{cd}', [StaffController::class, 'destroy'])->whereNumber('cd')->name('staff.destroy');
        Route::get('staff/{cd}/restore', [StaffController::class, 'restoreConfirm'])->whereNumber('cd')->name('staff.restore-confirm');
        Route::post('staff/{cd}/restore', [StaffController::class, 'restore'])->whereNumber('cd')->name('staff.restore');
    });
    // 関連付けを解除する (staff_card_delete.php): el legacy no pedía permiso; aquí, el de 編集
    Route::delete('staff/{cd}/card', [StaffController::class, 'releaseCard'])->whereNumber('cd')
        ->middleware('can:kengen.1024.3')->name('staff.card.destroy');

    // スタッフ検索項目設定 (form_config.php)
    Route::get('staff-fields', [StaffFieldController::class, 'edit'])->name('staff-fields.edit');
    Route::put('staff-fields', [StaffFieldController::class, 'update'])->name('staff-fields.update');

    // 日別シフト管理 (schedule/staff_shift.php)
    Route::get('shifts/day', [StaffShiftController::class, 'day'])->name('shifts.day');
    Route::post('shifts/day', [StaffShiftController::class, 'save'])->name('shifts.save');
    // 月別シフト管理 (schedule/staff_shift_month.php)
    Route::get('shifts/month', [StaffShiftController::class, 'month'])->name('shifts.month');
    Route::post('shifts/month', [StaffShiftController::class, 'saveMonth'])->name('shifts.month.save');

    // 営業日設定 (config/shop_work_calendar.php)
    Route::get('shop-work', [ShopWorkController::class, 'edit'])->name('shop-work.edit');
    Route::put('shop-work', [ShopWorkController::class, 'update'])->name('shop-work.update');

    // インフォメーション管理 (information.php)
    Route::get('information', [InformationController::class, 'index'])->name('information.index');
    Route::post('information', [InformationController::class, 'store'])->name('information.store');
    Route::put('information/{cd}', [InformationController::class, 'update'])->whereNumber('cd')->name('information.update');
    Route::delete('information/{cd}', [InformationController::class, 'destroy'])->whereNumber('cd')->name('information.destroy');
    // --- W1 (S2) --- 勤怠一覧: calendario, día (+ variante y NG), corrección
    // working_calendar.php (POST con shop_work_flg=up guarda 営業日/休日), work_list.php, work_list2.php, ng_work_list.php
    Route::match(['get', 'post'], 'working/calendar', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'calendar'])->name('working.calendar');
    Route::match(['get', 'post'], 'working/day', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'day'])->name('working.day');
    Route::match(['get', 'post'], 'working/day2', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'day2'])->name('working.day2');
    Route::match(['get', 'post'], 'working/ng', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'ng'])->name('working.ng');
    // 勤怠情報 (work_list_update.php): POST (el legacy escribía con un GET)
    Route::post('working/update', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'update'])->name('working.update');
    // AJAX 出勤区分 (change_syukkin.php); los JS usan la URL del legacy (legacy.operaciones.working-change)
    Route::post('working/change', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'change'])->name('working.change');
    // --- fin W1 ---

    // --- W2 (S2) --- 勤怠: mensual, 集計, 帳票, CSV
    // スタッフ一覧 (working_list.php) y スタッフ勤怠集計一覧 (working_total_list.php), con su búsqueda en sesión
    Route::get('working/monthly', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'monthly'])->name('working.monthly');
    Route::get('working/totals', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'totals'])->name('working.totals');
    Route::post('working/{screen}/search', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'search'])
        ->whereIn('screen', ['monthly', 'totals'])->name('working.search');
    // 帳票管理 (working_chouhyou.php): en lugar de SERVER_ADDR, la bandera de config/working.php
    Route::get('working/report', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'report'])
        ->middleware('can:operaciones.working-report.enabled')->name('working.report');
    // CSV 勤怠 del día (csv_download.php): dd = 'Y/n/j', shop_cd
    Route::match(['get', 'post'], 'working/csv', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'csv'])->name('working.csv');
    // --- fin W2 ---
});
