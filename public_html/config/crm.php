<?php

/*
 * Configuración propia del CRM (núcleo). Los valores secretos van solo en .env, nunca aquí.
 * Lo que había para las tablas nuevas (menús, campos de clientes…) se quitó con la base legacy:
 * queda en git (commit 8d84c98) como referencia.
 */
return [

    /*
     * Prefecturas (都道府県), en el orden JIS del legacy. Se guardan como texto (大阪府), igual que el legacy.
     */
    'prefectures' => [
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県',
        '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県',
        '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県',
        '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県',
        '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県',
        '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
    ],

    /*
     * Entorno del legacy: STAGE=docker abre los menús de mantenimiento (Common::isDevelopmentAuthorityFromIP).
     * En producción debe quedar vacío.
     */
    'stage' => env('STAGE', ''),

    /* IP de desarrollo (Constant::DEVELOPMENT_IPS del legacy), separadas por comas */
    'developer_ips' => array_filter(array_map('trim', explode(',', (string) env('CRM_DEVELOPER_IPS', '')))),

    /*
     * Login de soporte del proveedor (system/login.php): usuarios `Relation` y `extsupport`
     * verificados contra el servidor externo; entran como `master` con estas claves internas.
     */
    'support' => [
        'relation_login_pass' => env('RELATION_LOGIN_PASS', ''),
        'support_master_pass' => env('SUPPORT_MASTER_PASS', ''),
        'api_host' => env('CRM_SUPPORT_API_HOST', 'b.g-api.net'),
        'timeout' => 15,
    ],

    /* Avisos por correo al soporte del proveedor (vacío = no se envía) */
    'notify' => [
        // shop_new_register.php: alta de tienda
        'shop_created' => env('CRM_NOTIFY_SHOP_CREATED', 'support@glory.ne.jp'),
    ],

    /* Días que se recuerda el usuario en el login (cookie, casilla ログイン情報を保存) */
    'remember_login_id_days' => 90,

];
