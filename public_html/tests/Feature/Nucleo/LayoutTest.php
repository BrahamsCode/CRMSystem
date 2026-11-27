<?php

use App\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/helpers.php';

// Layout legacy (cabecera, menú lateral, pie), システム選択, permisos cKengen y estáticos

it('muestra システム選択 con los módulos activos en orden, sin 女の子管理', function () {
    seedNucleo();
    loginAs('master', 'secreto1');

    $html = $this->get('/system/select.php')
        ->assertOk()
        ->assertSee('<title>システム選択 - shop</title>', false)
        ->assertSee('■機能選択')
        ->assertSee('<div class="wrap oneColumn">', false)
        ->assertDontSee('<div class="functionSelect">', false) // en システム選択 no hay pestañas de cabecera
        ->assertSee('/system/logout.php', false)
        ->assertDontSee('総合トップ')
        ->getContent();

    expect($html)->toContain('id="functionSelect_1"')
        ->and($html)->toContain('id="functionSelect_0"')
        ->and($html)->toContain('id="functionSelect_12"')
        ->and($html)->not->toContain('id="functionSelect_8"')
        ->and($html)->not->toContain('id="functionSelect_2"')
        ->and(strpos($html, 'functionSelect_1"'))->toBeLessThan(strpos($html, 'functionSelect_3"'))
        ->and($html)->toContain('Copyright&nbsp;&copy;&nbsp;shop');
});

it('oculta 基本情報管理 y SNS管理 a quien no es master ni tiene el permiso', function () {
    seedNucleo();
    DB::table('tb_staff')->insert(['cd' => 2, 'shop_cd' => 1, 'login_id' => 'tanaka', 'login_pass' => 'p', 'disp_flg' => '1', 'tb_staff_group_cd' => 5]);
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => 'スタッフ', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert([['cd' => 1034, 'name' => 'SNS管理', 'hyouji_flg' => '1'], ['cd' => 1001, 'name' => '会員登録', 'hyouji_flg' => '1']]);
    DB::table('tb_kengen')->insert(['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1001, 'etsuran_flg' => '1', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0']);

    loginAs('tanaka', 'p');
    $html = $this->get('/system/select.php')->assertOk()->getContent();

    expect($html)->toContain('id="functionSelect_1"')
        ->and($html)->not->toContain('id="functionSelect_0"')
        ->and($html)->not->toContain('id="functionSelect_12"');
});

it('arma la página de un módulo con cabecera, pestañas, menú lateral y pie', function () {
    seedNucleo();
    loginAs('master', 'secreto1');

    $html = $this->get('/system/category0')
        ->assertOk()
        ->assertSee('<title>基本情報管理トップ -総合業務管理システム-</title>', false)
        ->assertSee('<body id="pTop" class="pageCategory0">', false)
        ->assertSee('<div class="wrap">', false)
        ->assertSee('<p class="loginName0">', false)
        ->assertSee('管理者')
        ->assertSee('<dl class="ac1 accordionBlock">', false)
        ->assertSee('/system/category0/config/features_config.php', false)
        ->assertSee('/system/category0/shop/shop_list.php', false)
        ->assertSee('/system/img/cover0.gif', false)
        ->assertSee('総合トップ')
        ->assertSee("xhr.setRequestHeader('X-CSRF-TOKEN'", false)
        ->getContent();

    // Pestañas: el módulo actual seleccionado; los de mantenimiento ocultos sin soporte ni IP de desarrollo
    expect($html)->toContain('<li class=" selected "><a href="/system/category0/" id="selectCategory0">')
        ->and($html)->toContain('id="selectCategory1"')
        ->and($html)->not->toContain('システム管理')
        ->and($html)->not->toContain('店舗新規登録');
});

it('muestra los menús de mantenimiento con STAGE=docker', function () {
    config(['crm.stage' => 'docker']);
    seedNucleo();
    loginAs('master', 'secreto1');

    $this->get('/system/category0')->assertSee('システム管理')->assertSee('店舗新規登録');
});

it('aplica la matriz cKengen a los Gates <modulo>.<acción> y kengen', function () {
    seedNucleo();
    DB::table('tb_staff_group')->insert([['cd' => 5, 'name' => 'A', 'disp_flg' => '1']]);
    DB::table('tb_kengen_contents')->insert([['cd' => 1001, 'name' => '会員登録', 'hyouji_flg' => '1'], ['cd' => 1033, 'name' => '売上管理', 'hyouji_flg' => '1']]);
    DB::table('tb_kengen')->insert([
        ['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1001, 'etsuran_flg' => '1', 'shinki_flg' => '1', 'shusei_flg' => '0', 'sakujo_flg' => '0'],
        ['cd' => 2, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1033, 'etsuran_flg' => '1', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0'],
    ]);
    DB::table('tb_staff')->insert([
        ['cd' => 2, 'shop_cd' => 1, 'login_id' => 'grupo', 'login_pass' => 'p', 'disp_flg' => '1', 'tb_staff_group_cd' => 5],
        ['cd' => 3, 'shop_cd' => 1, 'login_id' => 'singrupo', 'login_pass' => 'p', 'disp_flg' => '1', 'tb_staff_group_cd' => null],
    ]);

    $grupo = Gate::forUser(Staff::find(2));
    expect($grupo->allows('clientes.view'))->toBeTrue()
        ->and($grupo->allows('clientes.create'))->toBeTrue()
        ->and($grupo->allows('clientes.delete'))->toBeFalse()
        ->and($grupo->allows('ventas.delete'))->toBeFalse()
        ->and($grupo->allows('kengen.1033.1'))->toBeTrue()
        ->and($grupo->allows('kengen', [1033, 4]))->toBeFalse()
        ->and($grupo->allows('kengen', [[1033, 4], [1001, 2]]))->toBeTrue()
        ->and($grupo->allows('nucleo.master'))->toBeFalse();

    // Sin filas en tb_kengen: todos los permisos (fallback abierto del legacy)
    expect(Gate::forUser(Staff::find(3))->allows('ventas.delete'))->toBeTrue();

    // master: todo, salvo las banderas de soporte/IP
    $master = Gate::forUser(Staff::find(1));
    expect($master->allows('ventas.delete'))->toBeTrue()
        ->and($master->allows('kengen', [9999, 4]))->toBeTrue()
        ->and($master->allows('nucleo.maintenance'))->toBeFalse();
});

it('sirve los estáticos del legacy en sus URL absolutas', function () {
    $this->get('/system/css/mainStyle.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8');
    $this->get('/system/js/jquery-1.3.2.min.js')->assertOk();
    $this->get('/system/css/no-existe.css')->assertNotFound();
    $this->get('/system/css/../../index.php.css')->assertNotFound();
});

it('sirve /hp/uploads desde el disco public de Storage', function () {
    Storage::fake('public');
    Storage::disk('public')->put('hp/uploads/c6/banner/a.png', UploadedFile::fake()->image('a.png')->getContent());

    $this->get('/hp/uploads/c6/banner/a.png')->assertOk();
    $this->get('/hp/uploads/c6/banner/nada.png')->assertNotFound();
});
