<?php

use App\Http\Controllers\Admin\Promotions\MailAjaxController;
use App\Http\Controllers\Admin\Promotions\MailController;
use App\Http\Controllers\Admin\Promotions\TemplateCategoryController;
use App\Http\Controllers\Admin\Promotions\TemplateController;
use App\Http\Controllers\Admin\Promotions\TestAddressController;
use Illuminate\Support\Facades\Route;

/*
 * 販売促進管理 (S3): pantallas. Se cargan dentro del grupo admin (prefijo, auth:admin, nombre admin.).
 * La URL repite la del legacy sin .php (system/category3/<carpeta>/<archivo>.php); así las rutas
 * relativas de los JS del legacy (p. ej. `inc/upload.php` de staff_entry.js) caen dentro de la carpeta.
 */
Route::prefix('promocion')->name('promocion.')->group(function () {
    // テンプレート管理 y テストメールアドレス管理 (mail_mag)
    Route::get('mail_mag/mail_template', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('mail_mag/mail_template_entry', [TemplateController::class, 'entry'])->name('templates.entry');
    Route::get('mail_mag/mail_template_category', [TemplateCategoryController::class, 'index'])->name('template-categories.index');
    Route::get('mail_mag/test_mail_address', [TestAddressController::class, 'index'])->name('test-addresses.index');

    // 新規メール配信 (mail_mag) y 新規デコメール配信 (decomail): 発行条件選択 → 内容設定 → 確認 → 登録
    foreach (['mail' => 'mail_mag', 'deco' => 'decomail'] as $type => $folder) {
        Route::prefix($folder)->name($type.'.')->group(function () use ($type) {
            Route::match(['get', 'post'], 'mail_customer_select', [MailController::class, 'select'])->name('select')->defaults('mailType', $type);
            Route::match(['get', 'post'], 'mail_new', [MailController::class, 'create'])->name('create')->defaults('mailType', $type);
            Route::post('mail_new_confirm', [MailController::class, 'confirm'])->name('confirm')->defaults('mailType', $type);
            Route::post('mail_new_send', [MailController::class, 'store'])->name('store')->defaults('mailType', $type);
        });
    }
    Route::get('mail_mag/customer_list', [MailController::class, 'customers'])->name('mail.customers');

    // Imagen adjunta (staff_entry.js: rutas relativas a la carpeta de la pantalla)
    Route::post('mail_mag/inc/upload.php', [MailAjaxController::class, 'upload'])->name('mail.upload');
    Route::get('mail_mag/inc/disp_pic.php', [MailAjaxController::class, 'displayPicture'])->name('mail.picture');
});
