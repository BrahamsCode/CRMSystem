<?php

/*
 * Menú lateral de 端末管理 (category7), copia de system/inc/left_navi7.php.
 * Formato: Doc/contratos/NUCLEO.md, "Menú lateral". 情報端末管理 (kiosk) queda fuera de alcance.
 * Las entradas con URL del legacy (.php) son pantallas aún no migradas (ペーパーサイネージ, ICカード, ビーコン).
 */
return [
    'category' => 7,
    'blocks' => [
        [
            'title' => '電子看板管理',
            'image' => 'ln_t7_1.gif',
            'ac' => 'ac1',
            'child' => '電子看板管理',
            'links' => [
                ['label' => '放映プログラム管理', 'url' => '/admin/terminales/signage-programs'],
                ['label' => 'ディスプレイ管理', 'url' => '/admin/terminales/signage-displays'],
            ],
        ],
        [
            // Solo en la UI CRM2 (admin_master.base_system_version_flg = 2), como el legacy
            'title' => 'ペーパーサイネージ',
            'image' => 'ln_t7_4.gif',
            'ac' => 'ac4',
            'child' => 'ペーパーサイネージ',
            'can' => 'terminales.paper-signage.enabled',
            'links' => [
                ['label' => 'カード管理', 'url' => '/system/category7/card_signage/list.php'],
            ],
        ],
        [
            'title' => 'ICカード管理',
            'image' => 'ln_t7_2.gif',
            'ac' => 'ac2',
            'child' => 'ICカード管理',
            'links' => [
                ['label' => '端末管理', 'url' => '/system/category7/felica/terminal_list.php'],
                ['label' => 'カード管理', 'url' => '/system/category7/felica/card_list.php?sesClear=card'],
            ],
        ],
        [
            'title' => 'ビーコン管理',
            'image' => 'ln_t7_5.gif',
            'ac' => 'ac5',
            'child' => 'ビーコン管理',
            'links' => [
                ['label' => 'ビーコン管理', 'url' => '/system/category7/beacon/beacon_list.php'],
            ],
        ],
    ],
];
