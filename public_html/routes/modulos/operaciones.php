<?php

use App\Http\Controllers\Admin\Operaciones\InformationController;
use App\Http\Controllers\Admin\Operaciones\StaffController;
use App\Http\Controllers\Admin\Operaciones\StaffFieldController;
use App\Http\Controllers\Admin\Operaciones\StaffGroupController;
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
    });

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

    // インフォメーション管理 (information.php)
    Route::get('information', [InformationController::class, 'index'])->name('information.index');
    Route::post('information', [InformationController::class, 'store'])->name('information.store');
    Route::put('information/{cd}', [InformationController::class, 'update'])->whereNumber('cd')->name('information.update');
    Route::delete('information/{cd}', [InformationController::class, 'destroy'])->whereNumber('cd')->name('information.destroy');
});
