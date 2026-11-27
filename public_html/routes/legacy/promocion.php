<?php

use App\Http\Controllers\Admin\Promotions\TemplateCategoryController;
use App\Http\Controllers\Admin\Promotions\TemplateController;
use App\Http\Controllers\Admin\Promotions\TestAddressController;
use Illuminate\Support\Facades\Route;

/*
 * 販売促進管理 (S3): URL literales del legacy que llaman los JS por AJAX y los formularios
 * (CONVENCIONES §2). S1 las carga sin prefijo, con `web` y el guard del personal.
 */
Route::prefix('system/category3')->name('legacy.promocion.')->group(function () {
    Route::match(['get', 'post'], 'mail_mag/ajax_mail_template.php', [TemplateController::class, 'ajax'])->name('mail_mag.ajax_mail_template');
    Route::post('mail_mag/mail_template_register.php', [TemplateController::class, 'register'])->name('mail_mag.mail_template_register');
    Route::match(['get', 'post'], 'mail_mag/ajax_mail_template_category.php', [TemplateCategoryController::class, 'ajax'])->name('mail_mag.ajax_mail_template_category');
    Route::post('mail_mag/mail_template_category_register.php', [TemplateCategoryController::class, 'register'])->name('mail_mag.mail_template_category_register');
    Route::match(['get', 'post'], 'mail_mag/ajax_test_mail_address_new.php', [TestAddressController::class, 'ajax'])->name('mail_mag.ajax_test_mail_address_new');
    Route::post('mail_mag/test_mail_address_register.php', [TestAddressController::class, 'register'])->name('mail_mag.test_mail_address_register');
    Route::match(['get', 'post'], 'mail_mag/ajax_test_mail_address_staff.php', [TestAddressController::class, 'staff'])->name('mail_mag.ajax_test_mail_address_staff');
});
