<?php

use App\Http\Controllers\Admin\Promotions\TemplateCategoryController;
use App\Http\Controllers\Admin\Promotions\TemplateController;
use App\Http\Controllers\Admin\Promotions\TestAddressController;
use Illuminate\Support\Facades\Route;

/*
 * 販売促進管理 (S3): pantallas. Se cargan dentro del grupo admin (prefijo, auth:admin, nombre admin.).
 * La URL repite la del legacy sin .php (system/category3/<carpeta>/<archivo>.php).
 */
Route::prefix('promocion')->name('promocion.')->group(function () {
    // テンプレート管理 y テストメールアドレス管理 (mail_mag)
    Route::get('mail_mag/mail_template', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('mail_mag/mail_template_entry', [TemplateController::class, 'entry'])->name('templates.entry');
    Route::get('mail_mag/mail_template_category', [TemplateCategoryController::class, 'index'])->name('template-categories.index');
    Route::get('mail_mag/test_mail_address', [TestAddressController::class, 'index'])->name('test-addresses.index');
});
