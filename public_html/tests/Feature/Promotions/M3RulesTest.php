<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/*
 * M3: 自動 (auto_new → confirm → 配信条件選択 → 内容設定 → 登録) y フォロー (follow_new → confirm → 内容設定 → 登録),
 * su lista de programados con 起動/停止, edición y el buscador de 例外顧客 (ajax_search_kaiin).
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
    if (! Route::has('legacy.promocion.inc.ajax_search_kaiin')) {
        Route::middleware('web')->name('legacy.')->group(base_path('routes/legacy/promocion.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));
    Mail::fake();

    DB::table('admin_master')->insert(['cd' => 1, 'domain' => 'shop.crm-s.net', 'mail_footer' => '', 'application_use_flg' => 0]);
    DB::table('tb_office')->insert(['cd' => 1, 'kaishamei' => '本社', 'start_work_time' => '09<>30']);
    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('hatuban')->insert([['koumoku' => 'mailmag', 'kazu' => 500], ['koumoku' => 'send_schedule', 'kazu' => 70]]);
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'id' => 'A001', 'kanri_id' => 'K1', 'name' => '山田 太郎', 'name_kana' => 'ヤマダ', 'email1' => 'taro@example.com', 'mailmaga_email_type' => 'パソコン', 'touroku_kubun' => '2', 'disp_flg' => '1', 'tb_shop_cd' => 1,
            'touroku_date' => (string) Carbon::parse('2026-11-23 15:00', 'Asia/Tokyo')->getTimestamp()],
        ['cd' => 2, 'id' => 'A002', 'kanri_id' => 'K2', 'name' => '佐藤 花子', 'name_kana' => 'サトウ', 'email1' => 'hanako@example.com', 'mailmaga_email_type' => 'AU', 'touroku_kubun' => '2', 'disp_flg' => '1', 'tb_shop_cd' => 2,
            'touroku_date' => (string) Carbon::parse('2026-11-23 15:00', 'Asia/Tokyo')->getTimestamp()],
    ]);
});

it('registra un 自動 con meses y días a través del 配信条件選択', function () {
    $this->get(route('admin.promocion.auto.rule'))->assertOk()->assertSee('■自動メール情報')
        ->assertSee('<option value="09" selected>09時</option>', false)->assertSee('<option value="30" selected>30分</option>', false);

    $rule = ['mail_type' => 'autodeco', 'step1' => '1', 'step2_1' => ['1', '2'], 'step2_2' => ['5', '32'], 'step2_3' => ['3'], 'step2_jikan' => '09', 'step2_fun' => '30', 'kind' => ''];
    $this->post(route('admin.promocion.auto.rule-confirm'), $rule)->assertOk()
        ->assertSee('デコメール')->assertSee('1月 2月')->assertSee('5日 月末')->assertSee('name="step2_1" value="1,2"', false)
        ->assertDontSee('name="step2_3"', false);

    $carry = ['mail_type' => 'autodeco', 'step1' => '1', 'step2_1' => '1,2', 'step2_2' => '5,32', 'step2_jikan' => '09', 'step2_fun' => '30'];
    $select = $this->post(route('admin.promocion.auto.select'), $carry + ['act' => 'up'])->assertOk()
        ->assertSee('■振分配信条件抽出')->assertSee('自動 配信条件選択', false)->assertSee('name="step2_2" value="5,32"', false);
    preg_match('/name="search_uniqid" value="(\w{16})"/', $select->getContent(), $m);

    $this->post(route('admin.promocion.auto.create'), $carry + ['search_uniqid' => $m[1]])->assertOk()
        ->assertSee('09時30分&emsp;配信月：1月 2月&emsp;配信日：5日 月末', false)
        ->assertSee('name="mail_type" value="autodeco"', false)
        ->assertDontSee('name="nen"', false);

    $this->post(route('admin.promocion.auto.store'), $carry + ['search_uniqid' => $m[1], 'title' => '月初', 'naiyou' => '<p>本文</p>', 'tourokusya' => '-1', 'mail_send_verify' => '1'])
        ->assertOk();

    $mail = DB::table('mailmag_master')->where('cd', 500)->first();
    expect([$mail->type_flg, $mail->send_parameter, $mail->{'配信日時'}, $mail->{'配信状況'}, (int) $mail->{'配信総数'}])
        ->toBe(['autodeco', '1<>1,2<>5,32', '09:30', '1', 2])
        ->and(DB::table('send_schedule')->where('mag_cd', 500)->value('配信時間'))->toBe('09:30');

    // Lista de programados: condiciones, 起動中 y edición con las condiciones guardadas
    $this->get(route('admin.promocion.auto.future'))->assertOk()->assertSee('■自動メール配信')->assertSee('起動中')
        ->assertSee('配信月：1月 2月&lt;&gt;配信日：5日 月末', false)->assertSee('全店舗 ／', false);
    $this->get(route('admin.promocion.auto.edit', ['mailmagcd' => 500]))->assertOk()
        ->assertSee('配信月：1月 2月&emsp;配信日：5日 月末', false)->assertSee('name="mail_type" value="autodeco"', false);
});

it('registra una フォロー a N días del alta con su 振分配信 relativo a hoy', function () {
    // El 振分配信 usa el «today» de PostgreSQL (no el reloj de la prueba): alta del socio 1 hace 3 días según la BD
    $threeDaysAgo = (int) DB::selectOne("SELECT extract(epoch from timestamp 'today' - interval '3 days')::int + 3600 AS t")->t;
    DB::table('tb_customer')->where('cd', 1)->update(['touroku_date' => (string) $threeDaysAgo]);

    $this->get(route('admin.promocion.follow.rule'))->assertOk()->assertSee('■フォローメール情報 修正')
        ->assertDontSee('来店後のフォロー')->assertSee('<option value="-1" >全店舗</option>', false);

    // Solo dígitos: el legacy lo metía en el SQL
    $this->post(route('admin.promocion.follow.rule-confirm'), ['shop_cd' => '1', 'step1' => '2', 'step2_1' => "3'; drop", 'step2_jikan' => '08', 'step2_fun' => '00'])
        ->assertRedirect(route('admin.promocion.follow.rule'));

    $confirm = $this->post(route('admin.promocion.follow.rule-confirm'), ['shop_cd' => '1', 'mail_type' => 'follow', 'step1' => '2', 'step2_1' => '3', 'step2_jikan' => '08', 'step2_fun' => '00'])
        ->assertOk()->assertSee('本店')->assertSee('会員登録日から○日後のお客様にメール配信')->assertSee('3日後');
    preg_match('/name="search_uniqid" value="(\w{16})"/', $confirm->getContent(), $m);

    $base = ['search_uniqid' => $m[1], 'shop_cd' => '1', 'mail_type' => 'follow', 'step1' => '2', 'step2_1' => '3', 'step2_jikan' => '08', 'step2_fun' => '00'];
    $this->post(route('admin.promocion.follow.create'), $base)->assertOk()->assertSee('会員登録日から3日後', false);
    $this->post(route('admin.promocion.follow.store'), $base + ['title' => 'その後', 'naiyou' => '本文', 'tourokusya' => '1', 'mail_send_verify' => '2'])->assertOk()
        ->assertSee('毎日08時00分');

    $mail = DB::table('mailmag_master')->where('cd', 500)->first();
    expect([$mail->type_flg, $mail->send_parameter, $mail->{'配信日時'}, (int) $mail->{'配信総数'}])->toBe(['follow', '1<>2<>3', '08:00', 1])
        ->and($mail->{'振分配信'})->toContain("AND tb_shop_cd = 1")->toContain("interval '3 days'")
        ->and($mail->search_display)->toBe('会員登録日から3日後');

    // 停止 / 起動 (ajax_change_status) y edición con «<tienda> 宛»
    $this->get(route('admin.promocion.follow.future'))->assertOk()->assertSee('■フォローメール配信')->assertSee('本店 ／', false)->assertSee('停止する');
    $this->post(route('legacy.promocion.inc.ajax_change_status'), ['cd' => '500', 'st' => '停止'])->assertOk();
    expect(DB::table('mailmag_master')->where('cd', 500)->value('disp_flg'))->toBe('7')
        ->and(DB::table('send_schedule')->where('mag_cd', 500)->exists())->toBeFalse();
    $this->get(route('admin.promocion.follow.future'))->assertSee('停止中')->assertSee('起動する');
    $this->post(route('legacy.promocion.inc.ajax_change_status'), ['cd' => '500', 'st' => '起動'])->assertOk();
    expect(DB::table('mailmag_master')->where('cd', 500)->value('disp_flg'))->toBe('')
        ->and(DB::table('send_schedule')->where('mag_cd', 500)->value('配信時間'))->toBe('08:00');

    $this->get(route('admin.promocion.follow.edit', ['mailmagcd' => 500]))->assertOk()->assertSee('会員登録日から3日後 本店 宛');
});

it('arma la フォロー del cumpleaños y de un campo 月日 de 顧客追加情報', function () {
    DB::table('tb_search_cate')->insert(['cd' => 7, 'name' => '記念日', 'record_type' => '', 'disp_flg' => '1', 'hyouji_flg' => '1']);
    DB::table('tb_search_form')->insert(['cd' => 70, 'tb_search_cate_cd' => 7, 'name' => '結婚記念日', 'form_type' => '月日', 'disp_flg' => '1', 'hyouji_flg' => '1']);
    $this->get(route('admin.promocion.follow.rule'))->assertSee('結婚記念日から○日前後のお客様にメール配信');

    $this->post(route('admin.promocion.follow.rule-confirm'), ['shop_cd' => '-1', 'step1' => 'custom_70', 'step2_1' => '2', 'step2_zengo' => '後', 'step2_jikan' => '10', 'step2_fun' => '00'])
        ->assertOk()->assertSee('全店舗')->assertSee('結婚記念日から○日後のお客様にメール配信');
    $this->post(route('admin.promocion.follow.rule-confirm'), ['shop_cd' => '-1', 'step1' => 'custom_99', 'step2_1' => '2'])
        ->assertRedirect(route('admin.promocion.follow.rule'));
});

it('busca socios para el 例外顧客 de follow/auto y pinta los elegidos', function () {
    $this->get(route('legacy.promocion.inc.ajax_search_kaiin', ['name' => 'ヤマ']))->assertOk()
        ->assertSee("fnc_setMember('1')", false)->assertDontSee('佐藤');
    $this->get(route('legacy.promocion.inc.ajax_search_kaiin', ['shop_cd' => '9']))->assertSee('データが見つかりませんでした');
    $this->get(route('legacy.promocion.inc.ajax_search_kaiin', ['type' => 'display', 'reigai_kaiin_cds' => '2']))
        ->assertSee('<div id="member_2">', false)->assertSee('【A002】&emsp;佐藤 花子', false);
    $this->get(route('legacy.promocion.inc.ajax_get_shop_data'))->assertSee('<option value="2" >支店</option>', false);
});

it('アンケート: el registro marca la encuesta emitida y la edición conserva encuesta y cupón (pedidos de S4)', function () {
    DB::table('tb_c3_anketo_cate')->insert(['cd' => 10, 'name' => '満足度', 'disp_flg' => '1', 'attached_cd' => 5]);
    DB::table('tb_base_system_child')->insert(['cd' => 32, 'tb_base_system_cd' => 3, 'system_name' => 'マイクーポン管理', 'system_flg' => '1']);
    DB::table('tb_mycoupon_master')->insert(['cd' => 5, 'tb_shop_cd' => -1, 'title' => '回答特典', 'comment' => '10%OFF', 'disp_flg' => '1']);
    $uniqid = app(\App\Services\Promotions\MailAudience::class)->storeRaw("WHERE disp_flg = '1'", null, '');

    $mail = app(\App\Services\Promotions\MailRegistrar::class)->register('anketo',
        ['title' => 'Q', 'naiyou' => '本文', 'send_flg' => '0', 'nen' => '2026', 'tuki' => '12', 'hi' => '01', 'jikan' => '09', 'fun' => '00',
            'hakkou_cd' => '10', 'get_coupon_cd' => '5'], $uniqid, '1');
    $cate = DB::table('tb_c3_anketo_cate')->where('cd', 10)->first();
    expect([(int) $mail->type_data_cd, $cate->hyouji_flg, (int) $cate->attached_cd])->toBe([10, '1', 5])->and($cate->hakkou_date)->not->toBeNull();

    $page = $this->get(route('admin.promocion.mail.edit', ['mailmagcd' => $mail->cd]))->assertOk()
        ->assertSee('name="get_coupon_cd" value="5"', false)->assertSee('【回答特典', false);
    preg_match('/name="search_uniqid" value="(\w{16})"/', $page->getContent(), $m);

    // Quitar el cupón ya no borra la encuesta del envío (el legacy ponía type_data_cd = NULL)
    $this->post(route('admin.promocion.mail.update'), ['mailmagcd' => $mail->cd, 'search_uniqid' => $m[1], 'mail_type' => 'anketo', 'hakkou_cd' => '10',
        'get_coupon_cd' => '', 'title' => 'Q', 'naiyou' => '本文', 'nen' => '2026', 'tuki' => '12', 'hi' => '02', 'jikan' => '09', 'fun' => '00', 'mail_send_verify' => '7'])->assertOk();
    expect((int) DB::table('mailmag_master')->where('cd', $mail->cd)->value('type_data_cd'))->toBe(10)
        ->and(\App\Http\Controllers\Admin\Promotions\MailController::activeAc('anketo2.reply'))->toBe('.ac109')
        ->and(\App\Http\Controllers\Admin\Promotions\MailController::activeAc('anketo'))->toBe('.ac8');
});
