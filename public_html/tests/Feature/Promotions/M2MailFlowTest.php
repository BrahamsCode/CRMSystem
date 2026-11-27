<?php

use App\Jobs\Promotions\SendMailmagJob;
use App\Mail\Promotions\PromotionMail;
use App\Services\Promotions\MailSender;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/*
 * M2b: 新規メール配信 / 新規デコメール配信 (発行条件選択 → 内容設定 → 確認 → 登録) y sus AJAX.
 * Como M1TemplatesTest: layout mínimo y routes/legacy cargadas a mano mientras S1 no las publique.
 */
beforeEach(function () {
    if (! View::exists('layouts.legacy')) {
        $dir = storage_path('framework/testing/promocion-views/layouts');
        File::ensureDirectoryExists($dir);
        $stub = "<html><head><title>@yield('title')</title>@yield('css')@yield('js')</head><body>@yield('content')</body></html>";
        File::put("$dir/legacy.blade.php", $stub);
        File::put("$dir/legacy-popup.blade.php", $stub);
        View::addLocation(dirname($dir));
    }
    if (! Route::has('legacy.promocion.inc.ajax_reigai_kaiin')) {
        Route::middleware('web')->group(base_path('routes/legacy/promocion.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));
    Mail::fake();

    DB::table('admin_master')->insert(['cd' => 1, 'domain' => 'shop.crm-s.net', 'mail_footer' => '', 'mail_send_filter' => '1', 'application_use_flg' => 0]);
    DB::table('tb_office')->insert(['cd' => 1, 'kaishamei' => '本社']);
    DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1']);
    DB::table('hatuban')->insert([['koumoku' => 'mailmag', 'kazu' => 500], ['koumoku' => 'send_schedule', 'kazu' => 70]]);
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'id' => 'A001', 'name' => '山田 太郎', 'email1' => 'taro@example.com', 'mailmaga_flg' => '1', 'mailmaga_email_type' => 'パソコン', 'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => '2', 'tb_shop_cd' => 1],
        ['cd' => 2, 'id' => 'A002', 'name' => '佐藤 花子', 'email1' => 'hanako@example.com', 'mailmaga_flg' => '1', 'mailmaga_email_type' => 'AU', 'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => '1', 'tb_shop_cd' => 1],
        ['cd' => 3, 'id' => 'A003', 'name' => '退会', 'email1' => 'x@example.com', 'mailmaga_flg' => '1', 'mailmaga_email_type' => 'パソコン', 'touroku_kubun' => '2', 'disp_flg' => '0', 'touroku_date' => '3', 'tb_shop_cd' => 1],
    ]);
});

/** 発行条件選択 con todos los socios activos; devuelve el search_uniqid de la respuesta */
function m2Select($test, string $type = 'mail'): string
{
    $response = $test->post(route("admin.promocion.$type.select"), ['act' => 'up'])->assertOk();
    preg_match('/name="search_uniqid" value="(\w{16})"/', $response->getContent(), $m);

    return $m[1];
}

it('cuenta los destinatarios del filtro en 発行条件選択', function () {
    $this->get(route('admin.promocion.mail.select'))->assertOk()->assertSee('■振分配信条件抽出')->assertDontSee('件見つかりました');

    $this->post(route('admin.promocion.mail.select'), ['act' => 'up'])
        ->assertOk()
        ->assertSee('<span class="text3L attention">2</span>件見つかりました', false)
        ->assertSee('AU：<span class="text3L attention">1</span>件', false)
        ->assertSee(route('admin.promocion.mail.create'), false);
});

it('abre el 内容設定 con etiquetas, plantillas y la fecha actual; sin búsqueda vuelve atrás', function () {
    DB::table('tb_mail_template_category')->insert(['cd' => 1, 'name' => 'お知らせ', 'rank' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1']);
    DB::table('tb_mail_template')->insert(['cd' => 1, 'tb_mail_template_category_cd' => 1, 'title' => 'セール', 'naiyou' => '本文', 'mail_type' => '', 'rank' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1']);

    $this->post(route('admin.promocion.mail.create'), ['search_uniqid' => 'nope'])->assertRedirect();

    $uniqid = m2Select($this);
    $this->post(route('admin.promocion.mail.create'), ['search_uniqid' => $uniqid])
        ->assertOk()
        ->assertSee('■メール内容設定')
        ->assertSee('value="{管理番号}"', false)
        ->assertSee('<option value="1">お知らせ</option>', false)
        ->assertSee('<option value="11" selected>11月</option>', false)
        ->assertSee('「本社からのお知らせ！」', false)
        ->assertSee('id="photo"', false);

    // デコメール: TinyMCE, sin imagen y plantillas de deco (no hay)
    $deco = m2Select($this, 'deco');
    $this->post(route('admin.promocion.deco.create'), ['search_uniqid' => $deco])
        ->assertOk()->assertSee('tiny_mce.js')->assertDontSee('id="photo"', false)->assertDontSee('mailTemplateCategorySelect');
});

it('confirma con vista previa de un cliente del filtro y rechaza horas pasadas', function () {
    $uniqid = m2Select($this);
    $base = ['search_uniqid' => $uniqid, 'title' => '', 'naiyou' => "{名前}様 <b>", 'tourokusya' => '1', 'bikou' => '', 'reigai_kaiin_cds' => '2'];

    $this->post(route('admin.promocion.mail.confirm'), $base + ['send_flg' => '1'])
        ->assertOk()
        ->assertSee('以下の内容で配信してもよろしいですか？')
        ->assertSee('本社からのお知らせ！')
        ->assertSee('山田 太郎様 ＜b＞')
        ->assertSee('【A002】　佐藤 花子', false)
        ->assertSee('mail_magu@shop.crm-s.net' === '' ? '' : 'mail_mag@shop.crm-s.net')
        ->assertSee('即時配信')
        ->assertSee('name="mail_send_verify"', false);

    $this->post(route('admin.promocion.mail.confirm'), $base + ['send_flg' => '0', 'nen' => '2026', 'tuki' => '11', 'hi' => '26', 'jikan' => '09', 'fun' => '55'])
        ->assertOk()->assertSee('時間指定に過去を設定することはできません。')->assertDontSee('name="mail_send_verify"', false);

    $this->post(route('admin.promocion.mail.confirm'), array_merge($base, ['naiyou' => '', 'send_flg' => '1']))
        ->assertOk()->assertSee('メール内容を入力して下さい。');
});

it('registra el envío inmediato con 振分配信 enviable y no lo duplica al recargar', function () {
    Bus::fake();
    $uniqid = m2Select($this);
    $form = ['search_uniqid' => $uniqid, 'title' => '本社からのお知らせ！', 'naiyou' => '{名前}様', 'tourokusya' => '1', 'bikou' => 'メモ',
        'reigai_kaiin_cds' => '2', 'send_flg' => '1', 'jikan' => '10', 'fun' => '00', 'mail_send_verify' => '123'];

    $this->post(route('admin.promocion.mail.store'), $form)->assertOk()->assertSee('以下の内容で配信いたしました。')->assertSee('総数：1');

    $mail = DB::table('mailmag_master')->where('cd', 500)->first();
    expect($mail->{'配信状況'})->toBe('2')
        ->and($mail->type_flg)->toBe('')
        ->and($mail->{'店id'})->toBe('-1')
        ->and($mail->{'配信日時'})->toBe('2026/11/26 10:00:00')
        ->and($mail->{'振分条件'})->toBe('2')
        ->and($mail->{'振分配信'})->toStartWith('WHERE cd IN (');
    Bus::assertDispatched(SendMailmagJob::class, fn ($job) => $job->mailmagCd === 500);

    // Recarga con el mismo token: no registra otro
    $this->post(route('admin.promocion.mail.store'), $form)->assertOk();
    expect(DB::table('mailmag_master')->count())->toBe(1);

    // El SQL guardado funciona con el motor (cd 1 activo; el 2 queda fuera solo como 例外 en la lista, no en el SQL)
    app(MailSender::class)->send(500);
    Mail::assertSent(PromotionMail::class, fn ($m) => $m->hasTo('taro@example.com') && str_contains($m->body, '山田 太郎様'));
    Mail::assertNotSent(PromotionMail::class, fn ($m) => $m->hasTo('x@example.com'));
});

it('programa un envío con hora en send_schedule', function () {
    $uniqid = m2Select($this, 'deco');
    $this->post(route('admin.promocion.deco.store'), ['search_uniqid' => $uniqid, 'title' => 'T', 'naiyou' => '<p>本文</p>', 'tourokusya' => '-1',
        'send_flg' => '0', 'nen' => '2026', 'tuki' => '12', 'hi' => '01', 'jikan' => '09', 'fun' => '30', 'mail_send_verify' => '9'])
        ->assertOk()->assertSee('以下の内容で予約いたしました。')->assertSee('2026年12月01日09時30分');

    expect(DB::table('mailmag_master')->where('cd', 500)->first())->type_flg->toBe('deco')->{'配信状況'}->toBe('1')
        ->and(DB::table('send_schedule')->first())->{'配信時間'}->toBe('2026/12/01 09:30:00')->mag_cd->toBe(500)->cd->toBe(70);
});

it('responde los AJAX de plantillas, cupones, 例外顧客 y テストメール', function () {
    DB::table('tb_mail_template')->insert(['cd' => 5, 'tb_mail_template_category_cd' => 1, 'title' => 'セール', 'naiyou' => "本文\n2", 'mail_type' => '', 'rank' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1']);
    DB::table('tb_mycoupon_master')->insert(['cd' => 7, 'tb_shop_cd' => -1, 'title' => '10%OFF', 'comment' => "全品\n対象", 'coupon_kubun' => '', 'disp_flg' => '1']);
    DB::table('tb_test_mail_address')->insert(['cd' => 3, 'tb_shop_cd' => 1, 'name' => 'テスト', 'email' => 'test@example.com', 'rank' => 1, 'disp_flg' => '1']);

    $this->get(route('legacy.promocion.inc.ajax_get_template_titles', ['cd' => 1, 'mail_type' => '']))->assertSee('<option value="5">セール</option>', false);
    $this->get(route('legacy.promocion.inc.ajax_get_template_body', ['cd' => 5]))->assertContent("本文\n2");
    $this->post(route('legacy.promocion.inc.ajax_search_coupon'), ['shop_cd' => '1', 'mail_type' => 'mail'])->assertSee('setCoupon(7,', false)->assertSee('10%OFF');
    $this->post(route('legacy.promocion.inc.ajax_reigai_kaiin'), ['type' => 'display', 'reigai_kaiin_cds' => '1'])->assertSee('【A001】 山田 太郎', false);

    $uniqid = m2Select($this);
    $this->post(route('legacy.promocion.inc.ajax_reigai_kaiin'), ['search_uniqid' => $uniqid, 'reigai_kaiin_cds' => '2'])
        ->assertSee('name="reigai_kaiin[2]" class="reigai_kaiin" value="2"  onclick', false)
        ->assertSee('name="reigai_kaiin[1]" class="reigai_kaiin" value="1" checked', false);
    $this->post(route('legacy.promocion.inc.ajax_test_mail_list'), ['testMail_cds' => '3'])->assertSee('test@example.com')->assertSee('value="3" checked', false);

    $this->post(route('legacy.promocion.inc.ajax_send_test_mail'), ['testTitle' => '{名前}へ', 'testBody' => 'こんにちは', 'testTourokusya' => '1', 'testType' => 'mail',
        'search_uniqid' => $uniqid, 'testMail_cds' => '3'])->assertContent('送信しました。');
    Mail::assertSent(PromotionMail::class, fn ($m) => $m->hasTo('test@example.com') && str_contains($m->body, '＊＊＊ここに簡単ログインが入ります＊＊＊＊'));
});

it('sube la imagen temporal, la muestra y la publica al confirmar', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('a.jpg', 800, 400);

    $this->post(route('admin.promocion.mail.upload'), ['userfile' => $file, 'ext' => 'jpg', 'time' => '1700000000000'])->assertContent('success');
    Storage::disk('public')->assertExists('system/category3/mail_mag/uploads/temp/1700000000000.jpg');
    expect(getimagesize(Storage::disk('public')->path('system/category3/mail_mag/uploads/temp/1700000000000.jpg'))[0])->toBe(480);

    $this->get(route('admin.promocion.mail.picture', ['ext' => 'jpg', 't' => '1700000000000']))->assertSee('uploads/temp/1700000000000.jpg');
    $this->get(route('admin.promocion.mail.picture', ['ext' => 'jpg', 't' => '1']))->assertContent('ERROR');

    $uniqid = m2Select($this);
    $this->post(route('admin.promocion.mail.confirm'), ['search_uniqid' => $uniqid, 'naiyou' => 'x', 'send_flg' => '1', 'tourokusya' => '1', 'pic_name' => '1700000000000'])
        ->assertOk()->assertSee('画像タイプ：<b>image/jpeg</b>', false)->assertSee('name="p_name_b" value="-1-', false);
});
