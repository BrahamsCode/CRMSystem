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
 */
Route::prefix('mypage')->name('mypage.')->group(function () {
    // マイコレ管理 (mobile_mycolle_edit*.php). POST también: el reload_form del legacy se envía a la propia pantalla.
    Route::post('app-images/home-comment', [AppImageController::class, 'updateHomeComment'])
        ->name('app-images.home-comment');
    Route::post('app-images/customer-group', [AppImageController::class, 'updateCustomerGroupDisplay'])
        ->name('app-images.customer-group');
    Route::match(['get', 'post'], 'app-images/{section}', [AppImageController::class, 'edit'])
        ->whereIn('section', AppImageScreen::SECTIONS)
        ->name('app-images.edit');

    // オススメ管理 (user_hp/recommend.php): ?cd= abre la modificación; POST = 表示情報を更新
    Route::prefix('recommendations')->name('recommendations.')->controller(RecommendationController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::post('destroy', 'destroy')->name('destroy');
    });

    // 求人情報 (category6/recruit/recruit_*.php)
    Route::prefix('recruits')->name('recruits.')->controller(RecruitController::class)->group(function () {
        Route::match(['get', 'post'], '/', 'index')->name('index');
        Route::match(['get', 'post'], 'create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('{cd}', 'show')->whereNumber('cd')->name('show');
        Route::get('{cd}/complete', 'complete')->whereNumber('cd')->name('complete');
        Route::match(['get', 'post'], '{cd}/edit', 'edit')->whereNumber('cd')->name('edit');
        Route::post('{cd}/update', 'update')->whereNumber('cd')->name('update');
        Route::post('destroy', 'destroy')->name('destroy');
    });

    // 求人職種管理 (recruit_category*.php): ?cd= abre la modificación
    Route::prefix('recruit-categories')->name('recruit-categories.')->controller(RecruitCategoryController::class)->group(function () {
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
