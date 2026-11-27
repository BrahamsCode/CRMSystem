<?php

use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require_once __DIR__ . '/helpers.php';

// Login del personal contra tb_staff, como system/login.php / logout.php / session.php

it('muestra la pantalla de login del legacy', function () {
    seedNucleo();

    $this->get('/system/login.php')
        ->assertOk()
        ->assertSee('<title>ログイン shop</title>', false)
        ->assertSee('/system/css/mainStyle.css', false)
        ->assertSee('CRM System 管理画面へようこそ')
        ->assertSee('id="admin_id"', false)
        ->assertSee('id="admin_pass"', false)
        ->assertSee('id="logincheck"', false)
        ->assertSee('img_login_exlink_crm.png', false);
});

it('entra con login_id y login_pass, registra el historial y va a システム選択', function () {
    seedNucleo();

    loginAs('master', 'secreto1')->assertRedirect('/system/select.php');

    $this->assertAuthenticatedAs(Staff::find(1), 'admin');
    $fila = DB::table('tb_login_rireki')->first();
    expect($fila->cd)->toBe(1)
        ->and($fila->staff_cd)->toBe(1)
        ->and($fila->staff_name)->toBe('管理者')
        ->and($fila->komento)->toBe('ログイン')
        ->and((int) $fila->update)->toBeGreaterThan(0)
        ->and(DB::table('hatuban')->where('koumoku', 'tb_login_rireki')->value('kazu'))->toBe(2);
});

it('muestra los mensajes de error del legacy', function (string $id, string $pass, string $mensaje) {
    seedNucleo();
    DB::table('tb_staff')->insert(['cd' => 2, 'shop_cd' => 1, 'login_id' => 'baja', 'login_pass' => 'x', 'disp_flg' => '9']);

    loginAs($id, $pass)->assertRedirect('/system/login.php');
    $this->get('/system/login.php')->assertSee($mensaje);
    $this->assertGuest('admin');
})->with([
    'clave incorrecta' => ['master', 'mala', '※パスワードが正しくありません。'],
    'usuario desconocido' => ['nadie', 'x', '※ご入力のユーザーIDは無効です。'],
    'usuario dado de baja' => ['baja', 'x', '※ご入力のユーザーIDは無効です。'],
    'sin usuario' => ['', '', '※ユーザーIDを入力してください。'],
    'sin clave' => ['master', '', '※パスワードを入力してください。'],
]);

it('no acepta las claves internas de soporte escritas en el formulario', function () {
    config(['crm.support.support_master_pass' => 'clave-soporte']);
    seedNucleo();

    loginAs('master', 'clave-soporte')->assertRedirect('/system/login.php');
    $this->get('/system/login.php')->assertSee('※ご入力の情報は無効です。');
});

it('entra como master con extsupport si el servidor de soporte valida la clave', function () {
    config(['crm.support.support_master_pass' => 'clave-soporte', 'crm.support.api_host' => 'soporte.test']);
    Http::fake(['soporte.test/*' => Http::response(['status' => 'ok', 'data' => ['access_level' => 'master']])]);
    seedNucleo();

    loginAs('extsupport', '123456')->assertRedirect('/system/select.php');

    $this->assertAuthenticatedAs(Staff::find(1), 'admin');
    expect(session('nucleo.support_login'))->toBeTrue()
        ->and(session('nucleo.support_master'))->toBeTrue()
        ->and(DB::table('tb_login_rireki')->value('komento'))->toBe('ログイン ワンタイムパスワード: 123456');
});

it('recuerda solo el usuario si se marca la casilla', function () {
    seedNucleo();

    $this->post('/system/login.php', ['admin_id' => 'master', 'admin_pass' => 'secreto1', 'logincheck' => 'check'])
        ->assertCookie('admin_login_id', 'master');
});

it('manda al login sin sesión y a システム選択 con sesión', function () {
    seedNucleo();

    $this->get('/system/select.php')->assertRedirect('/system/login.php');

    loginAs('master', 'secreto1');
    $this->get('/system/login.php')->assertRedirect('/system/select.php');
});

it('cierra la sesión, registra ログアウト y vuelve al login', function () {
    seedNucleo();
    loginAs('master', 'secreto1');

    $this->get('/system/logout.php')->assertRedirect('/system/login.php');

    $this->assertGuest('admin');
    expect(DB::table('tb_login_rireki')->orderBy('cd')->pluck('komento')->all())->toBe(['ログイン', 'ログアウト']);
});

it('expulsa la sesión si cambia la clave del staff, como session.php', function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    $this->get('/system/select.php')->assertOk();

    DB::table('tb_staff')->where('cd', 1)->update(['login_pass' => 'nueva']);
    app('auth')->forgetGuards(); // cada petición real relee el staff; en la prueba el guard lo guarda en memoria

    $this->get('/system/select.php')->assertRedirect('/system/login.php');
    $this->assertGuest('admin');
});

it('deja shop_cd y master_cd en la sesión, como session.php', function () {
    seedNucleo();

    loginAs('master', 'secreto1');

    expect(session('shop_cd'))->toBe(1)->and(session('master_cd'))->toBe(1);
});

it('comparte $siteName con todas las vistas (tb_office cd=1)', function () {
    seedNucleo();

    expect(Illuminate\Support\Facades\Blade::render('{{ $siteName }}'))->toBe('shop');
});
