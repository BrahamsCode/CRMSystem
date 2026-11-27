<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/*
 * F2: ポイント仕様管理 (point_config.php), su AJAX de caducidad y los popups de plantillas de aviso.
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
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('admin_master')->insert(['cd' => 1, 'point_shikkou_kikan' => '30', 'point_yukoukigen_flg' => '2', 'application_use_flg' => '1', 'beacon_use_flg' => '0']);
    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('tb_customer_group')->insert(['cd' => 5, 'name' => 'VIP', 'disp_flg' => '1', 'rank' => 1]);
    DB::table('tb_base_system_grandchild')->insert(['cd' => 1, 'tb_base_system_cd' => 1, 'system_name' => 'ポイント管理', 'system_flg' => '1']);
});

it('muestra la pantalla y crea las 9 filas de avisos 2〜10', function () {
    $this->get(route('admin.promocion.points.config'))->assertOk()
        ->assertSee('■ポイント仕様管理')->assertSee('各店舗ごとのポイントルール設定')->assertSee('VIP')
        ->assertSee('■マイコレ設定')->assertDontSee('店舗毎の基礎来店ポイント設定')->assertDontSee('■有料会員')
        ->assertSee('id="point_yukoukigen_flg2" value="2" checked', false);

    expect(DB::table('point_shikkou_alert')->where('flg', '1')->orderBy('sort')->pluck('sort')->all())->toBe(range(0, 8));
    $this->get(route('admin.promocion.points.config'));
    expect(DB::table('point_shikkou_alert')->count())->toBe(9);
});

it('guarda los campos simples, la hora y las reglas por tienda y grupo', function () {
    $url = route('admin.promocion.points.config');
    $this->post($url, ['act' => 'change_point_shikkou_sousa', 'point_shikkou_sousa' => '2'])->assertOk();
    $this->post($url, ['act' => 'change_point_shikkou_time', 'point_shikkou_time_h' => '03', 'point_shikkou_time_m' => '30'])->assertOk();
    $this->post($url, ['act' => 'change_point_settings', 'point_settings' => [['1', '500', '2.5', '', '1'], ['2', '', '', '3']]])->assertOk();
    $this->post($url, ['act' => 'change_group_point_settings', 'point_settings' => [['5', '200', '1', '1']]])->assertOk();

    $admin = DB::table('admin_master')->first();
    expect($admin->point_shikkou_sousa)->toBe('2')->and($admin->point_shikkou_time)->toBe('03:30');
    $shops = DB::table('tb_shop')->orderBy('cd')->get();
    expect([$shops[0]->keisan_flg, (float) $shops[0]->shiyou_point_jyougen, (float) $shops[0]->point_percentage_genkin, (float) $shops[0]->point_percentage_card])->toBe(['2', 500.0, 2.5, 1.0])
        ->and([$shops[1]->keisan_flg, (float) $shops[1]->shiyou_point_jyougen, (float) $shops[1]->point_percentage_card])->toBe(['1', 1000.0, 3.0])
        ->and((float) DB::table('tb_customer_group')->value('shiyou_point_jyougen'))->toBe(200.0);
});

it('cierra cada aviso y crea otro con los mismos textos', function () {
    $this->get(route('admin.promocion.points.config'));
    $first = DB::table('point_shikkou_alert')->where('sort', 0)->first();
    DB::table('point_shikkou_alert')->where('cd', $first->cd)->update(['title' => '期限間近', 'message' => '本文']);

    $this->post(route('admin.promocion.points.config'), ['act' => 'change_point_shikkou_alert', 'point_shikkou_alert' => '14',
        'point_shikkou_alert_time_h' => '09', 'point_shikkou_alert_time_m' => '00',
        'point_shikkou_alert_cd' => [0 => (string) $first->cd], 'point_shikkou_alert_days' => [0 => '7'],
        'point_shikkou_alert_times_h' => [0 => '18'], 'point_shikkou_alert_times_m' => [0 => '30']])->assertOk()
        ->assertSee('value="7"', false);

    expect(DB::table('admin_master')->value('point_shikkou_alert'))->toBe('14')
        ->and(DB::table('point_shikkou_alert')->where('cd', $first->cd)->value('flg'))->toBe('0');
    $new = DB::table('point_shikkou_alert')->where('flg', '1')->where('sort', 0)->first();
    expect([$new->title, $new->message, $new->alert_day, $new->alert_time])->toBe(['期限間近', '本文', '7', '18:30'])
        ->and(strlen((string) $new->cd))->toBe(19);
});

it('avisa de los afectados y al activar la caducidad renueva la de los saldos', function () {
    DB::table('tb_customer')->insert([['cd' => 1, 'disp_flg' => '1', 'touroku_kubun' => '2'], ['cd' => 2, 'disp_flg' => '1', 'touroku_kubun' => '2']]);
    DB::table('tb_customer_point')->insert([['tb_customer_cd' => 1, 'service_point' => 10], ['tb_customer_cd' => 2, 'service_point' => 0]]);

    $this->post(route('admin.promocion.points.expiry-preview'))->assertOk()
        ->assertSee('有効アカウント1人')->assertSee('有効期限2026/12/26を設定します。');

    $this->post(route('admin.promocion.points.config'), ['act' => 'change_point_yukoukigen_flg', 'point_yukoukigen_flg' => ''])->assertOk();
    $limit = Carbon::parse('2026-12-26 23:59:59', 'Asia/Tokyo')->getTimestamp();
    expect((int) DB::table('tb_customer_point')->where('tb_customer_cd', 1)->value('point_yukoukigen'))->toBe($limit)
        ->and(DB::table('tb_customer_point')->where('tb_customer_cd', 2)->value('point_yukoukigen'))->toBeNull();

    $this->post(route('admin.promocion.points.expiry-preview'))->assertSeeText('ng');
});

it('serializa la マイコレ por tienda sin tocar el 基礎来店 si no está en pantalla', function () {
    DB::table('tb_shop')->where('cd', 1)->update(['login_default_point_settings' => serialize(['shopCd' => 1, 'earnPoints' => 5])]);

    $this->post(route('admin.promocion.points.config'), ['act' => 'change_login_point_settings',
        'login_point_settings' => [['shopCd' => '1', 'useFlg' => '1', 'title' => 'A&B', 'body' => '<b>', 'earnPoints' => '3']],
        'introduce_settings' => [['shopCd' => '1', 'introducePoints' => '10', 'introduceMaxMonthPoints' => '50', 'registrationPoints' => '5', 'body' => '']],
        'customer_registration_settings' => [['shopCd' => '1', 'registPoints' => '100', 'days' => '0', 'hours' => '10', 'minutes' => '05', 'body' => '']]])
        ->assertOk()->assertSee('value="A&amp;B"', false);

    $shop = DB::table('tb_shop')->where('cd', 1)->first();
    expect(unserialize($shop->login_point_settings))->toMatchArray(['shopCd' => 1, 'useFlg' => '1', 'title' => 'A&amp;B', 'body' => '&lt;b&gt;', 'earnPoints' => 3])
        ->and(unserialize($shop->introduce_settings))->toMatchArray(['introducePoints' => 10, 'introduceMaxMonthPoints' => '50'])
        ->and(unserialize($shop->customer_registration_settings))->toMatchArray(['days' => 0, 'hours' => '', 'minutes' => ''])
        ->and(unserialize($shop->login_default_point_settings))->toBe(['shopCd' => 1, 'earnPoints' => 5]);
});

it('edita las plantillas de aviso en admin_master y en point_shikkou_alert', function () {
    $this->get(route('admin.promocion.points.template', ['type' => 'alert']))->assertOk()
        ->assertSee('■ポイント有効期限切れ事前通知メール内容登録')->assertSee('{ポイント数}', false)->assertDontSee('{苗字}', false);

    $this->post(route('admin.promocion.points.template.update', ['type' => 'shikkou']), ['title' => ' 失効 ', 'naiyou' => 'ｶﾅ'])
        ->assertRedirect(route('admin.promocion.points.template', ['type' => 'shikkou', 'comp' => 1]));
    $admin = DB::table('admin_master')->first();
    expect([$admin->point_shikkou_mail_title, $admin->point_shikkou_mail_naiyou])->toBe(['失効', 'カナ']);
    $this->get(route('admin.promocion.points.template', ['type' => 'shikkou', 'comp' => 1]))->assertSee('window.parent.popUpWinReloadClose("");', false);

    // Sin título no guarda
    $this->post(route('admin.promocion.points.template.update', ['type' => 'alert']), ['title' => '', 'naiyou' => 'x']);
    expect(DB::table('admin_master')->value('point_alert_mail_naiyou'))->toBeNull();

    DB::table('point_shikkou_alert')->insert(['cd' => '1764000000001123456', 'flg' => '1', 'sort' => 0]);
    $this->post(route('admin.promocion.points.template.update', ['type' => 'shikkou_alert']), ['cd' => '1764000000001123456', 'title' => 'T', 'naiyou' => 'M']);
    expect(DB::table('point_shikkou_alert')->where('cd', '1764000000001123456')->value('title'))->toBe('T');
    $this->get(route('admin.promocion.points.template', ['type' => 'shikkou_alert', 'cd' => '1764000000001123456']))
        ->assertSee('name="cd" value="1764000000001123456"', false)->assertSee('value="T"', false);
});

it('arma el menú lateral de left_navi3 con los textos de la app y sus condiciones', function () {
    DB::table('tb_staff')->insert(['cd' => 1, 'shop_cd' => 1, 'login_id' => 'master', 'login_pass' => 'x', 'name' => '管理者', 'disp_flg' => '1']);
    DB::table('tb_base_system_child')->insert([
        ['cd' => 10, 'tb_base_system_cd' => 3, 'system_name' => 'ポイント管理', 'system_flg' => '1', 'rank' => 2],
        ['cd' => 11, 'tb_base_system_cd' => 3, 'system_name' => 'メルマガ管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 12, 'tb_base_system_cd' => 3, 'system_name' => 'スマトピ連携', 'system_flg' => '1', 'rank' => 3],
    ]);

    $menu = app(\App\Services\Nucleo\LegacyUi::class)->menu('promocion', \App\Models\Staff::find(1));
    $blocks = collect($menu['blocks'])->keyBy('ac');

    expect($menu['category'])->toBe(3)->and($blocks->keys()->all())->toBe(['ac1', 'ac13'])
        ->and($blocks['ac1']['title'])->toBe('お知らせ配信管理')
        ->and(array_column($blocks['ac1']['links'], 'label'))->toContain('新規お知らせ配信', 'マイコレ登録用QR')
        ->and(array_column($blocks['ac13']['links'], 'url'))->toBe(['/admin/promocion/point/point_config']);
});

it('en la tablet abre las plantillas de category3 con roid.popUp y cierra con roid.windowClose', function () {
    $tablet = ['User-Agent' => 'Mozilla/5.0 (Linux; Android 4.0; CRMRW107)'];
    $this->get(route('admin.promocion.points.config'), $tablet)->assertOk()
        ->assertSee('roid.popUp("'.route('admin.promocion.points.template', ['type' => 'alert']).'")', false)
        ->assertDontSee('category0/config/point_', false);
    $this->get(route('admin.promocion.points.template', ['type' => 'alert', 'comp' => 1]), $tablet)->assertSee('roid.windowClose();', false);
    $this->get(route('admin.promocion.points.config'))->assertDontSee('roid.popUp("', false);
});
