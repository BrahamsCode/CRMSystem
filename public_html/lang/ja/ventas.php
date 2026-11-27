<?php

/*
 * Textos de 販売管理 / ventas internas (S2) que salen de controladores y Form Requests.
 * Copiados del legacy (karute_menu/*.php, category2/item/*.php, src/Validator/Validator.php).
 */
return [
    // karute_new_register.php: die() cuando faltan socio o スタッフ
    'sale_error' => 'エラー発生しました。もう一度やり直してください。',
    'sale_negative' => '・合計の値がマイナスのため登録出来ません。',

    // karute_menu.php / karute_waribiki.php (submitChk)
    'menu_name_required' => '「名称」が未入力です。',
    'menu_kingaku_required' => '「金額」が未入力です。',
    'discount_value_required' => '「割引額」が未入力です。',

    // keihi.php (submitChk)
    'expense_shop_required' => '・店舗を選択してください。',
    'expense_price_required' => '・使用金額を入力してください。',

    // ajax_staffTotal.php / ajax_report.php
    'report_period_required' => '集計期間を入力してください。',
    'report_date_invalid' => '日付：不適切な形式です。',

    // src/Validator/Validator.php (item_edit_validate, Valitron 'ja')
    'product' => [
        'required' => ':attributeを入力してください',
        'select' => ':attributeを正しく選択してください',
        'digits' => ':attributeは数字で入力してください',
        'labels' => [
            'name' => '商品名(正式)',
            'display_name' => '商品名(表示用)',
            'display_title' => '表示見出し',
            'discription' => '一覧用説明',
            'discription_detail' => '詳細用説明',
            'is_exchangeable_of_point' => 'ポイントと交換するかどうか',
            'number_of_points_exchanged' => 'ポイント交換数',
            'available_shop_cds' => '使用可能な店舗',
            'hyouji_flg' => '表示',
        ],
        'upload_failed' => 'アップロードに失敗しました。',
    ],
];
