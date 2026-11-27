<?php

use App\Http\Controllers\Admin\Mypage\ApliFileUploadController;
use App\Http\Controllers\Admin\Mypage\RecommendationController;
use App\Http\Controllers\Admin\Mypage\RecruitController;
use Illuminate\Support\Facades\Route;

/*
 * URL literales del legacy que llama el JS de マイページ / マイコレ (S4). S1 las carga sin prefijo de URL,
 * con el guard del personal y el prefijo de nombre `legacy.` (routes/web.php): nombres legacy.mypage.*
 */
Route::name('mypage.')->group(function () {
    Route::post('system/category6/user_hp/ajax_apliFileUpload.php', ApliFileUploadController::class)
        ->name('apli-file-upload');

    // AjaxUpload de la imagen de オススメ (user_hp/inc/upload.php) y su vista previa (inc/disp_pic.php)
    Route::post('system/category6/user_hp/inc/upload.php', [RecommendationController::class, 'upload'])
        ->name('recommend-upload');
    Route::get('system/category6/user_hp/inc/disp_pic.php', [RecommendationController::class, 'tempPicture'])
        ->name('recommend-temp-picture');

    // 求人情報: AjaxUpload de las dos imágenes y el desplegable de 職種 por tienda
    Route::post('system/category6/recruit/inc/upload.php', [RecruitController::class, 'upload'])
        ->name('recruit-upload');
    Route::get('system/category6/recruit/inc/disp_pic.php', [RecruitController::class, 'tempPicture'])
        ->name('recruit-temp-picture');
    Route::post('system/category6/recruit/ajax_category.php', [RecruitController::class, 'categoryOptions'])
        ->name('recruit-category-options');
});
