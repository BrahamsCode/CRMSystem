<?php

use App\Mail\Base\ContactMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

require_once __DIR__ . '/helpers.php';

// 要望・お問い合わせ (system/contact/): お問合せ de la cabecera, antes 404

beforeEach(function () {
    seedNucleo();
    DB::table('tb_shop')->insert([
        ['cd' => 2, 'master_cd' => 1, 'kaishamei' => '梅田店', 'disp_flg' => '1'],
        ['cd' => 3, 'master_cd' => 1, 'kaishamei' => '閉店した店', 'disp_flg' => '9'],
    ]);
    DB::table('tb_staff')->insert(['cd' => 2, 'office_cd' => 1, 'shop_cd' => 2, 'login_id' => 'umeda01', 'login_pass' => 'clave1', 'name' => '田中', 'disp_flg' => '1']);
    Mail::fake();
});

/** Datos de un envío completo (los que devuelve la confirmación) */
function contactData(array $override = []): array
{
    return $override + [
        'kaishamei' => 'shop', 'tb_shop_cd' => '2', 'tantou_name' => '田中 翔太', 'tantou_name_kana' => 'タナカ ショウタ',
        'type' => 'お問い合わせ', 'comment' => "一行目\n<b>二行目</b>", 'domain' => 'crm.example.com',
    ];
}

it('abre el formulario desde la お問合せ de la cabecera; master elige entre las tiendas no borradas', function () {
    loginAs('master', 'secreto1');

    $this->get('/system/select.php')->assertSee("/system/contact/?wintype=no_headers", false);
    $this->get('/system/contact/?wintype=no_headers')->assertOk()
        ->assertSee('■要望・お問い合わせ')
        ->assertSee('<select name="tb_shop_cd" id="tb_shop_cd">', false)
        ->assertSee('梅田店')->assertDontSee('閉店した店')
        ->assertSee('id="type1" class="checkBtn" value="要望" checked="checked"', false)
        ->assertSee('value="閉じる"', false)
        ->assertDontSee('id="header"', false);
});

it('al personal no master le pone su tienda fija', function () {
    loginAs('umeda01', 'clave1');

    $this->get('/system/contact/index.php')->assertOk()
        ->assertDontSee('<select name="tb_shop_cd"', false)
        ->assertSee('梅田店<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="2" />', false)
        ->assertDontSee('本店');
});

it('exige el 本文 y muestra la confirmación con los datos', function () {
    loginAs('master', 'secreto1');

    $this->post('/system/contact/index.php', contactData(['comment' => '　', 'act' => 'up']))->assertOk()
        ->assertSee('※本文を入力してください。')->assertSee('送信内容の確認');

    $this->post('/system/contact/index.php', contactData(['act' => 'up']))->assertOk()
        ->assertSee('この内容で送信')->assertSee('内容を修正')
        ->assertSee('梅田店')
        ->assertSee('一行目<br />' . "\n" . '&lt;b&gt;二行目&lt;/b&gt;', false)
        ->assertSee('name="verify"', false);
});

it('envía el correo al proveedor una sola vez por confirmación', function () {
    loginAs('master', 'secreto1');

    $this->post('/system/contact/contact_complete.php', contactData(['verify' => '1795800000']))->assertOk()
        ->assertSee('お問い合わせを送信しました。');

    Mail::assertQueued(ContactMail::class, function (ContactMail $mail) {
        $text = $mail->render();

        return $mail->hasTo('crm@extlink.co.jp')
            && $mail->hasFrom('crm@extlink.co.jp')
            && $mail->hasSubject('【CRM　要望・お問い合わせフォーム】shop 梅田店')
            && str_contains($text, '■店舗名　　　　　：　梅田店')
            && str_contains($text, '■ドメイン：　crm.example.com')
            && str_contains($text, "一行目\n<b>二行目</b>");
    });

    // Recarga o doble clic: mismo verify → no se reenvía
    $this->post('/system/contact/contact_complete.php', contactData(['verify' => '1795800000']))->assertOk()
        ->assertSee('重複エラーが発生しました。')->assertSee('value="戻る"', false);
    Mail::assertQueuedCount(1);
});

it('un no master no puede enviar en nombre de otra tienda', function () {
    loginAs('umeda01', 'clave1');

    $this->post('/system/contact/contact_complete.php', contactData(['tb_shop_cd' => '1', 'verify' => '1']))->assertOk();

    Mail::assertQueued(ContactMail::class, fn (ContactMail $mail) => $mail->data['shop_name'] === '梅田店');
});

it('pide sesión', function () {
    $this->get('/system/contact/')->assertRedirect('/system/login.php');
});

it('sirve el favicon que enlaza el <head> común (antes 404)', function () {
    $this->get('/system/login.php')->assertSee('<link href="/system/img/favicon.ico" rel="shortcut icon" />', false);

    $this->get('/system/img/favicon.ico')->assertOk();
    $this->get('/system/img/favicon.png')->assertOk()->assertHeader('Content-Type', 'image/png');
});
