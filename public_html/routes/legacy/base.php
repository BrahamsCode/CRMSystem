<?php

use App\Http\Controllers\Admin\Base\IndexController;
use Illuminate\Support\Facades\Route;

/*
 * 基本情報管理 (category0, S1): URL literales del legacy. Se carga con el guard del personal
 * y el prefijo de nombre `legacy.` (routes/web.php).
 */
Route::name('base.')->group(function () {
    Route::get('system/category0', IndexController::class)->name('index');
});
