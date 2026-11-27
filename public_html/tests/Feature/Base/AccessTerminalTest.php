<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// CRM端末管理 (system/category0/config/slim_list.php, slim_href.php, slim_del_href.php), solo mantenimiento

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('tb_access_mapping')->insert([
        ['cd' => 10, 'subdomain' => 'acs0001.crm-s.net', 'tb_shop_cd' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1', 'ninshou_flg' => '1'],
        ['cd' => 20, 'subdomain' => 'old01.crm-s.net', 'tb_shop_cd' => 1, 'hyouji_flg' => '', 'disp_flg' => '1', 'ninshou_flg' => ''],
        ['cd' => 30, 'subdomain' => 'gone.crm-s.net', 'tb_shop_cd' => 1, 'hyouji_flg' => '1', 'disp_flg' => '9', 'ninshou_flg' => ''],
    ]);
});

it('exige mantenimiento', function () {
    $this->get('/system/category0/config/slim_list.php')->assertForbidden();
    $this->get('/system/category0/config/slim_href.php?def_domain=acs9')->assertForbidden();
});

it('lista los terminales activos y guarda tienda, On/Off y el borrado de las シリアル antiguas', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);

    $this->get('/system/category0/config/slim_list.php')->assertOk()
        ->assertSee('■CRM端末の店舗コード転送マッピング')
        ->assertSeeInOrder(['old01', '使用不可', 'name="tb_access_mapping[0][disp_flg]"', 'acs0001', '使用可能', "delOpenPopWindow('10','acs0001.crm-s.net')"], false)
        ->assertDontSee('gone');

    $this->post('/system/category0/config/slim_list.php', ['act_flg' => 'change_tb_access_mapping', 'tb_access_mapping' => [
        ['cd' => '20', 'tb_shop_cd' => '-1', 'hyouji_flg' => '1', 'disp_flg' => '9'],
        ['cd' => '10', 'tb_shop_cd' => '1', 'hyouji_flg' => ''],
    ]])->assertOk();

    $rows = DB::table('tb_access_mapping')->orderBy('cd')->get()->keyBy('cd');
    expect($rows[20]->tb_shop_cd)->toBe(-1)
        ->and($rows[20]->disp_flg)->toBe('9')
        ->and($rows[10]->hyouji_flg)->toBe('')
        ->and($rows[10]->disp_flg)->toBe('1');
});

it('registra y da de baja terminales solo al volver desde ac.crm-s.jp', function () {
    config(['crm.developer_ips' => ['127.0.0.1']]);

    $this->get('/system/category0/config/slim_href.php?def_domain=acs0002')->assertSee('不正なアクセスと判断し');
    expect(DB::table('tb_access_mapping')->where('subdomain', 'acs0002.crm-s.net')->exists())->toBeFalse();

    $from = ['referer' => 'http://ac.crm-s.jp/support/system/slim/api_slim_update.php'];
    $this->get('/system/category0/config/slim_href.php?def_domain=acs0002', $from)->assertOk()->assertSee('window.parent.location.reload();', false);
    $this->get('/system/category0/config/slim_href.php?def_domain=acs0002', $from)->assertSee('既に登録があるシリアルです。');
    $this->get('/system/category0/config/slim_href.php', $from)->assertSee('端末シリアルの受け取りに失敗しました。');

    $row = DB::table('tb_access_mapping')->where('subdomain', 'acs0002.crm-s.net')->first();
    expect($row->tb_shop_cd)->toBe(1)->and($row->hyouji_flg)->toBe('1');

    $this->get('/system/category0/config/slim_del_href.php?data_cd=10', $from)->assertSee('window.parent.location.reload();', false);
    expect(DB::table('tb_access_mapping')->where('cd', 10)->value('disp_flg'))->toBe('9');
});
