<?php

use App\Http\Controllers\Admin\Promotions\CouponController;
use App\Http\Controllers\Admin\Promotions\CouponStatsController;
use App\Http\Controllers\Admin\Promotions\MailAjaxController;
use App\Http\Controllers\Admin\Promotions\MailController;
use App\Http\Controllers\Admin\Promotions\MailPastController;
use App\Http\Controllers\Admin\Promotions\PointConfigController;
use App\Http\Controllers\Admin\Promotions\PointRuleController;
use App\Http\Controllers\Admin\Promotions\PointTemplateController;
use App\Http\Controllers\Admin\Promotions\RuleController;
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
    // フォロー (follow/) y 自動 (auto/): condiciones propias y después el flujo común de envío con mail_type
    // follow|followdeco / auto|autodeco (el formulario decide; la ruta da la familia)
    foreach (['follow', 'auto'] as $type) {
        Route::prefix($type)->name($type.'.')->group(function () use ($type) {
            Route::match(['get', 'post'], $type.'_new', [RuleController::class, $type])->name('rule');
            Route::post($type.'_new_confirm', [RuleController::class, $type.'Confirm'])->name('rule-confirm');
            if ($type === 'auto') {
                Route::match(['get', 'post'], 'auto_mail_customer_select', [MailController::class, 'select'])->name('select')->defaults('mailType', $type);
            }
            Route::post($type.'_mail_new', [MailController::class, 'create'])->name('create')->defaults('mailType', $type);
            Route::post($type.'_mail_new_confirm', [MailController::class, 'confirm'])->name('confirm')->defaults('mailType', $type);
            Route::post($type.'_mail_new_send', [MailController::class, 'store'])->name('store')->defaults('mailType', $type);
            Route::match(['get', 'post'], $type.'_mail_future_list', [MailController::class, 'future'])->name('future')->defaults('mailType', $type);
            Route::match(['get', 'post'], $type.'_mail_future', [MailController::class, 'edit'])->name('edit')->defaults('mailType', $type);
            Route::post($type.'_mail_future_confirm', [MailController::class, 'confirm'])->name('edit-confirm')->defaults('mailType', $type);
            Route::post($type.'_mail_future_send', [MailController::class, 'store'])->name('update')->defaults('mailType', $type);
            Route::post('mail_kaijo', [MailController::class, 'cancel'])->name('cancel')->defaults('mailType', $type);
        });
    }

    // クリックカウンタ (inc/mailmag_graph2.php) por familia: mailmag_type '', deco, coupon, anketo
    foreach (['mail' => ['mail_mag/mailmag_analyze', ''], 'deco' => ['decomail/mailmag_analyze', 'deco'],
        'coupon' => ['mycoupon/mycoupon_analyze', 'coupon'], 'anketo' => ['anketo/anketo_analyze', 'anketo']] as $key => [$uri, $type]) {
        Route::get($uri, [\App\Http\Controllers\Admin\Promotions\ClickCounterController::class, 'show'])->name('clicks.'.$key)->defaults('requestType', $type);
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
        Route::get('mycoupon_ranking', [CouponStatsController::class, 'ranking'])->name('ranking');
        Route::match(['get', 'post'], 'mycoupon_stats', [CouponStatsController::class, 'index'])->name('stats');
        Route::get('mycoupon_stats_detail', [CouponStatsController::class, 'show'])->name('stats.show');

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

    // ポイント仕様管理 (point): una pantalla con un `act` por formulario, popups de plantillas de aviso y AJAX
    Route::prefix('point')->name('points.')->group(function () {
        Route::match(['get', 'post'], 'point_config', [PointConfigController::class, 'edit'])->name('config');
        Route::post('ajax_point_yukoukigen_kakunin', [PointConfigController::class, 'expiryPreview'])->name('expiry-preview');
        Route::get('mail_template/{type}', [PointTemplateController::class, 'edit'])->name('template')->whereIn('type', array_keys(PointTemplateController::TYPES));
        Route::post('mail_template/{type}', [PointTemplateController::class, 'update'])->name('template.update')->whereIn('type', array_keys(PointTemplateController::TYPES));

        // ポイント発行ルール: lista, alta/modificación (mismo formulario), «登録中です» y borrado físico
        Route::match(['get', 'post'], 'point_rules_list', [PointRuleController::class, 'index'])->name('rules.index');
        Route::match(['get', 'post'], 'point_rules_new', [PointRuleController::class, 'form'])->name('rules.create');
        Route::match(['get', 'post'], 'point_rules_update', [PointRuleController::class, 'form'])->name('rules.edit');
        Route::get('point_rules_register', [PointRuleController::class, 'registered'])->name('rules.registered');
        Route::post('point_rules_delete', [PointRuleController::class, 'destroy'])->name('rules.destroy');
    });

    // 来店履歴管理 (visit) y DM発行 (dm) — S4
    Route::prefix('visit')->name('visit.')->group(function () {
        Route::match(['get', 'post'], 'visit_stats2', [\App\Http\Controllers\Admin\Promotions\VisitStatsController::class, 'index'])->name('stats');
        Route::match(['get', 'post'], 'day_visit_list', [\App\Http\Controllers\Admin\Promotions\VisitStatsController::class, 'day'])->name('day');
        Route::match(['get', 'post'], 'store_visit_list', [\App\Http\Controllers\Admin\Promotions\VisitStatsController::class, 'shop'])->name('store-list');
        Route::post('csv_download', [\App\Http\Controllers\Admin\Promotions\VisitCsvController::class, 'visits'])->name('csv');
        Route::post('storecsv_download', [\App\Http\Controllers\Admin\Promotions\VisitCsvController::class, 'shops'])->name('store-csv');
        Route::post('daycsv_download', [\App\Http\Controllers\Admin\Promotions\VisitCsvController::class, 'days'])->name('day-csv');
        Route::get('touch_config', [\App\Http\Controllers\Admin\Promotions\VisitTouchConfigController::class, 'edit'])->name('touch-config');
        Route::post('touch_config_register', [\App\Http\Controllers\Admin\Promotions\VisitTouchConfigController::class, 'update'])->name('touch-config.update');
    });
    Route::prefix('dm')->name('dm.')->group(function () {
        Route::match(['get', 'post'], 'customer_list', [\App\Http\Controllers\Admin\Promotions\DmController::class, 'index'])->name('index');
        Route::post('aterna_a4_12', [\App\Http\Controllers\Admin\Promotions\DmController::class, 'labels'])->name('labels');
    });

    // アンケート管理 (anketo) y お客様アンケート管理 (anketo2) — S4 (F6): un juego de controladores con `family`
    foreach (\App\Services\Promotions\AnketoFamily::FAMILIES as $anketoFamily) {
        Route::prefix($anketoFamily)->name($anketoFamily.'.')->group(function () use ($anketoFamily) {
            $c = \App\Http\Controllers\Admin\Promotions\AnketoCateController::class;
            $f = \App\Http\Controllers\Admin\Promotions\AnketoFormController::class;
            $a = \App\Http\Controllers\Admin\Promotions\AnketoAnswerController::class;
            Route::match(['get', 'post'], 'anketo_cate_list', [$c, 'index'])->name('index')->defaults('family', $anketoFamily);
            Route::match(['get', 'post'], 'anketo_cate_new', [$c, 'create'])->name('create')->defaults('family', $anketoFamily);
            Route::match(['get', 'post'], 'anketo_cate_update', [$c, 'edit'])->name('edit')->defaults('family', $anketoFamily);
            Route::post('anketo_cate_action', [$c, 'action'])->name('action')->defaults('family', $anketoFamily);
            Route::match(['get', 'post'], 'anketo_cate_detail', [$c, 'show'])->name('show')->defaults('family', $anketoFamily);

            Route::match(['get', 'post'], 'anketo_form_list', [$f, 'index'])->name('forms.index')->defaults('family', $anketoFamily);
            Route::post('anketo_form_move', [$f, 'move'])->name('forms.move')->defaults('family', $anketoFamily);
            Route::match(['get', 'post'], 'anketo_form_new', [$f, 'create'])->name('forms.create')->defaults('family', $anketoFamily);
            Route::match(['get', 'post'], 'anketo_form_update', [$f, 'edit'])->name('forms.edit')->defaults('family', $anketoFamily);
            Route::post('anketo_form_delete', [$f, 'destroy'])->name('forms.destroy')->defaults('family', $anketoFamily);
            Route::get('anketo_form_detail', [$f, 'show'])->name('forms.show')->defaults('family', $anketoFamily);

            Route::get('anketo_test', [\App\Http\Controllers\Admin\Promotions\AnketoTestController::class, 'show'])->name('test')->defaults('family', $anketoFamily);
            Route::post('anketo_test', [\App\Http\Controllers\Admin\Promotions\AnketoTestController::class, 'show'])->name('test.store')->defaults('family', $anketoFamily);

            Route::get('anketo_answer_analyze', [$a, 'analyze'])->name('analyze')->defaults('family', $anketoFamily);
            Route::match(['get', 'post'], 'anketo_answer_shousai', [$a, 'texts'])->name('answer')->defaults('family', $anketoFamily);
            Route::post('csv_answer_download', [$a, 'csv'])->name('csv')->defaults('family', $anketoFamily);
        });
    }
    Route::prefix('anketo')->name('anketo.mail.')->controller(\App\Http\Controllers\Admin\Promotions\AnketoMailController::class)->group(function () {
        // アンケート発行: 発行条件選択 → 内容設定 → 確認 → 登録 (motor de envío de S3, mail_type = anketo)
        Route::match(['get', 'post'], 'anketo_mail_customer_select', 'select')->name('select');
        Route::match(['get', 'post'], 'anketo_mail_new', 'create')->name('create');
        Route::post('anketo_mail_new_confirm', 'confirm')->name('confirm');
        Route::post('anketo_mail_new_send', 'store')->name('store');
    });
    Route::prefix('anketo2')->name('anketo2.')->group(function () {
        // お客様アンケート: 回答一覧, 回答詳細 y 返信メール (mail_type = mail)
        Route::match(['get', 'post'], 'anketo_answer_list', [\App\Http\Controllers\Admin\Promotions\AnketoAnswerController::class, 'index'])->name('answers')->defaults('family', 'anketo2');
        Route::get('anketo_answer_detail', [\App\Http\Controllers\Admin\Promotions\AnketoAnswerController::class, 'show'])->name('answers.show')->defaults('family', 'anketo2');
        Route::controller(\App\Http\Controllers\Admin\Promotions\AnketoMailController::class)->name('reply.')->group(function () {
            Route::match(['get', 'post'], 'anketo_reply_customer_select', 'replySelect')->name('select');
            Route::match(['get', 'post'], 'anketo_reply_new', 'replyCreate')->name('create');
            Route::post('anketo_reply_new_confirm', 'replyConfirm')->name('confirm');
            Route::post('anketo_reply_new_send', 'replyStore')->name('store');
        });
    });

    // --- F4 (S2) ---
    // ポイント集計 y ポイント一括付与 (point): pantalla + AJAX (también en la URL literal del legacy) y subida del CSV
    Route::get('point/point_aggregate', [\App\Http\Controllers\Admin\Promotions\PointAggregateController::class, 'index'])->name('points.aggregate');
    Route::post('point/ajax_point_aggregate', [\App\Http\Controllers\Admin\Promotions\PointAggregateController::class, 'data'])->name('points.aggregate.ajax');
    Route::get('point/csv_grant_points', [\App\Http\Controllers\Admin\Promotions\PointGrantController::class, 'index'])->name('points.grant-csv');
    Route::post('point/csv_grant_points', [\App\Http\Controllers\Admin\Promotions\PointGrantController::class, 'store'])->name('points.grant-csv.store');
    // --- fin F4 ---

    // --- F5 (S2) ---
    // スタンプ管理 (stamp): デザイン, 仕様 (+ AJAX de afectados y popups de plantillas), ルール y クーポンお知らせ
    Route::prefix('stamp')->name('stamps.')->group(function () {
        Route::get('stamp_pattern', [\App\Http\Controllers\Admin\Promotions\StampPatternController::class, 'index'])->name('pattern');
        Route::post('stamp_pattern_register', [\App\Http\Controllers\Admin\Promotions\StampPatternController::class, 'update'])->name('pattern.update');

        Route::get('stamp_config', [\App\Http\Controllers\Admin\Promotions\StampConfigController::class, 'edit'])->name('config');
        Route::post('stamp_config_register', [\App\Http\Controllers\Admin\Promotions\StampConfigController::class, 'update'])->name('config.update');
        Route::post('ajax_stamp_shikkou_kakunin', [\App\Http\Controllers\Admin\Promotions\StampConfigController::class, 'expiryPreview'])->name('expiry-preview');
        Route::get('mail_template/{type}', [\App\Http\Controllers\Admin\Promotions\StampTemplateController::class, 'edit'])->name('template')
            ->whereIn('type', array_keys(\App\Http\Controllers\Admin\Promotions\StampTemplateController::TYPES));
        Route::post('mail_template/{type}', [\App\Http\Controllers\Admin\Promotions\StampTemplateController::class, 'update'])->name('template.update')
            ->whereIn('type', array_keys(\App\Http\Controllers\Admin\Promotions\StampTemplateController::TYPES));

        // スタンプ発行ルール: lista, alta/modificación (mismo formulario), «登録中です» y borrado físico
        Route::match(['get', 'post'], 'stamp_rules_list', [\App\Http\Controllers\Admin\Promotions\StampRuleController::class, 'index'])->name('rules.index');
        Route::match(['get', 'post'], 'stamp_rules_new', [\App\Http\Controllers\Admin\Promotions\StampRuleController::class, 'form'])->name('rules.create');
        Route::match(['get', 'post'], 'stamp_rules_update', [\App\Http\Controllers\Admin\Promotions\StampRuleController::class, 'form'])->name('rules.edit');
        Route::get('stamp_rules_register', [\App\Http\Controllers\Admin\Promotions\StampRuleController::class, 'registered'])->name('rules.registered');
        Route::post('stamp_rules_delete', [\App\Http\Controllers\Admin\Promotions\StampRuleController::class, 'destroy'])->name('rules.destroy');

        // クーポンお知らせ機能管理
        Route::get('oshirase_coupon', [\App\Http\Controllers\Admin\Promotions\StampOshiraseController::class, 'index'])->name('oshirase');
        Route::post('ajax_oshirase_coupon', [\App\Http\Controllers\Admin\Promotions\StampOshiraseController::class, 'ajax'])->name('oshirase.ajax');
        Route::post('oshirase_coupon_register', [\App\Http\Controllers\Admin\Promotions\StampOshiraseController::class, 'store'])->name('oshirase.store');
    });
    // --- fin F5 ---

    // QRコード情報 / メールマガジン基本情報 (mail_mag/base*.php) — S4 (M7): una pantalla con ?type=base|mailmaga|mycolle|point
    Route::prefix('mail_mag')->name('base.')->group(function () {
        Route::get('base', [\App\Http\Controllers\Admin\Promotions\QrBaseController::class, 'show'])->name('show');
        Route::get('qr_img', \App\Http\Controllers\Publico\Promotions\QrImageController::class)->name('qr');
        Route::get('base_download', [\App\Http\Controllers\Admin\Promotions\QrBaseController::class, 'download'])->name('download');
        Route::post('qr_print_pdf', [\App\Http\Controllers\Admin\Promotions\QrBaseController::class, 'print'])->name('qr-print');
    });

    // プッシュ通知 (push/) — S4 (M5): 発行条件選択 (motor de S3, mailType = push), 新規作成/修正, 完了, 予定/フォロー/自動の一覧,
    // 過去配信 y プッシュ集計. El AJAX y las .php del menú están en routes/legacy/promocion.php
    Route::prefix('push')->name('push.')->group(function () {
        $push = \App\Http\Controllers\Admin\Promotions\PushController::class;
        Route::match(['get', 'post'], 'push_customer_select', [$push, 'select'])->name('select');
        Route::match(['get', 'post'], 'push_new', [$push, 'create'])->name('create');
        Route::get('push_complete', [$push, 'complete'])->name('complete');
        Route::match(['get', 'post'], 'push_follow_list', [$push, 'followList'])->name('follow-list');
        Route::match(['get', 'post'], 'push_past', [\App\Http\Controllers\Admin\Promotions\PushPastController::class, 'index'])->name('past');
        Route::get('push_past_shousai', [\App\Http\Controllers\Admin\Promotions\PushPastController::class, 'show'])->name('show');
        Route::match(['get', 'post'], 'push_history', [\App\Http\Controllers\Admin\Promotions\PushHistoryController::class, 'index'])->name('history');
        Route::get('push_history_detail', [\App\Http\Controllers\Admin\Promotions\PushHistoryController::class, 'show'])->name('history.show');
    });
});
