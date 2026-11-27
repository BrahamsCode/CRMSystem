<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Nucleo/helpers.php';

/*
 * Tercera pasada (testeo real en :8081, Doc/qa/regresion.md «Promoción (S3)»): un caso por fallo encontrado.
 */

it('redirige los 絵文字 de TinyMCE que guardan las plantillas del legacy (../decomail/js/…) a sus estáticos', function () {
    $this->withoutMiddleware(Authenticate::class);

    // Desde /admin/promocion/mail_mag/mail_template, «../decomail/js/…» resuelve aquí
    $this->get('/admin/promocion/decomail/js/tiny_mce/plugins/emojiau/img/f37e.gif')
        ->assertStatus(301)
        ->assertRedirect(asset('legacy/system/category3/decomail/js/tiny_mce/plugins/emojiau/img/f37e.gif'));
    expect(file_exists(public_path('legacy/system/category3/decomail/js/tiny_mce/plugins/emojiau/img/f37e.gif')))->toBeTrue();

    // Solo el árbol de tiny_mce
    $this->get('/admin/promocion/decomail/js/../../.env')->assertNotFound();
});

it('DM発行 carga myFlexSelect y el datepicker que usa el buscador de clientes', function () {
    seedNucleo();
    loginAs('master', 'secreto1');

    $this->get(route('admin.promocion.dm.index'))
        ->assertOk()
        ->assertSee('legacy/system/plugin/myflexselect/myFlexSelect.js', false)
        ->assertSee('legacy/system/plugin/ui-datepicker/ui.datepicker-ja.js', false);
});

it('con kensu vacío (vuelta de 削除/修正) las listas muestran 20 por página, no 1', function () {
    seedNucleo();
    foreach (range(1, 3) as $i) {
        DB::table('tb_mycoupon_master')->insert(['cd' => $i, 'tb_shop_cd' => -1, 'title' => "QA-S3 クーポン{$i}", 'coupon_kubun' => '', 'limit_flg' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1']);
        DB::table('mailmag_master')->insert(['cd' => $i, 'タイトル' => "QA-S3 お知らせ{$i}", '配信状況' => '2', '配信日' => '2026/11/0'.$i, '配信日時' => '2026/11/0'.$i.' 10:00:00', '登録者' => '1', 'type_flg' => '', 'disp_flg' => '']);
    }
    loginAs('master', 'secreto1');

    $this->get(route('admin.promocion.coupons.index', ['shop_cd' => -1, 'coupon_kubun' => '', 'page' => 1, 'kensu' => '']))
        ->assertOk()->assertSee('QA-S3 クーポン1')->assertSee('QA-S3 クーポン3');
    $this->get(route('admin.promocion.mail.past', ['kensu' => '']))
        ->assertOk()->assertSee('QA-S3 お知らせ1')->assertSee('QA-S3 お知らせ3');
    $this->get(route('admin.promocion.coupons.stats', ['kensu' => '']))->assertOk();
});

it('en local el aviso de push solo va al log; en producción (y pruebas) llama a g-api', function () {
    config(['promocion.push_trigger' => 'log']);
    expect(app(App\Services\Promotions\PushTrigger::class))->toBeInstanceOf(App\Services\Promotions\LogPushTrigger::class);
    Illuminate\Support\Facades\Http::fake();
    app(App\Services\Promotions\PushTrigger::class)->trigger('mailmaga', 1);
    Illuminate\Support\Facades\Http::assertNothingSent();

    config(['promocion.push_trigger' => 'gapi']);
    expect(app(App\Services\Promotions\PushTrigger::class))->toBeInstanceOf(App\Services\Promotions\GApiPushTrigger::class);
});
