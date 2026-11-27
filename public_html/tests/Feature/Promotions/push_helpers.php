<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';

/*
 * Datos de M5 (プッシュ通知): app activa (aviso a g-api), プッシュ通知 activo y tres socios
 * (101 y 102 con la app; 103 sin registration_id).
 */
function pushSeed(): void
{
    DB::table('admin_master')->where('cd', 1)->update(['domain' => 'shop.crm-s.net', 'application_use_flg' => 1, 'push_api_host' => '1']);
    DB::table('tb_base_system_child')->insert(['cd' => 34, 'tb_base_system_cd' => 3, 'system_name' => 'プッシュ通知', 'system_flg' => '1', 'rank' => 1]);
    DB::table('hatuban')->insert([['koumoku' => 'push_master', 'kazu' => 700], ['koumoku' => 'push_follow_master', 'kazu' => 50]]);
    DB::table('tb_job')->insert([['cd' => 1, 'name' => '会社員', 'rank' => 1, 'disp_flg' => '1'], ['cd' => 2, 'name' => '学生', 'rank' => 2, 'disp_flg' => '1']]);
    $epoch = fn (string $d) => (string) Carbon::parse($d, 'Asia/Tokyo')->getTimestamp();
    DB::table('tb_customer')->insert([
        ['cd' => 101, 'id' => 'K101', 'login_id' => 'taro', 'name' => '山田太郎', 'name_kana' => 'ヤマダタロウ', 'tel' => '0311112222', 'email1' => 'taro@example.com',
            'kojin_sex' => '男性', 'kojin_birth' => '1990/01/15', 'job' => '会社員', 'registration_id' => 'reg-101', 'mailmaga_flg' => '1',
            'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => $epoch('2025-04-01 10:00:00'), 'tb_shop_cd' => 1],
        ['cd' => 102, 'id' => 'K102', 'login_id' => 'hanako', 'name' => '佐藤花子', 'name_kana' => 'サトウハナコ', 'tel' => '0633334444', 'email1' => 'hanako@example.com',
            'kojin_sex' => '女性', 'kojin_birth' => '', 'job' => '', 'registration_id' => 'reg-102', 'mailmaga_flg' => '1',
            'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => $epoch('2025-05-01 10:00:00'), 'tb_shop_cd' => 1],
        ['cd' => 103, 'id' => 'K103', 'login_id' => 'jiro', 'name' => '山田次郎', 'name_kana' => 'ヤマダジロウ', 'tel' => '', 'email1' => '',
            'kojin_sex' => '男性', 'kojin_birth' => '2010/03/03', 'job' => '学生', 'registration_id' => '[]', 'mailmaga_flg' => '1',
            'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => $epoch('2025-06-01 10:00:00'), 'tb_shop_cd' => 1],
    ]);
}

/** Regla de push_follow_master (programado / フォロー / 自動) */
function pushFollow(array $row): void
{
    DB::table('push_follow_master')->insert($row + [
        'tb_shop_cd' => -1, 'title' => '定期のお知らせ', 'message' => serialize(['こんにちは']), 'wherestr' => "WHERE disp_flg = '1'",
        'search_display' => '全員', 'disp_flg' => '1', 'post_point' => null, 'point_expiration_days' => null,
        'send_time' => '10:00', 'send_parameter' => '', 'next_send_date' => null, 'touroku_date' => 1, 'update_date' => 1,
        'pos_search' => '', 'pos_tb_shop_cd' => 0, 'pic_link' => serialize([]),
    ]);
}

function pushUniqid($response): string
{
    preg_match('/name="search_uniqid" value="(\w{16})"/', $response->getContent(), $m);

    return $m[1] ?? '';
}
