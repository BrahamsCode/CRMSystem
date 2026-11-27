<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 店舗グループ (system/category0/shop/shop_category.php)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
});

it('da de alta, edita, ordena y borra grupos de tiendas', function () {
    $url = '/system/category0/shop/shop_category.php';

    $this->get($url)->assertOk()->assertSee('■店舗グループ 登録')->assertSee('店舗グループは登録されていません。');

    $this->post($url, ['act' => 'new', 'title' => ' 関西ｸﾞﾙｰﾌﾟ '])->assertOk()->assertSee('登録中です。')->assertSee('URL=shop_category.php?act=', false);
    $this->post($url, ['act' => 'new', 'title' => '関東'])->assertOk();

    $rows = DB::table('tb_shop_category')->orderBy('sort')->get();
    expect($rows->pluck('title')->all())->toBe(['関西グループ', '関東'])
        ->and($rows->pluck('sort')->all())->toBe([1, 2])
        ->and($rows[0]->cd)->toBe(1);

    $this->get($url)->assertSee('2件')->assertSee('関西グループ');
    $this->get($url . '?cd=' . $rows[1]->cd)->assertSee('value="関東"', false)->assertSee('この内容で修正する');

    // ▲ sobre el segundo: pasa a ser el primero
    $this->get($url . '?thisCd=' . $rows[1]->cd . '&thisRow=2&kind=up')->assertOk();
    expect(DB::table('tb_shop_category')->orderBy('sort')->pluck('title')->all())->toBe(['関東', '関西グループ']);

    $this->post($url, ['act' => 'up', 'cd' => $rows[1]->cd, 'title' => '関東エリア']);
    $this->post($url, ['act' => 'del', 'cd' => $rows[0]->cd])->assertSee('削除中です。');

    expect(DB::table('tb_shop_category')->where('cd', $rows[1]->cd)->value('title'))->toBe('関東エリア')
        ->and(DB::table('tb_shop_category')->where('cd', $rows[0]->cd)->value('flg'))->toBe(9);
    $this->get($url)->assertSee('1件')->assertDontSee('関西グループ');
});
