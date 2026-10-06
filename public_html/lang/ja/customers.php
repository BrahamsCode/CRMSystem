<?php

/**
 * Etiquetas del módulo de clientes en japonés.
 *
 * Los textos salen del sistema legacy (顧客新規登録) para que el personal de
 * tienda encuentre los mismos nombres de siempre.
 */
return [

    'modulo' => '顧客管理',
    'modulo_sub' => '顧客の管理',

    'menu' => [
        'config' => '設定',
        'resumen' => 'トップ',
        'nuevo' => '顧客新規登録',
        'buscar' => '顧客検索',
        'visita' => '来店処理',
        'estadisticas' => '顧客情報集計',
        'campos' => '登録・検索項目設定',
        'grupos' => '顧客グループ設定',
        'motivos' => '初回来店動機項目設定',
        'rangos' => '顧客ランク設定',
        'reglas' => '顧客ランク振り分け設定',
        'info' => '顧客追加情報設定',
        'intervalo' => 'カードリーダ設定',
    ],

    'nuevo' => [
        'titulo' => '顧客新規登録',
        'obligatorios' => '※マークがある項目は、必須項目です。',
        'paso_datos' => '入力',
        'paso_confirmar' => '確認',
        'revisar' => '内容をご確認ください。よろしければ「登録する」を押してください。',
        'errores' => '入力内容をご確認ください：',

        'seccion_cliente' => '顧客情報',
        'seccion_personal' => '個人情報',
        'seccion_promo' => '販促情報',
        'seccion_familia' => '家族情報',
        'seccion_mascota' => 'Mascota',
        'seccion_acceso' => 'マイページ ログイン情報',
        'acceso_desc' => 'お客様がマイページにログインする際の情報です。',

        'sub_direccion' => '住所',
        'sub_contacto' => '連絡先',
        'sub_trabajo' => '勤務先',

        'num_socio' => '会員番号',
        'num_socio_hint' => '登録時に自動で付与されます。',
        'num_gestion' => '管理番号',
        'tienda' => '登録店舗',
        'tipo' => '個人／法人',
        'tipo_persona' => '個人',
        'tipo_empresa' => '法人',

        'apellido_kana' => '姓（カナ）',
        'nombre_kana' => '名（カナ）',
        'apellido' => '姓',
        'nombre' => '名',

        'zip' => '郵便番号',
        'zip_buscar' => '住所検索',
        'zip_buscando' => '検索中…',
        'zip_error' => '該当する住所が見つかりませんでした。',
        'pref' => '都道府県',
        'ciudad_kana' => '市区町村（カナ）',
        'ciudad' => '市区町村',
        'calle' => '番地',
        'edificio_kana' => '建物名（カナ）',
        'edificio' => '建物名',

        'tel' => '電話番号',
        'fax' => 'ファックス番号',
        'mail1' => 'メールアドレス1',
        'mail2' => 'メールアドレス2',
        'referido' => '紹介者',
        'referido_buscar' => '会員を検索',

        'nacimiento' => '生年月日',
        'sexo' => '性別',
        'sangre' => '血液型',
        'movil' => '携帯電話番号',
        'mail_personal' => '個人メールアドレス',
        'ocupacion' => '職業',
        'trabajo_nombre_kana' => '勤務先 名前（カナ）',
        'trabajo_nombre' => '勤務先 名前',
        'rubro' => '業種',
        'trabajo_tel' => '勤務先 電話番号',
        'trabajo_fax' => '勤務先 ファックス番号',

        'newsletter' => 'メルマガ配信有無',
        'tipo_direccion' => 'アドレス区分',
        'tipo_direccion_hint' => '検索結果の内訳に使われます。',
        'recordatorio' => '予約リマインダーメール配信有無',
        'grupo' => '顧客グループ',
        'motivo' => '初回来店動機',
        'notas' => '備考',

        'conyuge' => '配偶者',
        'aniversario' => '結婚記念日',

        'mascota_etiqueta' => '追加フォーム',
        'mascota_nombre' => '名前',
        'mascota_tipo' => '種類',
        'mascota_peso' => '体重',

        'login_id' => 'ログインID',
        'login_id_hint' => '会員番号と同じです。',
        'password' => 'パスワード',

        'cancelar' => 'キャンセル',
        'revisar_btn' => '内容を確認する',
        'volver_editar' => '修正する',
        'confirmar' => 'この内容で登録する',
    ],

    'comun' => [
        'seleccionar' => '選択してください',
        'sin_grupo' => 'グループなし',
        'enviar' => '配信する',
        'no_enviar' => '配信しない',
        'no_entregable' => '配信不能',
        'si' => '有',
        'no' => '無',
        'buscar_cliente' => '顧客を検索…',
        'cerrar_sesion' => 'ログアウト',
        'inicio' => '総合トップ',
        'ruta' => 'パンくず',
        'abrir_menu' => 'メニューを開く',
    ],
];
