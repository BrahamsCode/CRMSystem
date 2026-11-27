<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// システム管理 (system/category0/config/system.php), solo mantenimiento

const SYSTEM_URL = '/system/category0/config/system.php';

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('hatuban')->insert(['koumoku' => 'contents_master', 'kazu' => 1]);
});

it('exige mantenimiento', function () {
    $this->get(SYSTEM_URL)->assertForbidden();
    $this->post(SYSTEM_URL, ['act_flg' => 'ssl_flg', 'ssl_flg' => '1'])->assertForbidden();
});

it('pinta los bloques sin 初期化 ni エクスポート', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);

    $this->get(SYSTEM_URL)->assertOk()
        ->assertSee('<title>マスターデータ管理 -総合業務管理システム-</title>', false)
        ->assertSee('ver <span style="font-size: 20px;font-weight: bold; color:#EE0000;">2.2.2</span>', false)
        ->assertSee('メニュー表示管理')
        ->assertSee('マイコレアプリ使用フラグ')
        ->assertSee('value="production" id="smatopi_environment_0" checked=\'checked\'', false)
        ->assertDontSee('データベースを初期化する')
        ->assertDontSee('DB構造を同期');
});

it('activa y desactiva módulos por índice como el legacy', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);
    DB::table('tb_base_system_child')->insert([
        ['cd' => 10, 'tb_base_system_cd' => 1, 'system_name' => '顧客情報', 'system_flg' => '1'],
        ['cd' => 30, 'tb_base_system_cd' => 3, 'system_name' => 'クーポン', 'system_flg' => '0'],
    ]);
    DB::table('tb_base_system_grandchild')->insert(['cd' => 5, 'tb_base_system_cd' => 1, 'tb_base_system_child_cd' => 10, 'system_name' => 'ポイント管理', 'system_flg' => '1']);

    // seedNucleo: cd 0, 1, 2, 3, 8, 12 → índices 0..5
    $this->get(SYSTEM_URL)
        ->assertSee('<input type="hidden" name="category0" value="1">', false)
        ->assertSee('name="category1" value="1" checked', false)
        ->assertSee('name="category_cd3" value="3"', false)
        ->assertSee('name="tb_base_system_grandchild_cd0" value="5"', false);

    $this->post(SYSTEM_URL, [
        'act' => 'change_master_cate', 'change_tb_base_system_row' => 6, 'child_max' => 2, 'grandchild_max' => 1,
        'category0' => '1', 'category_cd0' => '0', 'category_cd1' => '1', 'category2' => '1', 'category_cd2' => '2',
        'category3' => '1', 'category_cd3' => '3', 'category_cd4' => '8', 'category_cd5' => '12',
        'tb_base_system_child_cd0' => '10', 'tb_base_system_child_flg1' => '1', 'tb_base_system_child_cd1' => '30',
        'tb_base_system_grandchild_cd0' => '5',
    ])->assertRedirect(SYSTEM_URL . '#menuHyoujiKanri');

    expect(DB::table('tb_base_system')->orderBy('cd')->pluck('system_flg', 'cd')->all())
        ->toBe([0 => '1', 1 => '0', 2 => '1', 3 => '1', 8 => '0', 12 => '0'])
        ->and(DB::table('tb_base_system_child')->orderBy('cd')->pluck('system_flg')->all())->toBe(['0', '1'])
        ->and(DB::table('tb_base_system_grandchild')->value('system_flg'))->toBe('0');
});

it('guarda banderas, カルテ連結, 文言 y valores por tienda', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);

    $this->post(SYSTEM_URL, ['act_flg' => 'base_system_version', 'base_system_version_flg' => '2'])->assertOk();
    $this->post(SYSTEM_URL, ['act_flg' => 'ssl_flg', 'ssl_flg' => '1']);
    $this->post(SYSTEM_URL, ['act_flg' => 'application_use_flg', 'application_use_flg' => '1', 'push_api_host' => '1']);
    $this->post(SYSTEM_URL, ['act_flg' => 'related', 'raiten_flg' => '1', 'point_flg' => '2', 'karte_flg' => ''])->assertContent('カルテcdを入力してください。');
    $this->post(SYSTEM_URL, ['act_flg' => 'related', 'raiten_flg' => '1', 'point_flg' => '2', 'karte_flg' => '7'])->assertRedirect(SYSTEM_URL . '#karteRenketsu');
    $this->post(SYSTEM_URL, ['act_flg' => 'mongon', 'naiyou' => '来店情報'])->assertRedirect(SYSTEM_URL . '#mongonKanri');
    $this->post(SYSTEM_URL, ['act_flg' => 'atokara_disp_flg', 'atokara_disp_flg' => ['1' => '1']]);
    $this->post(SYSTEM_URL, ['act_flg' => 'atokara_disp_flg', 'atokara_disp_flg' => ['1' => '']]);
    $this->post(SYSTEM_URL, ['act_flg' => 'smatopi_environment', 'smatopi_environment' => 'develop']);
    $this->post(SYSTEM_URL, ['act_flg' => 'smatopi_environment', 'smatopi_environment' => 'otro']);

    $admin = DB::table('admin_master')->first();
    expect($admin->base_system_version_flg)->toBe('2')
        ->and($admin->ssl_flg)->toBe(1)
        ->and($admin->application_use_flg)->toBe(1)
        ->and($admin->push_api_host)->toBe('1')
        ->and($admin->related_features)->toBe('1<>2<>7')
        ->and(DB::table('tb_manage_words')->where('cd', 8)->value('naiyou'))->toBe('来店情報')
        ->and(DB::table('contents_master')->where('field', 'atokara_disp_flg')->get(['int_data', 'text_data'])->map(fn ($r) => (array) $r)->all())
        ->toBe([['int_data' => 1, 'text_data' => '']])
        ->and(DB::table('contents_master')->where('category', 'smatopi')->orderBy('cd')->pluck('disp_flg', 'text_data')->all())
        ->toBe(['develop' => '0', 'production' => '1']);

    $this->get(SYSTEM_URL)->assertSee('来店機能')->assertSee('スタンプOn')->assertSee('value="7"', false);
});

it('la マイコレアプリ使用フラグ solo se guarda desde las IP de desarrollo', function () {
    session(['nucleo.support_login' => true]);
    $this->get(SYSTEM_URL)->assertOk()->assertDontSee('マイコレアプリ使用フラグ');
    $this->post(SYSTEM_URL, ['act_flg' => 'application_use_flg', 'application_use_flg' => '1']);

    expect(DB::table('admin_master')->value('application_use_flg'))->toBeNull();
});
