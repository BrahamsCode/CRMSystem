<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 権限管理 (system/category0/config/authority.php + AJAX)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('tb_staff_group')->insert([['cd' => 5, 'name' => '店長', 'rank' => 1, 'disp_flg' => '1'], ['cd' => 6, 'name' => '旧', 'rank' => 2, 'disp_flg' => '9']]);
    DB::table('tb_kengen_contents')->insert([
        ['cd' => 8, 'name' => '名前', 'tb_base_system_cd' => 1, 'hyouji_flg' => '1', 'comment' => null],
        ['cd' => 1001, 'name' => '会員登録', 'tb_base_system_cd' => 1, 'hyouji_flg' => '1', 'comment' => "機能1: 閲覧\n機能2: 新規"],
        ['cd' => 1005, 'name' => 'メルマガ管理', 'tb_base_system_cd' => 2, 'hyouji_flg' => '1', 'comment' => null],
    ]);
    DB::table('tb_staff')->insert(['cd' => 2, 'shop_cd' => 1, 'login_id' => 'tencho', 'login_pass' => 'p', 'name' => '田中', 'disp_flg' => '1', 'tb_staff_group_cd' => 5]);
});

it('muestra los grupos activos y la matriz del grupo por AJAX', function () {
    $this->get('/system/category0/config/authority.php')
        ->assertOk()->assertSee('■権限一覧')->assertSee('店長')->assertDontSee('>旧<', false);

    // 1005 es de un módulo inactivo (販売管理, system_flg 0): no sale
    $this->get('/system/category0/config/ajax_group_authority.php?gcd=5')
        ->assertOk()
        ->assertSee('name="etsuran_flg_8"', false)
        ->assertSee('name="sakujo_flg_1001"', false)
        ->assertDontSee('etsuran_flg_1005')
        ->assertSee('機能1: 閲覧<br />', false)
        ->assertSee('このグループに登録されているスタッフ')
        ->assertSee('田中');
});

it('guarda la matriz por AJAX: inserta con hatuban y luego actualiza', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_kengen', 'kazu' => 100]);

    $this->get('/system/category0/config/ajax_group_authority_register.php?act=update&staff_group=5&etsuran_flg_1001=on&shinki_flg_1001=on&etsuran_flg_8=on')
        ->assertOk()->assertContent('');

    $rows = DB::table('tb_kengen')->where('tb_staff_group_cd', 5)->orderBy('tb_kengen_contents_cd')->get()->keyBy('tb_kengen_contents_cd');
    expect($rows)->toHaveCount(3)
        ->and($rows[1001]->etsuran_flg)->toBe('1')
        ->and($rows[1001]->shusei_flg)->toBe('')
        ->and($rows[8]->etsuran_flg)->toBe('1')
        ->and($rows[1005]->etsuran_flg)->toBe('')
        ->and($rows->pluck('cd')->sort()->values()->all())->toBe([100, 101, 102]);

    $this->get('/system/category0/config/ajax_group_authority_register.php?act=update&staff_group=5&sakujo_flg_1001=on')->assertOk();
    $row = DB::table('tb_kengen')->where('tb_staff_group_cd', 5)->where('tb_kengen_contents_cd', 1001)->first();
    expect($row->etsuran_flg)->toBe('')->and($row->sakujo_flg)->toBe('1')
        ->and(DB::table('tb_kengen')->count())->toBe(3);

    $this->get('/system/category0/config/ajax_group_authority.php?gcd=5')->assertSee('name="sakujo_flg_1001" checked', false);
});
