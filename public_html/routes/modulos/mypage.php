<?php

use App\Http\Controllers\Admin\Mypage\AppImageController;
use App\Http\Controllers\Admin\Mypage\LotteryController;
use App\Http\Controllers\Admin\Mypage\RecommendationController;
use App\Http\Controllers\Admin\Mypage\RecruitCategoryController;
use App\Http\Controllers\Admin\Mypage\RecruitController;
use App\Services\Mypage\AppImageScreen;
use Illuminate\Support\Facades\Route;

/*
 * マイページ / マイコレ (S4). Se carga dentro del grupo admin: URL /admin/mypage/..., nombre admin.mypage.*
 *
 * Las pantallas del legacy se envían a sí mismas (POST) y enlazan con GET: donde hace falta se aceptan
 * los dos métodos.
 *
 * Permisos (cKengen, NUCLEO.md §4): el legacy solo los comprobaba en el menú y ocultando botones; aquí
 * además se cierran en el servidor (403), como clientes y operaciones. Lo que depende del dato enviado
 * (alta o modificación, 表示情報を更新) lo comprueba el controlador o su FormRequest.
 */
Route::prefix('mypage')->name('mypage.')->group(function () {
    // マイコレ管理 (mobile_mycolle_edit*.php), con el permiso 1035 閲覧 de su menú (left_navi6).
    // POST también: el reload_form del legacy se envía a la propia pantalla.
    Route::middleware('can:kengen.1035.1')->group(function () {
        Route::post('app-images/home-comment', [AppImageController::class, 'updateHomeComment'])
            ->name('app-images.home-comment');
        Route::post('app-images/customer-group', [AppImageController::class, 'updateCustomerGroupDisplay'])
            ->name('app-images.customer-group');
        Route::match(['get', 'post'], 'app-images/{section}', [AppImageController::class, 'edit'])
            ->whereIn('section', AppImageScreen::SECTIONS)
            ->name('app-images.edit');
    });

    // オススメ管理 (user_hp/recommend.php): ?cd= abre la modificación; POST = 表示情報を更新
    Route::prefix('recommendations')->name('recommendations.')->controller(RecommendationController::class)->group(function () {
        // 1016 閲覧; el POST de 表示情報を更新 pide además 1016 編集 (en el controlador)
        Route::match(['get', 'post'], '/', 'index')->name('index')->can('kengen.1016.1');
        // act=new pide 1016 新規 y act=up 1016 編集 (RecommendationRequest::authorize)
        Route::post('store', 'store')->name('store');
        Route::post('destroy', 'destroy')->name('destroy')->can('kengen.1016.4');
    });

    // 求人情報 (category6/recruit/recruit_*.php)
    Route::prefix('recruits')->name('recruits.')->controller(RecruitController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index')->can('kengen.1023.1');
        Route::match(['get', 'post'], 'create', 'create')->name('create')->can('kengen.1023.2');
        Route::post('store', 'store')->name('store')->can('kengen.1023.2');
        Route::get('{cd}', 'show')->whereNumber('cd')->name('show')->can('kengen.1023.1');
        // 完了: tras el alta o la modificación (1023 閲覧, 新規 o 編集; en el controlador)
        Route::get('{cd}/complete', 'complete')->whereNumber('cd')->name('complete');
        Route::match(['get', 'post'], '{cd}/edit', 'edit')->whereNumber('cd')->name('edit')->can('kengen.1023.3');
        Route::post('{cd}/update', 'update')->whereNumber('cd')->name('update')->can('kengen.1023.3');
        Route::post('destroy', 'destroy')->name('destroy')->can('kengen.1023.4');
    });

    // 求人職種管理 (recruit_category*.php): ?cd= abre la modificación. Con 1023 削除, como su entrada de menú.
    Route::prefix('recruit-categories')->name('recruit-categories.')->middleware('can:kengen.1023.4')->controller(RecruitCategoryController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::post('destroy', 'destroy')->name('destroy');
    });

    // ガチャリン管理 (kujibiki/kujibiki_config.php): cada formulario manda su act= a la misma pantalla
    Route::prefix('lottery')->name('lottery.')->controller(LotteryController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'update')->name('update');
    });
});
