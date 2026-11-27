<?php

use App\Http\Controllers\Admin\Operaciones\InformationController;
use App\Http\Controllers\Admin\Operaciones\KintaiConfigController;
use App\Http\Controllers\Admin\Operaciones\KintaiController;
use App\Http\Controllers\Admin\Operaciones\KintaiCsvController;
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

    // 勤怠管理 (kintai/): lista del día, alta y CSV diario para todos (la tienda del no master es la suya)
    Route::match(['get', 'post'], 'kintai', [KintaiController::class, 'index'])->name('kintai.index');
    Route::get('kintai/create', [KintaiController::class, 'create'])->name('kintai.create');
    Route::post('kintai', [KintaiController::class, 'store'])->name('kintai.store');
    Route::get('kintai/csv', [KintaiCsvController::class, 'daily'])->name('kintai.csv.daily');
    // Editar, borrar, 端末パスワード変更, 各種設定 y CSV mensual: solo master (el legacy solo mostraba
    // los botones y los enlaces al master)
    Route::middleware('can:nucleo.master')->group(function () {
        Route::get('kintai/{hiduke}/edit', [KintaiController::class, 'edit'])->whereNumber('hiduke')->name('kintai.edit');
        Route::put('kintai/{hiduke}', [KintaiController::class, 'update'])->whereNumber('hiduke')->name('kintai.update');
        Route::delete('kintai/{hiduke}', [KintaiController::class, 'destroy'])->whereNumber('hiduke')->name('kintai.destroy');
        Route::delete('kintai/{hiduke}/records', [KintaiController::class, 'destroyRecords'])->whereNumber('hiduke')->name('kintai.records.destroy');
        Route::get('kintai/password', [KintaiConfigController::class, 'editPassword'])->name('kintai.password.edit');
        Route::post('kintai/password', [KintaiConfigController::class, 'updatePassword'])->name('kintai.password.update');
        Route::get('kintai/password/complete', [KintaiConfigController::class, 'passwordComplete'])->name('kintai.password.complete');
        Route::get('kintai/config', [KintaiConfigController::class, 'edit'])->name('kintai.config.edit');
        Route::post('kintai/config', [KintaiConfigController::class, 'update'])->name('kintai.config.update');
        Route::get('kintai/csv/monthly', [KintaiCsvController::class, 'monthly'])->name('kintai.csv.monthly');
    });

    // 営業日設定 (config/shop_work_calendar.php)
    Route::get('shop-work', [ShopWorkController::class, 'edit'])->name('shop-work.edit');
    Route::put('shop-work', [ShopWorkController::class, 'update'])->name('shop-work.update');

    // インフォメーション管理 (information.php)
    Route::get('information', [InformationController::class, 'index'])->name('information.index');
    Route::post('information', [InformationController::class, 'store'])->name('information.store');
    Route::put('information/{cd}', [InformationController::class, 'update'])->whereNumber('cd')->name('information.update');
    Route::delete('information/{cd}', [InformationController::class, 'destroy'])->whereNumber('cd')->name('information.destroy');
});
