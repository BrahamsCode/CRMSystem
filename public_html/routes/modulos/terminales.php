<?php

use App\Http\Controllers\Admin\Terminales\SignageDisplayController;
use App\Http\Controllers\Admin\Terminales\SignageMaterialController;
use App\Http\Controllers\Admin\Terminales\SignageProgramController;
use Illuminate\Support\Facades\Route;

/*
 * 端末管理 / 電子看板 (S4, category7/digital_signage). Se carga dentro del grupo admin:
 * URL /admin/terminales/..., nombre admin.terminales.*
 *
 * Varias pantallas aceptan GET y POST porque el HTML del legacy llega a ellas con
 * formularios POST (buscador, 件数, «<<一覧へ戻る», 編集する, «<<詳細へ戻る»).
 * Los ▲▼ son GET (el legacy hace location.href).
 */
Route::prefix('terminales')->name('terminales.')->group(function () {
    // 放映素材 (screen_saver_*.php)
    Route::prefix('signage-materials')->name('signage-materials.')->controller(SignageMaterialController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::get('sort', 'sort')->name('sort');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('deleted', 'deleted')->name('deleted');
        Route::get('{cd}', 'show')->whereNumber('cd')->name('show');
        Route::match(['get', 'post'], '{cd}/edit', 'edit')->whereNumber('cd')->name('edit');
        Route::post('{cd}/update', 'update')->whereNumber('cd')->name('update');
        Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // 放映プログラム (movie_program_*.php); sin ▲▼: el legacy tiene esa columna comentada
    Route::prefix('signage-programs')->name('signage-programs.')->controller(SignageProgramController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::match(['get', 'post'], '{cd}', 'show')->whereNumber('cd')->name('show');
        Route::match(['get', 'post'], '{cd}/edit', 'edit')->whereNumber('cd')->name('edit');
        Route::post('{cd}/update', 'update')->whereNumber('cd')->name('update');
        Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // ディスプレイ (display_*.php)
    Route::prefix('signage-displays')->name('signage-displays.')->controller(SignageDisplayController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::get('sort', 'sort')->name('sort');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('{cd}', 'show')->whereNumber('cd')->name('show');
        Route::match(['get', 'post'], '{cd}/edit', 'edit')->whereNumber('cd')->name('edit');
        Route::post('{cd}/update', 'update')->whereNumber('cd')->name('update');
        Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // ペーパーサイネージ (card_signage/*.php). control.php (AJAX) está en routes/legacy/terminales.php
    Route::prefix('paper-cards')->name('paper-cards.')->controller(\App\Http\Controllers\Admin\Terminales\PaperCardController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('edit', 'edit')->name('edit');
        Route::get('aggregate', 'aggregate')->name('aggregate');
        Route::get('csv', 'csv')->name('csv');
        Route::get('qr', 'qr')->name('qr');
    });

    // ICカード管理 / 端末管理 (felica/terminal_list.php). Borrado AJAX en routes/legacy/terminales.php
    Route::prefix('felica-terminals')->name('felica-terminals.')->controller(\App\Http\Controllers\Admin\Terminales\FelicaTerminalController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::get('sort', 'sort')->name('sort');
    });

    // ICカード管理 / カード管理 (felica/card_list.php). 会員検索 y borrado AJAX en routes/legacy/terminales.php
    Route::prefix('felica-cards')->name('felica-cards.')->controller(\App\Http\Controllers\Admin\Terminales\FelicaCardController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::post('update', 'update')->name('update');
    });
});
