<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// エリア管理 (system/category0/config/area*.php)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    DB::table('hatuban')->insert(['koumoku' => 'tb_area', 'kazu' => 1]);
});

it('da de alta áreas raíz e hijas con confirmación y las muestra en el árbol', function () {
    $this->get('/system/category0/config/area.php')->assertOk()->assertSee('■エリア管理')->assertSee('登録されているデータはありません');

    $this->post('/system/category0/config/area_edit.php', ['upflg' => 'new', 'name' => ''])->assertSee('※エリア名は必須入力項目です。');
    $this->post('/system/category0/config/area_edit.php', ['upflg' => 'new', 'name' => '関西', 'comment' => 'x'])
        ->assertSee('■エリア新規登録 確認')->assertSee('value="この内容で登録する"', false);

    $this->post('/system/category0/config/area_register.php', ['upflg' => 'new', 'name' => '関西', 'comment' => ''])->assertSee('URL=area.php?pid=', false);
    $this->post('/system/category0/config/area_register.php', ['upflg' => 'new', 'name' => '大阪', 'pid' => 1]);
    $this->post('/system/category0/config/area_register.php', ['upflg' => 'new', 'name' => '京都', 'pid' => 1]);

    $rows = DB::table('tb_area')->orderBy('cd')->get();
    expect($rows->pluck('name')->all())->toBe(['関西', '大阪', '京都'])
        ->and($rows[1]->pid)->toBe(1)
        ->and($rows->pluck('row_lis')->all())->toBe([1, 1, 2]);

    $this->get('/system/category0/config/area.php')->assertSee('d.add(1, 0,"関西",', false)->assertSee('d.add(2, 1,"大阪",', false)->assertSee('1件ヒット');
    $this->get('/system/category0/config/area.php?pid=1')->assertSee('2件ヒット')->assertSee('京都')->assertSee('上の階層へ');
});

it('ordena, cambia 表示 y borra subiendo a los hijos', function () {
    DB::table('tb_area')->insert([
        ['cd' => 1, 'pid' => null, 'name' => 'A', 'row_lis' => 1, 'flg' => '1', 'disp_flg' => '1'],
        ['cd' => 2, 'pid' => null, 'name' => 'B', 'row_lis' => 2, 'flg' => '1', 'disp_flg' => '1'],
        ['cd' => 3, 'pid' => 2, 'name' => 'B1', 'row_lis' => 1, 'flg' => '1', 'disp_flg' => '1'],
    ]);

    $this->get('/system/category0/config/area.php?thisCd=2&thisRow=2&kind=up')->assertOk();
    expect(DB::table('tb_area')->whereNull('pid')->orderBy('row_lis')->pluck('name')->all())->toBe(['B', 'A']);

    $this->post('/system/category0/config/area.php', ['update' => 'update', 'update_max' => 2, 'cd0' => 2, 'narabi0' => 1, 'flg0' => '1', 'cd1' => 1, 'narabi1' => 2]);
    expect(DB::table('tb_area')->where('cd', 1)->value('flg'))->toBe('');

    $this->get('/system/category0/config/area_confirm.php?del_cd=2')->assertSee('■エリア情報削除 確認')->assertSee('value="削除する"', false);
    $this->post('/system/category0/config/area_register.php', ['upflg' => 'del', 'del_cd' => 2])->assertSee('情報削除中です。');

    expect(DB::table('tb_area')->where('cd', 2)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_area')->where('cd', 3)->value('pid'))->toBeNull();
});

it('edita un área existente', function () {
    DB::table('tb_area')->insert(['cd' => 7, 'pid' => null, 'name' => '関東', 'comment' => 'c', 'row_lis' => 1, 'flg' => '1', 'disp_flg' => '1']);

    $this->get('/system/category0/config/area_edit.php?area_cd=7')->assertSee('■エリア情報更新')->assertSee('value="関東"', false);
    $this->post('/system/category0/config/area_register.php', ['upflg' => 'update', 'area_cd' => 7, 'name' => '首都圏', 'comment' => '']);

    expect(DB::table('tb_area')->where('cd', 7)->value('name'))->toBe('首都圏');
});
