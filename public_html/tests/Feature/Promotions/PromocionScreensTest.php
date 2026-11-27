<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Nucleo/helpers.php';

/*
 * Humo: las pantallas de 販売促進管理 con el login real de master y las rutas tal como las carga routes/web.php
 * (QA de S1: mail_past daba 500 porque las rutas legacy quedaban como legacy.legacy.promocion.*).
 */
it('abre las pantallas del módulo con el título y el menú del legacy', function (string $route) {
    seedNucleo();
    DB::table('tb_base_system_child')->insert(['cd' => 30, 'tb_base_system_cd' => 3, 'system_name' => 'メルマガ管理', 'system_flg' => '1', 'rank' => 1]);
    loginAs('master', 'secreto1');

    $this->get(route($route))->assertOk()->assertSee(' - shop</title>', false)->assertSee('ac1 accordionBlock', false);
})->with([
    'admin.promocion.mail.select', 'admin.promocion.mail.future', 'admin.promocion.mail.past',
    'admin.promocion.deco.past', 'admin.promocion.templates.index', 'admin.promocion.test-addresses.index',
    'admin.promocion.coupons.index', 'admin.promocion.coupons.create', 'admin.promocion.coupons.ranking', 'admin.promocion.coupons.stats',
    'admin.promocion.points.rules.index', 'admin.promocion.points.rules.create',
    'admin.promocion.follow.rule', 'admin.promocion.follow.future', 'admin.promocion.auto.rule', 'admin.promocion.auto.future',
    'admin.promocion.clicks.mail', 'admin.promocion.clicks.deco', 'admin.promocion.clicks.coupon',
    'admin.promocion.reminder.rule', 'admin.promocion.reminder.future',
]);

it('redirige las URL legacy de フォロー/自動 con su query y oculta スマトピ hasta que decida Antony (F8)', function () {
    seedNucleo();
    DB::table('admin_master')->update(['smatopi_flg' => 1]);
    DB::table('tb_base_system_child')->insert([
        ['cd' => 30, 'tb_base_system_cd' => 3, 'system_name' => 'メルマガ管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 31, 'tb_base_system_cd' => 3, 'system_name' => 'スマトピ連携', 'system_flg' => '1', 'rank' => 2],
        ['cd' => 32, 'tb_base_system_cd' => 3, 'system_name' => 'プッシュ通知', 'system_flg' => '1', 'rank' => 3],
    ]);
    loginAs('master', 'secreto1');

    $this->get('/system/category3/follow/follow_new.php?kind=followPush')->assertRedirect(route('admin.promocion.follow.rule', ['kind' => 'followPush']));
    $this->get('/system/category3/auto/auto_new.php?kind=autoPush')->assertRedirect(route('admin.promocion.auto.rule', ['kind' => 'autoPush']));
    $this->get(route('admin.promocion.follow.rule', ['kind' => 'followPush']))->assertOk()->assertSee('フォロー通知');

    $this->get(route('admin.promocion.mail.past'))->assertOk()
        ->assertDontSee('smatopi_message', false)->assertDontSee('スマトピ管理画面')
        ->assertSee('/admin/promocion/follow/follow_new?kind=followPush', false)->assertSee('/admin/promocion/auto/auto_new?kind=autoPush', false);
});
