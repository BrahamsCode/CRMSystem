<?php

use App\Http\Controllers\Admin\Base\IndexController;
use App\Http\Controllers\Admin\Base\OfficeController;
use App\Http\Controllers\Admin\Base\ShopCategoryController;
use App\Http\Controllers\Admin\Base\ShopController;
use App\Http\Controllers\Admin\Nucleo\ZipController;
use Illuminate\Support\Facades\Route;

/*
 * 基本情報管理 (category0, S1): URL literales del legacy. Se carga con el guard del personal
 * y el prefijo de nombre `legacy.` (routes/web.php). Diseño: Doc/diseno/nucleo-base.md.
 */
Route::name('base.')->group(function () {
    Route::get('system/category0', IndexController::class)->name('index');

    // 店舗情報管理
    Route::prefix('system/category0/shop')->name('shops.')->controller(ShopController::class)->group(function () {
        Route::get('shop_list.php', 'index')->name('index');
        Route::get('shop_detail.php', 'show')->name('show');
        Route::match(['get', 'post'], 'shop_new.php', 'form')->name('form');
        Route::post('shop_new_confirm.php', 'confirm')->name('confirm');
        Route::post('shop_new_register.php', 'register')->name('register');
        Route::get('shop_new_complete.php', 'complete')->name('complete');
        Route::post('shop_delete_register.php', 'delete')->name('delete');
    });
    // 基本情報確認・変更 y 基本情報登録 de primer acceso (mismo controlador, setup = true)
    foreach (['system/category0/config' => 'config.', 'system' => 'setup.'] as $dir => $name) {
        Route::name($name)->controller(OfficeController::class)->group(function () use ($dir, $name) {
            $setup = $name === 'setup.';
            Route::match(['get', 'post'], "{$dir}/config.php", 'form')->name('form')->defaults('setup', $setup);
            Route::post("{$dir}/config_confirm.php", 'confirm')->name('confirm')->defaults('setup', $setup);
            Route::post("{$dir}/config_register.php", 'register')->name('register')->defaults('setup', $setup);
        });
    }

    Route::match(['get', 'post'], 'system/category0/shop/shop_category.php', ShopCategoryController::class)->name('shop-categories');
});

/*
 * Código postal (zip.php) en todas las URL relativas del legacy que lo usan (endpoint común del núcleo)
 */
Route::name('nucleo.zip.')->group(function () {
    foreach (['category0/config', 'category0/shop', 'category1/customer', 'category5/staff', 'inc'] as $i => $dir) {
        Route::match(['get', 'post'], "system/{$dir}/zip.php", ZipController::class)->name((string) $i);
    }
});
