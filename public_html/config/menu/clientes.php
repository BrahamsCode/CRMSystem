<?php

/*
 * Menú lateral de 顧客管理 (category1), copia de system/inc/left_navi1.php.
 * Formato: Doc/contratos/NUCLEO.md, "Menú lateral". Los bloques salen solo si su tb_base_system_child
 * está activo y en su orden (rank, cd), como el legacy.
 */
return [
    'category' => 1,
    'blocks' => [
        [
            'title' => '顧客管理',
            'image' => 'ln_t1_1.gif',
            'ac' => 'ac1',
            'child' => '顧客管理',
            'links' => [
                ['label' => '顧客新規登録', 'url' => '/system/category1/customer/customer_new.php', 'can' => 'clientes.create'],
                ['label' => '顧客検索', 'url' => '/system/category1/customer/customer_list.php'],
                ['label' => '来店処理', 'url' => '/system/category1/customer/customer_search.php'],
                ['label' => '登録・検索項目設定', 'url' => '/system/category1/customer/form_config.php'],
                ['label' => '顧客情報集計', 'url' => '/system/category1/shukei/shukei_list.php'],
                // isExtShopDomain(): contents_master mycolle/app_layout_mode = '1' (Gate en ClientesServiceProvider)
                ['label' => 'ポイント管理', 'url' => '/system/category1/customer/point_setting.php', 'can' => 'clientes.ext-shop.enabled'],
                ['label' => 'ポイント履歴', 'url' => '/system/category1/customer/point_history.php', 'can' => 'clientes.ext-shop.enabled'],
                ['label' => '顧客グループ設定', 'url' => '/system/category1/customer/customer_group_category_new.php'],
                ['label' => '初回来店動機項目設定', 'url' => '/system/category1/customer/raiten_douki.php'],
                ['label' => '顧客ランク設定', 'url' => '/system/category1/rank/customer_rank_list.php', 'grandchild' => '顧客ランク%'],
                ['label' => '顧客ランク振り分け設定', 'url' => '/system/category1/rank/customer_rank_schedule.php', 'grandchild' => '顧客ランク%'],
                ['label' => '顧客追加情報設定', 'url' => '/system/category1/customer/search_cate_list.php'],
                ['label' => 'CSVインポート', 'url' => '/system/category1/customer/csv_import.php', 'can' => 'nucleo.maintenance'],
            ],
        ],
        [
            'title' => '入退室管理',
            'image' => 'ln_t1_2.gif',
            'ac' => 'ac2',
            'child' => '入退室管理',
            'image_only' => true,
            'links' => [
                ['label' => '入退室一覧', 'url' => '/system/category1/entrance/working_calendar.php'],
            ],
        ],
        [
            'title' => '初期設定管理',
            'image' => 'ln_t5_4.gif',
            'ac' => 'ac3',
            'child' => '初期設定管理',
            'image_only' => true,
            'links' => [
                ['label' => 'カードリーダーモード設定', 'url' => '/system/category1/config/felica_mode.php'],
                ['label' => 'カード管理', 'url' => '/system/category1/config/card_new.php'],
            ],
        ],
        [
            'title' => '売上管理',
            'image' => 'ln_t1_4.gif',
            'ac' => 'ac4',
            'child' => 'カルテ管理',
            'image_only' => true,
            'links' => [
                ['label' => '売上データ作成', 'url' => '/system/category1/karute_menu/karute_new.php', 'can' => 'ventas.create'],
                ['label' => '売上データ削除', 'url' => '/system/category1/karute_menu/karute_delete_list.php', 'can' => 'ventas.delete'],
                ['label' => 'メニュー管理', 'url' => '/system/category1/karute_menu/karute_menu.php', 'can' => 'ventas.create'],
                ['label' => '割引管理', 'url' => '/system/category1/karute_menu/karute_waribiki.php', 'can' => 'ventas.create'],
                ['label' => '経費管理', 'url' => '/system/category1/karute_menu/keihi.php', 'can' => 'ventas.create'],
                ['label' => 'スタッフ別集計', 'url' => '/system/category1/karute_menu/staff_total.php', 'can' => 'ventas.view'],
                ['label' => '日報・週報・月報', 'url' => '/system/category1/karute_menu/report.php', 'can' => 'ventas.view'],
            ],
        ],
    ],
];
