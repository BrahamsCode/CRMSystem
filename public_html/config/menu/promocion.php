<?php

use App\Services\Promotions\PromocionMenu;

/*
 * Menú lateral de 販売促進管理 (category3), copia de system/inc/left_navi3.php. Formato: Doc/contratos/NUCLEO.md §6.
 * Las pantallas ya migradas apuntan a /admin/promocion/<carpeta>/<archivo> (sin .php); el resto, a la URL del legacy.
 * Condiciones (Gates `promocion.*.enabled`, PromocionServiceProvider):
 *   app = admin_master.application_use_flg, beacon = beacon_use_flg, ext-shop = isExtShopDomain()
 *   (contents_master mycolle/app_layout_mode = 1), smatopi = smatopi_flg, reminder = tb_base_system 9 u 11 activo.
 */
$p = '/'.App\MyApp::ADMINS_SUBDIR.'/promocion';

return [
    'category' => 3,
    'blocks' => [
        [
            'title' => 'メルマガ管理',
            'title_from' => [PromocionMenu::class, 'mailTitle'],
            'image' => 'ln_t3_1.gif',
            'ac' => 'ac1',
            'child' => 'メルマガ管理',
            'links' => [
                ['label' => 'QRコード情報', 'url' => '/system/category3/mail_mag/base.php'],
                ['label' => 'マイコレ登録用QR', 'url' => '/system/category3/mail_mag/base_mycolle.php', 'can' => 'promocion.app.enabled'],
                ['label' => 'メルマガ用QR', 'url' => '/system/category3/mail_mag/base_mailmaga.php'],
                ['label' => 'ポイント利用QR', 'url' => '/system/category3/mail_mag/base_point.php', 'can' => 'promocion.app-ext-shop.enabled'],
                ['label' => '新規メール配信', 'label_from' => [PromocionMenu::class, 'mailNew'], 'url' => "$p/mail_mag/mail_customer_select", 'can' => 'kengen.1005.2'],
                ['label' => '予定配信メール一覧', 'label_from' => [PromocionMenu::class, 'mailFuture'], 'url' => "$p/mail_mag/mail_future_list", 'can' => 'kengen.1005.1'],
                ['label' => '過去配信メール一覧', 'label_from' => [PromocionMenu::class, 'mailPast'], 'url' => "$p/mail_mag/mail_past", 'can' => 'kengen.1005.1'],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
                ['label' => 'テストメールアドレス管理', 'label_from' => [PromocionMenu::class, 'testAddress'], 'url' => "$p/mail_mag/test_mail_address"],
                ['label' => 'クリックカウンタ', 'url' => "$p/mail_mag/mailmag_analyze"],
            ],
        ],
        [
            'title' => 'マイクーポン管理',
            'image' => 'ln_t3_2.gif',
            'ac' => 'ac7',
            'child' => 'マイクーポン管理',
            'links' => [
                ['label' => 'クーポン新規登録', 'url' => "$p/mycoupon/mycoupon_new", 'can' => 'kengen.1009.2'],
                ['label' => 'クーポン一覧', 'url' => "$p/mycoupon/mycoupon_list", 'can' => 'kengen.1009.1'],
                ['label' => '過去クーポン配信一覧', 'url' => "$p/mycoupon/mail_past", 'can' => 'kengen.1005.2'],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
                ['label' => '使用ランキング', 'url' => "$p/mycoupon/mycoupon_ranking"],
                ['label' => 'クリックカウンタ', 'url' => "$p/mycoupon/mycoupon_analyze"],
                ['label' => 'クーポン利用数確認', 'url' => "$p/mycoupon/mycoupon_stats"],
            ],
        ],
        [
            'title' => 'アンケート管理',
            'image' => 'ln_t3_8.gif',
            'ac' => 'ac8',
            'child' => 'アンケート管理',
            'links' => [
                ['label' => 'アンケート一覧', 'url' => '/system/category3/anketo/anketo_cate_list.php', 'can' => 'kengen.1011.1'],
                ['label' => '過去アンケート配信一覧', 'url' => '/system/category3/anketo/anketo_cate_list.php?which=sended', 'can' => 'kengen.1011.2'],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
                ['label' => 'クリックカウンタ', 'url' => "$p/anketo/anketo_analyze"],
            ],
        ],
        [
            'title' => 'スマトピ連携',
            'image' => 'ln_t3_8.gif',
            'ac' => 'ac14',
            'child' => 'スマトピ連携',
            'can' => 'promocion.smatopi.enabled',
            'links' => [
                ['label' => 'メッセージ作成', 'url' => '/system/category3/smatopi/smatopi_message.php', 'target' => '_blank'],
                ['label' => 'メッセージ送信履歴', 'url' => '/system/category3/smatopi/smatopi_sent_history.php', 'target' => '_blank'],
                ['label' => 'スマトピ管理画面', 'url' => 'https://smatopi.net/login', 'target' => '_blank'],
            ],
        ],
        [
            'title' => 'お客様アンケート管理',
            'image' => 'ln_t3_8.gif',
            'ac' => 'ac109',
            'child' => 'お客様アンケート管理',
            'links' => [
                ['label' => 'お客様アンケート新規登録', 'url' => '/system/category3/anketo2/anketo_cate_new.php'],
                ['label' => 'お客様アンケート一覧', 'url' => '/system/category3/anketo2/anketo_cate_list.php'],
            ],
        ],
        [
            'title' => 'デコメール管理',
            'image' => 'ln_t3_9.gif',
            'ac' => 'ac9',
            'child' => 'デコメール管理',
            'links' => [
                ['label' => '新規デコメール配信', 'url' => "$p/decomail/mail_customer_select", 'can' => 'kengen.1006.2'],
                ['label' => '予定配信デコメール一覧', 'url' => "$p/decomail/mail_future_list", 'can' => 'kengen.1006.1'],
                ['label' => '過去配信メール一覧', 'url' => "$p/decomail/mail_past", 'can' => 'kengen.1006.1'],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
                ['label' => 'クリックカウンタ', 'url' => "$p/decomail/mailmag_analyze"],
            ],
        ],
        [
            'title' => '来店履歴管理',
            'image' => 'ln_t3_4.gif',
            'ac' => 'ac4',
            'child' => '来店履歴管理',
            'links' => [
                // El legacy añade shop_cd=<tienda de la sesión>; visit_stats2 la toma de la sesión si falta
                ['label' => '来店履歴一覧', 'url' => '/system/category3/visit/visit_stats2.php?kikan=0', 'can' => 'kengen.1012.1'],
                ['label' => 'カードリーダ設定', 'url' => '/system/category3/visit/touch_config.php', 'can' => 'kengen.1012.2'],
            ],
        ],
        [
            'title' => '予約管理',
            'image' => 'ln_t3_5.gif',
            'ac' => 'ac5',
            'child' => '予約管理',
            'links' => [
                ['label' => '予約新規登録', 'url' => '/system/category3/reservation/reserve_customer_search.php', 'can' => 'kengen.1026.2'],
                ['label' => '予約・来店一覧', 'url' => '/system/category3/reservation/reserve_calendar.php', 'can' => 'kengen.1026.1'],
                ['label' => '予約カテゴリ登録・変更', 'url' => '/system/category3/reservation/category_entry.php', 'can' => 'kengen.1026.4'],
                ['label' => '予約項目設定', 'url' => '/system/category3/reservation/config.php', 'can' => 'kengen.1026.4'],
                ['label' => '予約機種一覧', 'url' => '/system/category3/reservation/model_reservation.php', 'can' => 'promocion.ext-shop.enabled'],
            ],
        ],
        [
            'title' => 'スタンプ管理',
            'image' => 'ln_t4_4.gif',
            'ac' => 'ac6',
            'child' => 'スタンプ管理',
            'links' => [
                ['label' => 'スタンプデザイン管理', 'url' => '/system/category3/stamp/stamp_pattern.php', 'can' => 'kengen.1010.1'],
                ['label' => 'スタンプ仕様管理', 'url' => '/system/category3/stamp/stamp_config.php', 'can' => 'kengen.1010.2'],
                ['label' => 'スタンプルール一覧', 'url' => '/system/category3/stamp/stamp_rules_list.php', 'can' => 'kengen.1010.3'],
                ['label' => 'クーポンお知らせ機能管理', 'url' => '/system/category3/stamp/oshirase_coupon.php', 'can' => 'kengen.1010.4'],
            ],
        ],
        [
            'title' => 'ポイント管理',
            'ac' => 'ac13',
            'child' => 'ポイント管理',
            'links' => [
                ['label' => 'ポイント仕様管理', 'url' => "$p/point/point_config", 'can' => 'kengen.1037.2'],
                ['label' => 'ポイントルール一覧', 'url' => "$p/point/point_rules_list", 'can' => ['promocion.point-rules.enabled']],
                ['label' => 'ポイント集計', 'url' => "$p/point/point_aggregate", 'can' => 'promocion.beacon.enabled'],
                ['label' => 'ポイント一括付与', 'url' => "$p/point/csv_grant_points", 'can' => 'promocion.app-ext-shop.enabled'],
            ],
        ],
        [
            'title' => 'フォローメール管理',
            'title_from' => [PromocionMenu::class, 'followTitle'],
            'image' => 'ln_t3_10.gif',
            'ac' => 'ac10',
            'child' => 'フォローメール管理',
            'links' => [
                ['label' => 'フォローメール新規作成', 'label_from' => [PromocionMenu::class, 'followNew'], 'url' => "$p/follow/follow_new", 'can' => 'kengen.1007.2'],
                ['label' => 'フォローメール設定一覧', 'label_from' => [PromocionMenu::class, 'followList'], 'url' => "$p/follow/follow_mail_future_list", 'can' => 'kengen.1007.1'],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
            ],
        ],
        [
            'title' => '自動メール管理',
            'title_from' => [PromocionMenu::class, 'autoTitle'],
            'image' => 'ln_t3_11.gif',
            'ac' => 'ac11',
            'child' => '自動メール管理',
            'links' => [
                ['label' => '自動メール新規作成', 'label_from' => [PromocionMenu::class, 'autoNew'], 'url' => "$p/auto/auto_new", 'can' => 'kengen.1008.2'],
                ['label' => '自動メール設定一覧', 'label_from' => [PromocionMenu::class, 'autoList'], 'url' => "$p/auto/auto_mail_future_list", 'can' => 'kengen.1008.1'],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
            ],
        ],
        [
            'title' => 'プッシュ通知',
            'image' => 'ln_t3_12.gif',
            'ac' => 'ac12',
            'child' => 'プッシュ通知',
            'links' => [
                ['label' => 'プッシュ通知新規作成', 'url' => '/system/category3/push/push_customer_select.php'],
                ['label' => '予定通知設定一覧', 'url' => '/system/category3/push/push_follow_list.php?type_flg=scheduled'],
                ['label' => '過去配信内容一覧', 'url' => '/system/category3/push/push_past.php'],
                ['label' => 'フォロー通知新規作成', 'url' => '/system/category3/follow/follow_new.php?kind=followPush'],
                ['label' => 'フォロー通知設定一覧', 'url' => '/system/category3/push/push_follow_list.php?type_flg=follow'],
                ['label' => '自動通知新規作成', 'url' => '/system/category3/auto/auto_new.php?kind=autoPush'],
                ['label' => '自動通知設定一覧', 'url' => '/system/category3/push/push_follow_list.php?type_flg=auto'],
                ['label' => 'プッシュ通知集計', 'url' => '/system/category3/push/push_history.php', 'can' => 'promocion.ext-shop.enabled'],
            ],
        ],
        // Fuera del bucle de hijos en el legacy: van al final
        [
            'title' => 'リマインダメール管理',
            'title_from' => [PromocionMenu::class, 'reminderTitle'],
            'image' => 'ln_t3_110.gif',
            'ac' => 'ac110',
            'can' => 'promocion.reminder.enabled',
            'links' => [
                ['label' => 'リマインダメール新規作成', 'label_from' => [PromocionMenu::class, 'reminderNew'], 'url' => "$p/reminder/reminder_new"],
                ['label' => 'リマインダメール設定一覧', 'label_from' => [PromocionMenu::class, 'reminderList'], 'url' => "$p/reminder/reminder_mail_future_list"],
                ['label' => 'テンプレート管理', 'url' => "$p/mail_mag/mail_template"],
            ],
        ],
        [
            'title' => '予約管理',
            'image' => 'ln_t4_4.gif',
            'ac' => 'ac111',
            'can' => 'promocion.ext-shop.enabled',
            'links' => [
                ['label' => '予約機種一覧', 'url' => '/system/category3/reservation/model_reservation.php'],
            ],
        ],
    ],
];
