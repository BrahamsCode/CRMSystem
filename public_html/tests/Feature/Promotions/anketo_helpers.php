<?php

use App\Services\Promotions\AnketoFamily;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';

/*
 * Datos de F6 (アンケート管理 / お客様アンケート管理) para una familia: la encuesta 10 con tres ítems
 * (20 ラジオボタン, 21 チェックボックス, 22 テキスト) y las respuestas de dos socios (grupos 1 y 2).
 */
function anketoSeed(string $family, array $cate = []): void
{
    $f = AnketoFamily::from($family);
    $epoch = fn (string $d) => Carbon::parse($d, 'Asia/Tokyo')->getTimestamp();

    DB::table($f->cateTable())->insert($cate + [
        'cd' => 10, 'name' => '満足度調査', 'comment' => "管理用\nメモ", 'disp_flg' => '1', 'hyouji_flg' => null,
        'start_date' => $epoch('2026-11-01 00:00:00'), 'end_date' => $epoch('2026-12-31 23:59:59'), 'touroku_date' => '1',
    ]);
    DB::table($f->formTable())->insert([
        ['cd' => 20, 'tb_c3_anketo_cate_cd' => 10, 'rank' => 1, 'name' => '満足度', 'form_type' => 'ラジオボタン', 'flds_row' => 3,
            'titles' => '良い<>普通<>悪い', 'data' => '良い<>普通<>悪い', 'hissu' => '1', 'hyouji_flg' => $family === 'anketo2' ? '-1' : '1', 'disp_flg' => '1', 'style_class' => 'checkBtn'],
        ['cd' => 21, 'tb_c3_anketo_cate_cd' => 10, 'rank' => 2, 'name' => '来店理由', 'form_type' => 'チェックボックス', 'flds_row' => 3,
            'titles' => '近い<>安い<>美味しい', 'data' => '近い<>安い<>美味しい', 'hissu' => '', 'hyouji_flg' => '1', 'disp_flg' => '1', 'style_class' => ''],
        ['cd' => 22, 'tb_c3_anketo_cate_cd' => 10, 'rank' => 3, 'name' => 'ご意見', 'form_type' => 'テキストエリア', 'flds_row' => null,
            'titles' => '', 'data' => '', 'hissu' => '', 'hyouji_flg' => '1', 'disp_flg' => '1', 'style_class' => 'writeArea'],
    ]);
    DB::table('tb_customer')->insert([
        ['cd' => 101, 'id' => 'K101', 'name' => '山田太郎', 'email1' => 'taro@example.com', 'kojin_sex' => '男性', 'kojin_birth' => '1990/01/15',
            'mailmaga_flg' => '1', 'mailmaga_email_type' => 'パソコン', 'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => '1', 'tb_shop_cd' => 1],
        ['cd' => 102, 'id' => 'K102', 'name' => '佐藤花子', 'email1' => 'hanako@example.com', 'kojin_sex' => '女性', 'kojin_birth' => '',
            'mailmaga_flg' => '1', 'mailmaga_email_type' => 'AU', 'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => '2', 'tb_shop_cd' => 1],
    ]);
    $sub = fn (int $cd, int $customer, int $form, string $text, string $group, string $date) => [
        'cd' => $cd, 'tb_customer_cd' => $customer, 'tb_c3_anketo_cate_cd' => 10, 'tb_c3_anketo_form_cd' => $form,
        'text_data' => $text, 'category_inner_group' => $group, 'disp_flg' => '1', 'form_type' => '1',
        'touroku_date' => (string) $epoch($date),
    ];
    DB::table($f->subTable())->insert([
        $sub(301, 101, 20, '良い', '1', '2026-11-20 10:00:00'),
        $sub(302, 101, 21, '近い<>美味しい<>', '1', '2026-11-20 10:00:00'),
        $sub(303, 101, 22, 'また<来ます>', '1', '2026-11-20 10:00:00'),
        $sub(304, 102, 20, '普通', '2', '2026-11-25 18:30:00'),
        $sub(305, 102, 21, '美味しい<>', '2', '2026-11-25 18:30:00'),
        $sub(306, 102, 22, '', '2', '2026-11-25 18:30:00'),
    ]);
}
