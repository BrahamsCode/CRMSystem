<?php

use App\Http\Controllers\Admin\Promotions\MailAjaxController;
use App\Http\Controllers\Admin\Promotions\MailPastController;
use App\Http\Controllers\Admin\Promotions\TemplateCategoryController;
use App\Http\Controllers\Admin\Promotions\TemplateController;
use App\Http\Controllers\Admin\Promotions\TestAddressController;
use Illuminate\Support\Facades\Route;

/*
 * 販売促進管理 (S3): URL literales del legacy (nombres `legacy.promocion.*`: web.php añade `legacy.`) que llaman los JS por AJAX y los formularios
 * (CONVENCIONES §2). S1 las carga sin prefijo, con `web` y el guard del personal.
 */
Route::prefix('system/category3')->name('promocion.')->group(function () {
    Route::match(['get', 'post'], 'mail_mag/ajax_mail_template.php', [TemplateController::class, 'ajax'])->name('mail_mag.ajax_mail_template');
    Route::post('mail_mag/mail_template_register.php', [TemplateController::class, 'register'])->name('mail_mag.mail_template_register');
    Route::match(['get', 'post'], 'mail_mag/ajax_mail_template_category.php', [TemplateCategoryController::class, 'ajax'])->name('mail_mag.ajax_mail_template_category');
    Route::post('mail_mag/mail_template_category_register.php', [TemplateCategoryController::class, 'register'])->name('mail_mag.mail_template_category_register');
    Route::match(['get', 'post'], 'mail_mag/ajax_test_mail_address_new.php', [TestAddressController::class, 'ajax'])->name('mail_mag.ajax_test_mail_address_new');
    Route::post('mail_mag/test_mail_address_register.php', [TestAddressController::class, 'register'])->name('mail_mag.test_mail_address_register');
    Route::match(['get', 'post'], 'mail_mag/ajax_test_mail_address_staff.php', [TestAddressController::class, 'staff'])->name('mail_mag.ajax_test_mail_address_staff');

    // AJAX de los formularios de envío (inc/)
    Route::get('inc/ajax_get_template_titles.php', [MailAjaxController::class, 'templateTitles'])->name('inc.ajax_get_template_titles');
    Route::get('inc/ajax_get_template_body.php', [MailAjaxController::class, 'templateBody'])->name('inc.ajax_get_template_body');
    Route::match(['get', 'post'], 'inc/ajax_search_coupon.php', [MailAjaxController::class, 'searchCoupon'])->name('inc.ajax_search_coupon');
    Route::match(['get', 'post'], 'inc/ajax_testMailList.php', [MailAjaxController::class, 'testMailList'])->name('inc.ajax_test_mail_list');
    Route::post('inc/ajax_sendTestMail.php', [MailAjaxController::class, 'sendTestMail'])->name('inc.ajax_send_test_mail');
    Route::post('inc/ajax_mail_delete.php', [MailPastController::class, 'destroy'])->name('inc.ajax_mail_delete');
    Route::match(['get', 'post'], 'inc/ajax_reigai_kaiin.php', [MailAjaxController::class, 'exceptions'])->name('inc.ajax_reigai_kaiin');
    // follow/auto: 例外顧客 (reigai_kaiin2.js) y 起動/停止
    Route::match(['get', 'post'], 'inc/ajax_search_kaiin.php', [MailAjaxController::class, 'searchCustomers'])->name('inc.ajax_search_kaiin');
    Route::get('inc/ajax_get_shop_data.php', [MailAjaxController::class, 'shopOptions'])->name('inc.ajax_get_shop_data');
    Route::post('inc/ajax_change_status.php', [MailAjaxController::class, 'changeStatus'])->name('inc.ajax_change_status');

    // 来店履歴管理 y DM発行 (S4): GET de las .php del legacy (menú left_navi3, enlaces entre pantallas) → pantallas nuevas
    foreach (['visit_stats2' => 'stats', 'day_visit_list' => 'day', 'store_visit_list' => 'store-list'] as $file => $name) {
        Route::get("visit/$file.php", [\App\Http\Controllers\Admin\Promotions\VisitStatsController::class, 'legacy'])
            ->name("visit.$file")->defaults('target', "admin.promocion.visit.$name");
    }
    Route::get('visit/touch_config.php', [\App\Http\Controllers\Admin\Promotions\VisitTouchConfigController::class, 'legacy'])->name('visit.touch_config');
    Route::get('dm/customer_list.php', [\App\Http\Controllers\Admin\Promotions\DmController::class, 'legacy'])->name('dm.customer_list');

    // アンケート管理 / お客様アンケート管理 (S4, F6): GET de las .php del menú → pantallas nuevas (con su query) y el AJAX de «選択»
    foreach (['anketo/anketo_cate_list' => 'anketo.index', 'anketo2/anketo_cate_list' => 'anketo2.index', 'anketo2/anketo_cate_new' => 'anketo2.create'] as $file => $target) {
        Route::get("$file.php", [\App\Http\Controllers\Admin\Promotions\AnketoCateController::class, 'legacy'])
            ->name(str_replace('/', '.', $file))->defaults('target', "admin.promocion.$target");
    }
    Route::post('anketo2/ajax_set_anketo.php', [\App\Http\Controllers\Admin\Promotions\AnketoCateController::class, 'setActive'])->name('anketo2.ajax_set_anketo');
});
