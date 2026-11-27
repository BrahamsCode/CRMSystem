<?php

use App\Http\Controllers\Admin\Promotions\CouponController;
use App\Http\Controllers\Admin\Promotions\MailAjaxController;
use App\Http\Controllers\Admin\Promotions\MailController;
use App\Http\Controllers\Admin\Promotions\MailPastController;
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

            // 予定配信: lista, 修正, 確認, 登録 y 予定解除
            Route::match(['get', 'post'], 'mail_future_list', [MailController::class, 'future'])->name('future')->defaults('mailType', $type);
            Route::match(['get', 'post'], 'mail_future', [MailController::class, 'edit'])->name('edit')->defaults('mailType', $type);
            Route::post('mail_future_confirm', [MailController::class, 'confirm'])->name('edit-confirm')->defaults('mailType', $type);
            Route::post('mail_future_send', [MailController::class, 'store'])->name('update')->defaults('mailType', $type);
            Route::post('mail_kaijo', [MailController::class, 'cancel'])->name('cancel')->defaults('mailType', $type);

            // 過去配信: lista, 詳細 y 配信先 (+ CSV)
            Route::match(['get', 'post'], 'mail_past', [MailPastController::class, 'index'])->name('past')->defaults('mailType', $type);
            Route::get('mail_past_shousai', [MailPastController::class, 'show'])->name('show')->defaults('mailType', $type);
            Route::match(['get', 'post'], 'mail_sended_list', [MailPastController::class, 'recipients'])->name('recipients')->defaults('mailType', $type);
        });
    }
    Route::get('mail_mag/customer_list', [MailController::class, 'customers'])->name('mail.customers');
    Route::post('mail_mag/csv_shousai_download', [MailPastController::class, 'clicksCsv'])->name('mail.clicks-csv');

    // Imagen adjunta (staff_entry.js: rutas relativas a la carpeta de la pantalla)
    Route::post('mail_mag/inc/upload.php', [MailAjaxController::class, 'upload'])->name('mail.upload');
    Route::get('mail_mag/inc/disp_pic.php', [MailAjaxController::class, 'displayPicture'])->name('mail.picture');

    // マイクーポン管理 (mycoupon)
    Route::prefix('mycoupon')->name('coupons.')->group(function () {
        Route::match(['get', 'post'], 'mycoupon_list', [CouponController::class, 'index'])->name('index');
        Route::match(['get', 'post'], 'mycoupon_new', [CouponController::class, 'form'])->name('create');
        Route::match(['get', 'post'], 'mycoupon_update', [CouponController::class, 'form'])->name('edit');
        Route::get('mycoupon_register', [CouponController::class, 'registered'])->name('registered');
        Route::match(['get', 'post'], 'mycoupon_detail', [CouponController::class, 'show'])->name('show');
        Route::post('mycoupon_delete', [CouponController::class, 'destroy'])->name('destroy');

        // このクーポンを送る: 発行条件選択 → 内容設定 → 確認 → 登録 (inc/mail_*_frm.php con mail_type = mycoupon) y 過去配信
        Route::name('mail.')->group(function () {
            Route::match(['get', 'post'], 'mycoupon_mail_customer_select', [MailController::class, 'select'])->name('select')->defaults('mailType', 'mycoupon');
            Route::match(['get', 'post'], 'mycoupon_mail_new', [MailController::class, 'create'])->name('create')->defaults('mailType', 'mycoupon');
            Route::post('mycoupon_mail_new_confirm', [MailController::class, 'confirm'])->name('confirm')->defaults('mailType', 'mycoupon');
            Route::post('mycoupon_mail_new_send', [MailController::class, 'store'])->name('store')->defaults('mailType', 'mycoupon');
            Route::match(['get', 'post'], 'mail_past', [MailPastController::class, 'index'])->name('past')->defaults('mailType', 'mycoupon');
            Route::get('mail_past_shousai', [MailPastController::class, 'show'])->name('show')->defaults('mailType', 'mycoupon');
            Route::match(['get', 'post'], 'mail_sended_list', [MailPastController::class, 'recipients'])->name('recipients')->defaults('mailType', 'mycoupon');
        });
    });
});
