<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// ショートカット管理 (system/category0/config/bookmark.php, bookmark_list.php, ajax_bookmark.php)

/** URL legacy de cada acceso (como bookmark_reset.sql): con ruta propia, movida a /admin y descartada */
const BOOKMARK_URLS = [
    1 => '/system/category1/customer/customer_new.php',
    2 => '/system/category1/customer/customer_list.php',
    3 => '/system/category1/rank/customer_rank_list.php',
    4 => '/system/category5/staff/staff_list.php',
    5 => '/system/category3/mycoupon/mycoupon_list.php',
    6 => '/system/category6/digital_catalog/digital_catalog_list.php',
];

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
        'cd' => $cd, 'tb_base_system_cd' => $base, 'tb_base_system_child_cd' => $child, 'url' => BOOKMARK_URLS[$cd],
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

it('enlaza en la cabecera la pantalla de Laravel y oculta los accesos sin pantalla (descartadas o fuera de alcance)', function () {
    DB::table('tb_base_system_child')->insert(['cd' => 50, 'tb_base_system_cd' => 5, 'system_name' => 'スタッフ管理', 'system_flg' => '1', 'rank' => 1]);
    DB::table('tb_base_system_child')->insert(['cd' => 60, 'tb_base_system_cd' => 6, 'system_name' => 'フォトギャラリー', 'system_flg' => '1', 'rank' => 1]);
    DB::table('tb_bookmark')->whereIn('cd', [4, 5])->update(['bookmark_flg' => '1']);
    DB::table('tb_bookmark')->where('cd', 4)->update(['tb_base_system_cd' => 5, 'tb_base_system_child_cd' => 50]);
    DB::table('tb_bookmark')->insert([
        'cd' => 6, 'tb_base_system_cd' => 6, 'tb_base_system_child_cd' => 60, 'url' => BOOKMARK_URLS[6], 'bookmark_name' => '掲載一覧',
        'title' => 'フォトギャラリー掲載一覧', 'naiyou' => '掲載中の一覧', 'bookmark_flg' => '1', 'disp_flg' => '1', 'rank' => 6, 'user_rank' => 4,
    ]);

    // Antes: href a la URL legacy (404 para las pantallas movidas a /admin)
    $this->get('/system/select.php')->assertOk()
        ->assertSee('<a href="/system/category1/customer/customer_new.php">新規会員登録</a>', false)
        ->assertSee('<a href="/admin/operaciones/staff">売上登録</a>', false)
        ->assertSee('<a href="/admin/promocion/mycoupon/mycoupon_list">クーポン管理</a>', false)
        ->assertDontSee('掲載一覧');

    $this->get('/system/category0/config/bookmark.php')->assertSee('クーポン管理の説明')->assertDontSee('フォトギャラリー掲載一覧');
    $this->get('/system/category0/config/bookmark_list.php')->assertSee('name="bookmark_5"', false)->assertDontSee('name="bookmark_6"', false);
});

it('resuelve cada URL de fábrica de bookmark_reset.sql a una ruta existente o la oculta', function () {
    preg_match_all("#'(/system/category\d+/[^']+)'#", file_get_contents(database_path('sql/nucleo/bookmark_reset.sql')), $m);
    expect(count(array_unique($m[1])))->toBeGreaterThan(50);
    $hidden = [];
    foreach (array_unique($m[1]) as $url) {
        $target = \App\Models\Bookmark::resolveUrl($url);
        if ($target === null) {
            $hidden[] = $url;

            continue;
        }
        expect(\App\Models\Bookmark::hasRoute((string) parse_url($target, PHP_URL_PATH)))->toBeTrue($url . ' → ' . $target);
    }

    // Solo las descartadas por no tener consumidor (category6) y kiosk (touchcation, fuera de alcance)
    expect(collect($hidden)->reject(fn ($u) => preg_match('#^/system/category(6/(user_hp|shop_hp|digital_catalog)|7/kiosk)/#', $u))->all())->toBe([]);
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
