<?php

/*
 * Menú lateral de 販売管理 (category2), copia de system/inc/left_navi2.php.
 * Las ventas internas (karute_menu) están en el menú de clientes (config/menu/clientes.php, bloque 売上管理).
 * Formato: Doc/contratos/NUCLEO.md, "Menú lateral".
 */
return [
    'category' => 2,
    'blocks' => [
        [
            'title' => '商品情報管理',
            'image' => 'ln_t2_1.gif',
            'ac' => 'ac1',
            'child' => '商品情報管理',
            'links' => [
                ['label' => '商品管理', 'url' => '/system/category2/item/list.php'],
            ],
        ],
    ],
];
