<?php

use App\Http\Controllers\Admin\Senalizacion\BillingPdfController;
use App\Http\Controllers\Admin\Senalizacion\ContactController;
use App\Http\Controllers\Admin\Senalizacion\ContentCategoryController;
use App\Http\Controllers\Admin\Senalizacion\ContentController;
use App\Http\Controllers\Admin\Senalizacion\CycleController;
use App\Http\Controllers\Admin\Senalizacion\FileController;
use App\Http\Controllers\Admin\Senalizacion\HomeController;
use App\Http\Controllers\Admin\Senalizacion\PackageController;
use App\Http\Controllers\Admin\Senalizacion\TerminalController;
use Illuminate\Support\Facades\Route;

/*
 * Señalización web (S4): gestión de `signage/` del legacy (BD `admin`, conexión `signage`).
 * Dentro del grupo admin: URL /admin/senalizacion/..., nombre admin.senalizacion.*; los controladores
 * exigen el Gate `senalizacion.manage` (master). {kind}: image (contents*) o video (movie_contents*).
 * Los borrados, que el legacy hacía por GET (?delete_cd=), van por POST.
 */
Route::prefix('senalizacion')->name('senalizacion.')->group(function () {
    // index.php: ホーム
    Route::get('/', [HomeController::class, 'index'])->name('index');

    // list/terminal.php: 端末一覧 (?cd= para editar)
    Route::prefix('terminals')->name('terminals.')->controller(TerminalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('{cd}', 'update')->whereNumber('cd')->name('update');
        Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // list/packages.php: パッケージ一覧; view/packages.php: vista previa (?cd=)
    Route::prefix('packages')->name('packages.')->controller(PackageController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('preview', 'preview')->name('preview');
        Route::post('{cd}', 'update')->whereNumber('cd')->name('update');
        Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // list/cycle.php: ciclos del paquete (?cd= para elegir el ciclo)
    Route::prefix('packages/{packagesCd}/cycles')->whereNumber('packagesCd')->name('cycles.')->controller(CycleController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'store')->name('store');
        Route::post('{cd}', 'update')->whereNumber('cd')->name('update');
    });

    Route::prefix('{kind}')->whereIn('kind', ['image', 'video'])->group(function () {
        // list/contents_category.php, movie_contents_category.php: 画像サイズ別一覧
        Route::prefix('categories')->name('content-categories.')->controller(ContentCategoryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
        });

        // list/contents.php, contents_detail.php, movie_contents*.php
        Route::name('contents.')->controller(ContentController::class)->group(function () {
            Route::get('categories/{categoryCd}/contents', 'index')->whereNumber('categoryCd')->name('index');
            Route::post('categories/{categoryCd}/contents', 'store')->whereNumber('categoryCd')->name('store');
            Route::post('contents/{cd}', 'update')->whereNumber('cd')->name('update');
            Route::post('contents/{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
            Route::get('contents/{cd}/detail', 'detail')->whereNumber('cd')->name('detail');
            Route::post('contents/{cd}/detail', 'update')->whereNumber('cd')->name('detail.update');
        });
    });

    // list/signage.php: ファイル
    Route::prefix('files')->name('files.')->controller(FileController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('{cd}/destroy', 'destroy')->whereNumber('cd')->name('destroy');
    });

    // GoTel (PROVISIONAL, en pausa hasta que Japón confirme): mail/index.php お問い合わせ y pdf/*.php 請求書/領収書
    Route::match(['get', 'post'], 'contact', [ContactController::class, 'form'])->name('contact');
    Route::get('invoice', [BillingPdfController::class, 'invoice'])->name('invoice');
    Route::get('receipt', [BillingPdfController::class, 'receipt'])->name('receipt');
});
