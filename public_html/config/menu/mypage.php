<?php

/*
 * Menú lateral de マイページ管理 (category6), copia de system/inc/left_navi6.php.
 * Formato: Doc/contratos/NUCLEO.md, "Menú lateral". Solo las entradas que siguen vivas
 * (Doc/diseno/contenido-terminales.md §1.1): トピックス, ヘッダ画像, フリーページ, 携帯HP,
 * フォトギャラリー y la parte de diseño de マイページデザイン管理 se descartaron (solo las leía hp/).
 * ガチャリン管理 no sale: en el legacy su bloque está comentado (se entra por URL).
 */
use App\Models\Mypage\MobileNavi;

return [
    'category' => 6,
    'blocks' => [
        [
            'title' => 'マイページ管理',
            'image' => 'ln_t4_1.gif',
            'ac' => 'ac1',
            'child' => 'マイページ管理',
            'links' => [
                // Solo con la app activa (admin_master.application_use_flg) y permiso 1035
                ['label' => 'マイコレ管理', 'url' => '/admin/mypage/app-images/all', 'can' => 'mypage.mycolle.enabled'],
                ['label' => 'カバーフロー画像設定管理', 'url' => '/admin/mypage/app-images/cover', 'can' => 'mypage.mycolle.enabled'],
                ['label' => 'バナー画像設定管理', 'url' => '/admin/mypage/app-images/banner', 'can' => 'mypage.mycolle.enabled'],
                ['label' => 'HOMEメインイメージ管理', 'url' => '/admin/mypage/app-images/main', 'can' => 'mypage.mycolle.enabled'],
                ['label' => 'サムネイル画像管理', 'url' => '/admin/mypage/app-images/thumbnail', 'can' => 'mypage.mycolle.enabled'],
                // Como el legacy: nombre de tb_mobile_navi (cd 9) + 管理; si está vacío, la etiqueta fija
                ['label' => 'オススメ管理', 'url' => '/admin/mypage/recommendations', 'can' => 'kengen.1016.1',
                    'label_from' => [MobileNavi::class, 'recommendTitle']],
            ],
        ],
        [
            'title' => '求人情報管理',
            'image' => 'ln_t4_3.gif',
            'ac' => 'ac3',
            'child' => '求人情報管理',
            'links' => [
                ['label' => '求人情報新規登録', 'url' => '/admin/mypage/recruits/create', 'can' => 'kengen.1023.2'],
                ['label' => '求人情報一覧', 'url' => '/admin/mypage/recruits', 'can' => 'kengen.1023.1'],
                ['label' => '求人職種管理', 'url' => '/admin/mypage/recruit-categories', 'can' => 'kengen.1023.4'],
            ],
        ],
    ],
];
