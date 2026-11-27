<?php

use Illuminate\Support\Facades\Route;

/*
 * URL literales del legacy de 業務支援管理 (category5) que llaman los JS, los formularios y los marcadores.
 * S1 las carga sin prefijo de URL, con el guard del personal y el prefijo de nombre `legacy.`: nombres legacy.operaciones.*
 */
Route::name('operaciones.')->group(function () {
    // --- W1 (S2) --- 勤怠一覧 (category5/working)
    // AJAX de work_list2 / ng_work_list (misma respuesta JSON). change_syukkin2.php no se migra (inyección SQL)
    Route::post('system/category5/working/change_syukkin.php', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'change'])->name('working-change');
    // Marcadores y formularios viejos: a la pantalla nueva con los mismos parámetros
    Route::match(['get', 'post'], 'system/category5/working/working_calendar.php', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'legacy'])->defaults('target', 'calendar')->name('working-calendar');
    Route::match(['get', 'post'], 'system/category5/working/work_list.php', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'legacy'])->defaults('target', 'day')->name('working-day');
    Route::match(['get', 'post'], 'system/category5/working/work_list2.php', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'legacy'])->defaults('target', 'day2')->name('working-day2');
    Route::match(['get', 'post'], 'system/category5/working/ng_work_list.php', [\App\Http\Controllers\Admin\Operaciones\WorkingController::class, 'legacy'])->defaults('target', 'ng')->name('working-ng');
    // --- fin W1 ---

    // --- W2 (S2) --- 勤怠 mensual, 集計, 帳票, CSV (category5/working)
    // Marcadores de las pantallas: a la ruta nueva conservando la query
    Route::get('system/category5/working/working_list.php', fn (\Illuminate\Http\Request $r) => redirect()->route('admin.operaciones.working.monthly', $r->query()))
        ->name('working-monthly');
    Route::get('system/category5/working/working_total_list.php', fn (\Illuminate\Http\Request $r) => redirect()->route('admin.operaciones.working.totals', $r->query()))
        ->name('working-totals');
    Route::get('system/category5/working/working_chouhyou.php', fn (\Illuminate\Http\Request $r) => redirect()->route('admin.operaciones.working.report', $r->query()))
        ->name('working-report');
    // CSV: el formulario de work_list.php (action="csv_download.php") y el Flash (csv_file_out.php, ahora descarga directa)
    Route::match(['get', 'post'], 'system/category5/working/csv_download.php', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'csv'])
        ->name('working-csv');
    Route::match(['get', 'post'], 'system/category5/working/csv_download/csv_file_out.php', [\App\Http\Controllers\Admin\Operaciones\WorkingSummaryController::class, 'csv'])
        ->name('working-csv-file-out');
    // --- fin W2 ---
});
