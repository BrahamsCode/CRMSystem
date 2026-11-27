<?php

/*
 * Menú lateral de 販売促進管理 (legacy system/inc/left_navi3.php). S1 lo une en el layout.
 *
 * Cada bloque es un <dl class="acN accordionBlock"> del legacy:
 *   system     nombre en tb_base_system_child (el bloque sale si está activo)
 *   title      texto de la cabecera; title_app: el que usa si la app (admin_master.application_use_flg) está activa
 *   items      entradas: label, route (nombre de ruta Laravel) o url (pantalla aún no migrada),
 *              kengen [código, nivel] de cKengen (master siempre las ve)
 * Se va completando submódulo a submódulo; las entradas con `url` apuntan al legacy hasta su migración.
 */
return [
    [
        'class' => 'ac1',
        'system' => 'メルマガ管理',
        'title' => 'メルマガ管理',
        'title_app' => 'お知らせ配信管理',
        'items' => [
            ['label' => 'QRコード情報', 'url' => '/system/category3/mail_mag/base.php'],
            ['label' => 'マイコレ登録用QR', 'url' => '/system/category3/mail_mag/base_mycolle.php'],
            ['label' => 'メルマガ用QR', 'url' => '/system/category3/mail_mag/base_mailmaga.php'],
            ['label' => 'ポイント利用QR', 'url' => '/system/category3/mail_mag/base_point.php'],
            ['label' => '新規お知らせ配信', 'route' => 'admin.promocion.mail.select', 'kengen' => [1005, 2]],
            ['label' => '予定配信お知らせ一覧', 'route' => 'admin.promocion.mail.future', 'kengen' => [1005, 1]],
            ['label' => '過去配信お知らせ一覧', 'route' => 'admin.promocion.mail.past', 'kengen' => [1005, 1]],
            ['label' => 'テンプレート管理', 'route' => 'admin.promocion.templates.index'],
            ['label' => 'テストお知らせアドレス管理', 'route' => 'admin.promocion.test-addresses.index'],
            ['label' => 'クリックカウンタ', 'url' => '/system/category3/mail_mag/mailmag_analyze.php'],
        ],
    ],
    [
        'class' => 'ac9',
        'system' => 'デコメール管理',
        'title' => 'デコメール管理',
        'items' => [
            ['label' => '新規デコメール配信', 'route' => 'admin.promocion.deco.select', 'kengen' => [1006, 2]],
            ['label' => '予定配信デコメール一覧', 'route' => 'admin.promocion.deco.future', 'kengen' => [1006, 1]],
            ['label' => '過去配信メール一覧', 'route' => 'admin.promocion.deco.past', 'kengen' => [1006, 1]],
            ['label' => 'テンプレート管理', 'route' => 'admin.promocion.templates.index'],
            ['label' => 'クリックカウンタ', 'url' => '/system/category3/decomail/mailmag_analyze.php'],
        ],
    ],
    [
        'class' => 'ac7',
        'system' => 'マイクーポン管理',
        'title' => 'マイクーポン管理',
        'items' => [
            ['label' => 'クーポン新規登録', 'route' => 'admin.promocion.coupons.create', 'kengen' => [1009, 2]],
            ['label' => 'クーポン一覧', 'route' => 'admin.promocion.coupons.index', 'kengen' => [1009, 1]],
            ['label' => '過去クーポン配信一覧', 'route' => 'admin.promocion.coupons.mail.past', 'kengen' => [1005, 2]],
            ['label' => 'テンプレート管理', 'route' => 'admin.promocion.templates.index'],
            ['label' => '使用ランキング', 'route' => 'admin.promocion.coupons.ranking'],
            ['label' => 'クリックカウンタ', 'url' => '/system/category3/mycoupon/mycoupon_analyze.php'],
            ['label' => 'クーポン利用数確認', 'route' => 'admin.promocion.coupons.stats'],
        ],
    ],
];
