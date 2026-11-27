<?php

/*
 * Menú lateral de 業務支援管理 (category5), copia de system/inc/left_navi5.php.
 * Formato: Doc/contratos/NUCLEO.md, "Menú lateral". Cada bloque sale solo si su tb_base_system_child
 * está activo. Los enlaces se agregan a medida que S5 implementa cada pantalla (las demás siguen
 * en el diseño: Doc/diseno/operaciones-integraciones.md §1).
 */
return [
    'category' => 5,
    'blocks' => [
        [
            'title' => 'スタッフ管理',
            'image' => 'ln_t5_1.gif',
            'ac' => 'ac1',
            'child' => 'スタッフ管理',
            'links' => [
                ['label' => 'スタッフ新規登録', 'url' => '/admin/operaciones/staff/create', 'can' => 'kengen.1024.2'],
                ['label' => 'スタッフ検索・一覧', 'url' => '/admin/operaciones/staff'],
                ['label' => 'スタッフグループ設定', 'url' => '/admin/operaciones/staff-groups', 'can' => 'nucleo.master'],
                ['label' => 'スタッフ検索項目設定', 'url' => '/admin/operaciones/staff-fields'],
                ['label' => 'インフォメーション管理', 'url' => '/admin/operaciones/information'],
            ],
        ],
        [
            // 勤怠管理 (ac2 de left_navi5.php). 勤怠データ一覧 (category5/kintai) es de S5
            // 勤怠一覧 (working/working_calendar.php) es de S2: su enlace va primero cuando tenga ruta
            'title' => '勤怠管理',
            'image' => 'ln_t5_1.gif',
            'ac' => 'ac2',
            'child' => '勤怠管理',
            'links' => [
                // --- W (S2) ---
                // ['label' => '勤怠一覧', 'url' => '/admin/operaciones/working/monthly'], (comentado en el legacy: working_list.php)
                ['label' => '勤怠一覧', 'url' => '/admin/operaciones/working/calendar'],
                // ['label' => '勤怠集計一覧', 'url' => '/admin/operaciones/working/totals'], (comentado en el legacy)
                // 帳票管理: el legacy solo lo mostraba en el servidor 202.218.51.245 (config/working.php)
                ['label' => '帳票管理', 'url' => '/admin/operaciones/working/report', 'can' => 'operaciones.working-report.enabled'],
                // --- fin W ---
                ['label' => '勤怠データ一覧', 'url' => '/admin/operaciones/kintai'],
                ['label' => '端末パスワード変更', 'url' => '/admin/operaciones/kintai/password', 'can' => 'nucleo.master'],
                ['label' => '各種設定', 'url' => '/admin/operaciones/kintai/config', 'can' => 'nucleo.master'],
            ],
        ],
        [
            // En el legacy este bloque está vacío y los turnos cuelgan de 勤怠管理 solo en un servidor concreto
            // (SERVER_ADDR); aquí se agrupan en スケジュール管理, que es su tb_base_system_child.
            'title' => 'スケジュール管理',
            'image' => 'ln_t5_1.gif',
            'ac' => 'ac3',
            'child' => 'スケジュール管理',
            'links' => [
                ['label' => '日別シフト管理', 'url' => '/admin/operaciones/shifts/day'],
                ['label' => '月別シフト管理', 'url' => '/admin/operaciones/shifts/month'],
                ['label' => 'シフト区分管理', 'url' => '/admin/operaciones/shift-kinds', 'can' => 'nucleo.master'],
            ],
        ],
        [
            'title' => '初期設定管理',
            'image' => 'ln_t5_1.gif',
            'ac' => 'ac4',
            'child' => '初期設定管理',
            'links' => [
                // カードリーダーモード設定 y 新規カード管理: ⏸ hasta que Antony confirme 勤怠
                ['label' => '営業日設定', 'url' => '/admin/operaciones/shop-work'],
            ],
        ],
    ],
];
