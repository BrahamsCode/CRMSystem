<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

require_once __DIR__ . '/helpers.php';

// Portadas de los módulos (system/category<N>/index.php) y clase pageCategory del <body> (getSelectCategoryNo)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
});

it('pinta la portada de 顧客管理 con los contadores de socios', function () {
    $now = now()->timestamp;
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'touroku_kubun' => '2', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'touroku_date' => (string) $now, 'update_date' => '', 'mailmaga_flg' => '1'],
        ['cd' => 2, 'touroku_kubun' => '2', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'touroku_date' => '1000', 'update_date' => '1000', 'mailmaga_flg' => '9'],
        ['cd' => 3, 'touroku_kubun' => '2', 'disp_flg' => '0', 'tb_shop_cd' => 1, 'touroku_date' => '1000', 'update_date' => (string) $now, 'mailmaga_flg' => '1'],
        ['cd' => 4, 'touroku_kubun' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'touroku_date' => (string) $now, 'update_date' => '', 'mailmaga_flg' => '1'],
    ]);

    $this->get('/system/category1/')->assertOk()
        ->assertSee('<title>顧客管理トップ - shop</title>', false)
        ->assertSee('<body id="pTop" class="pageCategory1">', false)
        ->assertSee('src="/system/img/cover1.gif"', false)
        ->assertSee('<li class=" selected "><a href="/system/category1/"', false)
        ->assertSee('顧客登録数 総計')
        ->assertSeeInOrder(['顧客登録数 総計', '2件', '解約数', '1件', '月度 登録数', '1件'], false)
        ->assertSee('disp_flg=' . urlencode('退会済'), false);
});

it('販売促進管理 cuenta los 不達 y fija su título; 5, 6 y 7 solo llevan la portada', function () {
    $this->get('/system/category3/index.php')->assertOk()
        ->assertSee('<title>販売促進管理トップ -総合業務管理システム-</title>', false)
        ->assertSee('不達確定数')
        ->assertDontSee('解約数');

    DB::table('tb_base_system')->insert([
        ['cd' => 5, 'system_name' => '業務支援管理', 'system_flg' => '1', 'narabi' => 5],
        ['cd' => 7, 'system_name' => '端末管理', 'system_flg' => '1', 'narabi' => 7],
    ]);
    $this->get('/system/category5/')->assertOk()->assertSee('<title>業務支援管理トップ - shop</title>', false)
        ->assertSee('cover5.gif')->assertDontSee('顧客登録数');
    $this->get('/system/category7/')->assertOk()->assertSee('cover7.gif')->assertSee('class="pageCategory7"', false);
});

it('category9 redirige a la agenda de reservas', function () {
    if (! Route::has('admin.reservas.timetable')) {
        $this->get('/system/category9/')->assertRedirect('/system/category9/yoyaku/time_table.php');
        Route::get('admin/reservas/timetable', fn () => 'agenda')->name('admin.reservas.timetable');
        Route::getRoutes()->refreshNameLookups();
    }
    // con la agenda del módulo de reservas migrada, va a ella
    $this->get('/system/category9/index.php')->assertRedirect(route('admin.reservas.timetable'));
});

it('pageCategory sigue el segundo tramo de la URL o la categoría del menú', function () {
    $this->get('/system/select.php')->assertSee('<body id="pTop" class="pageCategoryselect.php">', false);

    Route::middleware(['web', 'auth:admin'])->get('admin/qa-prueba', fn () => view('admin.nucleo.category-top', [
        'category' => 5, 'menuKey' => 'operaciones', 'systemName' => 'x', 'titleSuffix' => null, 'counter' => null,
    ]));
    $this->get('/admin/qa-prueba')->assertSee('<body id="pTop" class="pageCategory5">', false);

    // config/menu sin 'category': la del mapa de portadas (promocion → 3)
    config(['menu.promocion' => ['blocks' => []]]);
    Route::middleware(['web', 'auth:admin'])->get('admin/qa-promo', fn () => view('admin.nucleo.category-top', [
        'category' => 3, 'menuKey' => 'promocion', 'systemName' => 'x', 'titleSuffix' => null, 'counter' => null,
    ]));
    $this->get('/admin/qa-promo')->assertSee('<body id="pTop" class="pageCategory3">', false)
        ->assertSee('<li class=" selected "><a href="/system/category3/"', false);
});
