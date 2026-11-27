<?php

use App\Http\Controllers\Admin\Mypage\ApliFileUploadController;
use Illuminate\Support\Facades\Route;

/*
 * URL literales del legacy que llama el JS de マイページ / マイコレ (S4). S1 las carga sin prefijo de URL,
 * con el guard del personal y el prefijo de nombre `legacy.` (routes/web.php): nombres legacy.mypage.*
 */
Route::name('mypage.')->group(function () {
    Route::post('system/category6/user_hp/ajax_apliFileUpload.php', ApliFileUploadController::class)
        ->name('apli-file-upload');
});
