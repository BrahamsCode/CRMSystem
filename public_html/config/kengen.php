<?php

/*
 * Permisos con nombre `<modulo>.<acción>` → [tb_kengen_contents.cd, función] del legacy (cKengen).
 * Función: 1 閲覧 (view), 2 新規 (create), 3 編集 (update), 4 削除 (delete); en contenidos que no son
 * de datos, 1–4 son 機能1–4. `master` pasa todos (Gate::before). Ver Doc/contratos/NUCLEO.md.
 *
 * Además existe siempre `kengen.<cd>.<función>` (p. ej. @can('kengen.1001.2')).
 * Un módulo que necesite otro nombre lo pide a S1, o lo define en su ServiceProvider con
 * App\Providers\NucleoServiceProvider::defineKengenGate('reservas.view', 1026, 1).
 */
return [

    'gates' => [
        // 顧客管理 (category1): 1001 会員登録, 1002 csv出力, 1003 顧客グループ情報, 1004 フォトアルバム
        'clientes.view' => [1001, 1],
        'clientes.create' => [1001, 2],
        'clientes.update' => [1001, 3],
        'clientes.delete' => [1001, 4],
        'clientes.csv' => [1002, 1],
        'clientes.groups' => [1003, 1],
        'clientes.photos' => [1004, 1],

        // 売上管理 (1033): left_navi1 usa [1033][1] para listados, [2] para alta y [4] para borrar
        'ventas.view' => [1033, 1],
        'ventas.create' => [1033, 2],
        'ventas.update' => [1033, 3],
        'ventas.delete' => [1033, 4],

        // 販売促進管理 (category3)
        'promocion.mailmag' => [1005, 1],
        'promocion.decomail' => [1006, 1],
        'promocion.followmail' => [1007, 1],
        'promocion.automail' => [1008, 1],
        'promocion.coupon' => [1009, 1],
        'promocion.stamp' => [1010, 1],
        'promocion.survey' => [1011, 1],
        'promocion.visits' => [1012, 1],

        // 業務支援管理 (category5)
        'operaciones.staff' => [1024, 1],
        'operaciones.information' => [1025, 1],

        // マイページ / ホームページ (category6)
        'mypage.design' => [1014, 1],
        'mypage.topics' => [1015, 1],
        'mypage.recommend' => [1016, 1],
        'mypage.header' => [1017, 1],
        'mypage.recruit' => [1023, 1],
        'mypage.freepage' => [1032, 1],
        'mypage.mycolle' => [1035, 1],
        'mypage.gallery' => [1036, 1],

        // 予約管理 (category9): 1013 予約管理, 1026 予約システム, 1027 予約項目管理, 1028 スタッフ割当, 1029 予約設定, 1030 機能設定
        'reservas.view' => [1013, 1],
        'reservas.manage' => [1013, 3],

        // 基本情報管理 (category0)
        'base.authority' => [1031, 1],

        // SNS管理: función 1 = configurar (select.php / header.php)
        'sns.configure' => [1034, 1],
    ],

];
