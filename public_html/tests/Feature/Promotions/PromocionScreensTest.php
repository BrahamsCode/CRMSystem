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
]);
