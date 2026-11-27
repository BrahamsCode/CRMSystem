<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// ショートカット管理 (system/category0/config/bookmark.php, bookmark_list.php, ajax_bookmark.php)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('tb_base_system_child')->insert([
        ['cd' => 10, 'tb_base_system_cd' => 1, 'system_name' => '顧客情報', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 11, 'tb_base_system_cd' => 1, 'system_name' => '顧客ランク', 'system_flg' => '1', 'rank' => 2],
        ['cd' => 20, 'tb_base_system_cd' => 2, 'system_name' => '販売', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 30, 'tb_base_system_cd' => 3, 'system_name' => 'クーポン', 'system_flg' => '1', 'rank' => 1],
    ]);
    $row = fn (int $cd, int $base, int $child, string $title, string $flg, int $rank, ?int $userRank = null) => [
        'cd' => $cd, 'tb_base_system_cd' => $base, 'tb_base_system_child_cd' => $child, 'url' => "/x{$cd}.php",
        'bookmark_name' => $title, 'title' => $title, 'naiyou' => "{$title}の説明", 'bookmark_flg' => $flg,
        'disp_flg' => '1', 'rank' => $rank, 'user_rank' => $userRank,
    ];
    DB::table('tb_bookmark')->insert([
        $row(1, 1, 10, '新規会員登録', '1', 1, 2),
        $row(2, 1, 10, '顧客検索', '1', 2, 1),
        $row(3, 1, 11, '顧客ランク設定', '', 3),
        $row(4, 2, 20, '売上登録', '1', 4, 3),
        $row(5, 3, 30, 'クーポン管理', '', 5),
    ]);
});

it('lista las registradas por user_rank y oculta las de categorías deshabilitadas', function () {
    $this->get('/system/category0/config/bookmark.php')->assertOk()
        ->assertSee('■登録ショートカット一覧')
        ->assertSeeInOrder(['cd="2"', '顧客検索', 'cd="1"', '新規会員登録'], false)
        ->assertDontSee('売上登録の説明')
        ->assertDontSee('クーポン管理');
});

it('guarda el orden por AJAX y deja en null las que no llegan', function () {
    $this->post('/system/category0/config/ajax_bookmark.php', ['bm_1' => 1, 'bm_2' => 2])->assertOk();

    expect(DB::table('tb_bookmark')->orderBy('cd')->pluck('user_rank')->all())->toBe([1, 2, null, null, null]);
});

it('agrupa las funciones por categoría y omite 顧客ランク sin su pantalla habilitada', function () {
    $this->get('/system/category0/config/bookmark_list.php')->assertOk()
        ->assertSee('■登録できる機能一覧')
        ->assertSeeInOrder(['顧客管理', '顧客情報', 'name="bookmark_1" value="1" checked="checked"', '販売促進管理', 'クーポン', 'name="bookmark_5" value="1" /'], false)
        ->assertDontSee('顧客ランク設定')
        ->assertDontSee('売上登録の説明');

    DB::table('tb_base_system_grandchild')->insert(['cd' => 1, 'system_name' => '顧客ランク設定', 'system_flg' => '1']);
    $this->get('/system/category0/config/bookmark_list.php')->assertSee('顧客ランク設定');
});

it('actualiza las casillas (koushin) y restaura los accesos de fábrica (reset)', function () {
    $this->post('/system/category0/config/bookmark_list.php', ['act_flg' => 'koushin', 'bookmark_5' => '1'])->assertOk();
    expect(DB::table('tb_bookmark')->orderBy('cd')->pluck('bookmark_flg')->all())->toBe(['', '', '', '', '1']);

    DB::table('hatuban')->insert(['koumoku' => 'tb_bookmark', 'kazu' => 6]);
    $this->post('/system/category0/config/bookmark_list.php', ['act_flg' => 'reset'])->assertOk();

    expect(DB::table('tb_bookmark')->count())->toBe(63)
        ->and(DB::table('hatuban')->where('koumoku', 'tb_bookmark')->value('kazu'))->toBe(61)
        ->and(DB::table('tb_bookmark')->where('cd', 1)->value('tb_kengen_contents_cd'))->toBe(1001)
        ->and(DB::table('tb_bookmark')->whereNotNull('bookmark_flg')->count())->toBe(0);
});
