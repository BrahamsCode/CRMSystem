<?php

require_once __DIR__ . '/helpers.php';

use Illuminate\Support\Facades\DB;

// Fallos encontrados en el testeo real del navegador (Doc/qa/regresion.md, tercera pasada, S2 reservas)

beforeEach(fn () => reservasLogin());

it('cierra por URL directa 予約項目管理 (1027), スタッフ割当 (1028) y 予約設定 (1029) a quien no tiene el permiso', function () {
    // Staff no master con 1028 (スタッフ割当) pero sin 1027 ni 1029, como qa_partial
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => '一般', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert([
        ['cd' => 1027, 'name' => '予約項目管理', 'hyouji_flg' => '1'],
        ['cd' => 1028, 'name' => 'スタッフ割当', 'hyouji_flg' => '1'],
        ['cd' => 1029, 'name' => '予約設定', 'hyouji_flg' => '1'],
    ]);
    DB::table('tb_kengen')->insert([
        ['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1027, 'etsuran_flg' => '0', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0'],
        ['cd' => 2, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1028, 'etsuran_flg' => '1', 'shinki_flg' => '1', 'shusei_flg' => '1', 'sakujo_flg' => '1'],
        ['cd' => 3, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1029, 'etsuran_flg' => '0', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0'],
    ]);
    DB::table('tb_staff')->where('cd', 10)->update(['tb_staff_group_cd' => 5]);
    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    $this->get(route('admin.reservas.menus.index'))->assertForbidden();
    $this->get(route('admin.reservas.menu-categories.index'))->assertForbidden();
    $this->post(route('admin.reservas.menu-categories.store'), ['name' => 'QA'])->assertForbidden();
    $this->get(route('admin.reservas.settings.shop'))->assertForbidden();
    $this->get(route('admin.reservas.settings.form'))->assertForbidden();
    $this->post(route('admin.reservas.settings.features.update'))->assertForbidden();
    $this->get(route('admin.reservas.staff-works'))->assertOk();
    expect(DB::table('tb_yoyaku_koumoku_category')->where('name', 'QA')->exists())->toBeFalse();
});

it('master sigue entrando en 予約項目管理, スタッフ割当 y 予約設定', function () {
    $this->get(route('admin.reservas.menus.index'))->assertOk();
    $this->get(route('admin.reservas.menu-categories.index'))->assertOk();
    $this->get(route('admin.reservas.staff-works'))->assertOk();
    $this->get(route('admin.reservas.settings.shop'))->assertOk();
});
