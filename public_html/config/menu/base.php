<?php

/*
 * Menú lateral de 基本情報管理 (category0), copia de system/inc/left_navi0.php.
 * Formato de config/menu/<modulo>.php: ver Doc/contratos/NUCLEO.md, "Menú lateral".
 */
return [
    'category' => 0,
    'blocks' => [
        [
            'title' => '基本情報管理',
            'image' => 'ln_t0_1.gif',
            'ac' => 'ac1',
            'links' => [
                ['label' => '基本情報確認・変更', 'url' => '/system/category0/config/config.php'],
                ['label' => '機能設定', 'url' => '/system/category0/config/features_config.php'],
                ['label' => '権限管理', 'url' => '/system/category0/config/authority.php'],
                ['label' => 'ショートカット管理', 'url' => '/system/category0/config/bookmark.php'],
                ['label' => '権限項目管理', 'url' => '/system/category0/config/authority_contents_list.php', 'can' => 'nucleo.maintenance'],
                ['label' => 'システム管理', 'url' => '/system/category0/config/system.php', 'can' => 'nucleo.maintenance'],
                ['label' => 'CRM端末管理', 'url' => '/system/category0/config/slim_list.php', 'can' => 'nucleo.maintenance'],
                // データベース管理 (db_config.php): descartado, editor de BD crudo (Doc/diseno/nucleo-base.md)
                ['label' => 'エリア管理', 'url' => '/system/category0/config/area.php'],
            ],
        ],
        [
            'title' => '店舗情報管理',
            'image' => 'ln_t0_2.gif',
            'ac' => 'ac2',
            'links' => [
                ['label' => '店舗新規登録', 'url' => '/system/category0/shop/shop_new.php', 'can' => 'nucleo.shop-create'],
                ['label' => '店舗検索・一覧', 'url' => '/system/category0/shop/shop_list.php'],
                ['label' => '店舗グループ', 'url' => '/system/category0/shop/shop_category.php'],
            ],
        ],
    ],
];
