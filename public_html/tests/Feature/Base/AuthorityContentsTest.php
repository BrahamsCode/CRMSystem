<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 権限項目管理 (system/category0/config/authority_contents*.php), solo mantenimiento

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('tb_kengen_contents')->insert([
        ['cd' => 1, 'name' => '顧客名', 'tb_base_system_cd' => 1, 'comment' => "一行目\n<b>二行目</b>", 'hyouji_flg' => '1'],
        ['cd' => 1001, 'name' => '会員登録', 'tb_base_system_cd' => 3, 'comment' => '', 'hyouji_flg' => ''],
    ]);
});

it('exige mantenimiento (IP de desarrollo o soporte)', function () {
    $this->get('/system/category0/config/authority_contents_list.php')->assertForbidden();
    $this->post('/system/category0/config/ajax_authority_contents_register.php', ['type' => '1', 'name' => 'x'])->assertForbidden();
});

it('lista los項目 y actualiza 表示', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);

    $this->get('/system/category0/config/authority_contents_list.php')->assertOk()
        ->assertSee('■権限項目一覧')
        ->assertSeeInOrder(['顧客名', '顧客管理', '一行目<br />', '&lt;b&gt;二行目', '会員登録', '販売促進管理'], false)
        ->assertSee('name="hyouji_flg_array[1]" value="1"  checked="checked"', false);

    $this->post('/system/category0/config/authority_contents_list.php', [
        'update_hyouji_flg' => 'update', 'update_cd' => ['1', '1001'], 'hyouji_flg_array' => ['1001' => '1'],
    ])->assertOk();

    expect(DB::table('tb_kengen_contents')->orderBy('cd')->pluck('hyouji_flg')->all())->toBe(['', '1']);
});

it('da de alta en el rango de su タイプ y edita desde el popup', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);

    $this->get('/system/category0/config/authority_contents.php')->assertOk()->assertSee('▼タイプ選択')->assertSee('この内容で登録する');
    $this->get('/system/category0/config/authority_contents.php?cd=1001')->assertSee('機能タイプ')->assertSee('value="会員登録"', false)->assertSee('この内容で修正する');

    $this->post('/system/category0/config/ajax_authority_contents_register.php', ['type' => '1', 'name' => ' ｶﾅ項目 ', 'tb_base_system_cd' => '1', 'hyouji_flg' => '1'])
        ->assertOk()->assertContent('');
    $this->post('/system/category0/config/ajax_authority_contents_register.php', ['type' => '2', 'name' => '機能', 'tb_base_system_cd' => '3']);
    $this->post('/system/category0/config/ajax_authority_contents_register.php', ['cd' => '1', 'type' => '1', 'name' => '顧客氏名', 'tb_base_system_cd' => '1', 'comment' => 'c']);

    $rows = DB::table('tb_kengen_contents')->orderBy('cd')->get()->keyBy('cd');
    expect($rows->keys()->all())->toBe([1, 2, 1001, 1002])
        ->and($rows[2]->name)->toBe('カナ項目')
        ->and($rows[2]->hyouji_flg)->toBe('1')
        ->and($rows[1]->name)->toBe('顧客氏名')
        ->and($rows[1]->hyouji_flg)->toBe('');
});
