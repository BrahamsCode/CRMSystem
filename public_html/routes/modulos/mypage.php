<?php

use App\Http\Controllers\Admin\Mypage\AppImageController;
use App\Services\Mypage\AppImageScreen;
use Illuminate\Support\Facades\Route;

/*
 * マイページ / マイコレ (S4). Se carga dentro del grupo admin: URL /admin/mypage/..., nombre admin.mypage.*
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
});
