<?php

use App\Services\Promotions\VisitStatsService;

require_once __DIR__.'/../Terminales/helpers.php';
require_once __DIR__.'/visit_helpers.php';

/* F7 来店履歴管理: 来店履歴一覧 (visit_stats2), 日別 (day_visit_list) y 個別来店履歴 (store_visit_list) */

beforeEach(function () {
    s4Login();
    seedVisits();
});

it('来店履歴一覧: el día de negocio en curso con su análisis y su lista de clientes', function () {
    $this->get(route('admin.promocion.visit.stats'))
        ->assertOk()
        ->assertSee('<title>来店履歴一覧 - ', false)
        ->assertSee('circle.js', false)
        ->assertSeeInOrder(['2026年11月27日', '来店人数は', '4人'], false)
        ->assertSee('<strong>2人</strong>来店(<strong>50.0%</strong>)', false)
        ->assertSee('["会社員",2,"#1829CE"]', false)
        ->assertSee('時間別表&nbsp;&nbsp;2026年11月27日', false)
        ->assertSeeInOrder(['9時', '2', '10時', '0'], false)
        ->assertSee('CSV出力する')
        // 来客一覧: última visita primero; la borrada no cuenta
        ->assertSeeInOrder(['■来客一覧 (2026/11/27)', 'K102', 'M-102', '佐藤花子', '2026/11/28 03:00:00', 'K101', '山田太郎', '2026/11/27 09:40:00'], false)
        ->assertSee('/system/category1/inc/customer_detail.php?kaiin_cd=102&wintype=no_headers', false)
        ->assertSee('月間表へ戻る');
});

it('来店履歴一覧: 月間, 年間 y 過去全て, y otra tienda', function () {
    $nov = VisitStatsService::at(2026, 11, 15, 12);

    $this->get(route('admin.promocion.visit.stats', ['kikan' => 1, 'udate' => $nov]))
        ->assertOk()
        ->assertSee('日別表&nbsp;&nbsp;2026年11月', false)
        ->assertSeeInOrder(['27日', 'ChangeKikan(2,0,'.VisitStatsService::at(2026, 11, 27, 5).')', '4'], false)
        ->assertSee('年間表へ戻る')
        ->assertDontSee('■来客一覧');

    $this->get(route('admin.promocion.visit.stats', ['kikan' => 2, 'udate' => $nov]))
        ->assertOk()
        ->assertSeeInOrder(['10月', '1', '11月', '4'], false)
        ->assertSee('来店人数は<span style="font-size:120%;font-weight:bold; color: #336699;">5人</span>', false);

    $this->get(route('admin.promocion.visit.stats', ['kikan' => 3]))
        ->assertOk()
        ->assertSeeInOrder(['年別表', '2026年', '5'], false)
        ->assertDontSee('過去全てへ戻る');

    $this->post(route('admin.promocion.visit.stats'), ['shop_cd' => 2, 'kikan' => 0, 'sbm' => '表示'])
        ->assertOk()
        ->assertSee('来店人数は<span style="font-size:120%;font-weight:bold; color: #336699;">1人</span>', false);
});

it('来店履歴一覧: la lista de una hora y la URL del menú legacy', function () {
    $hour = VisitStatsService::at(2026, 11, 27, 9);
    $this->get(route('admin.promocion.visit.stats', ['kikan' => 0, 'udate' => $hour, 'disp' => 1]))
        ->assertOk()
        ->assertSee('■来客一覧 (2026/11/27 09:00)')
        ->assertSee('山田太郎')
        ->assertDontSee('佐藤花子');

    $this->get('/system/category3/visit/visit_stats2.php?kikan=0')
        ->assertRedirect(route('admin.promocion.visit.stats').'?kikan=0');
});

it('来店履歴一覧: un staff que no es master solo ve su tienda y no ve el CSV', function () {
    loginShopStaff();

    $this->get(route('admin.promocion.visit.stats', ['shop_cd' => 1]))
        ->assertOk()
        ->assertSee('<input type="hidden" name="shop_cd" value="2">', false)
        ->assertSee('来店人数は<span style="font-size:120%;font-weight:bold; color: #336699;">1人</span>', false)
        ->assertDontSee('CSV出力する');
});

it('日別: tabla por horas desde las 05:00, lista del día y de una hora (también de madrugada)', function () {
    $this->post(route('admin.promocion.visit.day'), ['shop_cd' => 1, 'kikan' => 0, 'submit' => '表示'])
        ->assertOk()
        ->assertSeeInOrder(['■来客数一覧', '2026/11/27'], false)
        ->assertSee('<small class="graKensu">2</small>', false)
        ->assertSee('&amp;ddd=27&amp;hhh=9&amp;page=1', false)
        ->assertSee('CSVファイル出力')
        ->assertSeeInOrder(['■来客一覧', 'K101', '山田太郎', '2', 'K102', '佐藤花子', '2'], false);

    $date = VisitStatsService::at(2026, 11, 27, 5);
    $this->get(route('admin.promocion.visit.day', ['shop_cd' => 1, 'kikan' => 0, 'date' => $date, 'ddd' => 27, 'hhh' => 9]))
        ->assertOk()
        ->assertSee('山田太郎')
        ->assertDontSee('佐藤花子');

    // 3時 = madrugada del día siguiente (el legacy buscaba las 03:00 del mismo día)
    $this->get(route('admin.promocion.visit.day', ['shop_cd' => 1, 'kikan' => 0, 'date' => $date, 'ddd' => 27, 'hhh' => 3]))
        ->assertOk()
        ->assertSee('佐藤花子')
        ->assertDontSee('山田太郎');
});

it('日別: 今月 con totales por hora y 今年 por días', function () {
    $this->post(route('admin.promocion.visit.day'), ['shop_cd' => 1, 'kikan' => 1, 'submit' => '表示'])
        ->assertOk()
        ->assertSee('<th>小計</th>', false)
        ->assertSeeInOrder(['2026/11/01', '2026/11/30', '合計'], false)
        ->assertSee('<th>4</th>', false);

    $this->post(route('admin.promocion.visit.day'), ['shop_cd' => 1, 'kikan' => 2, 'submit' => '表示'])
        ->assertOk()
        ->assertSeeInOrder(['10月', '11月', '12月', '合計'], false)
        ->assertSee('mmm=11&amp;ddd=27&amp;hhh=5', false)
        ->assertSee('<th>5</th>', false);

    // Sin tienda no calcula
    $this->get(route('admin.promocion.visit.day'))->assertOk()->assertDontSee('■来客数一覧');
});

it('個別来店履歴: todas las tiendas por hora, día y mes', function () {
    $this->get(route('admin.promocion.visit.store-list'))->assertOk()->assertSee('■個別来店履歴抽出')->assertDontSee('■来客数一覧');

    // 今日 (días naturales): 本店 3 (la de las 03:00 del 28 no entra), 支店 1
    $this->post(route('admin.promocion.visit.store-list'), ['kikan' => 0, 'submit' => '表示'])
        ->assertOk()
        ->assertSeeInOrder(['0時', '23時', '小計'], false)
        ->assertSeeInOrder(['本店', '支店', '合計'], false)
        ->assertSee('<td style="font-weight:bold;border-right:1px solid #87AFAE;">3</td>', false)
        ->assertSee('<th>4</th>', false);

    $this->post(route('admin.promocion.visit.store-list'), ['kikan' => 1, 'submit' => '表示'])
        ->assertOk()
        ->assertSee(e(route('admin.promocion.visit.day', ['shop_cd' => 1, 'kikan' => 0, 'date' => VisitStatsService::at(2026, 11, 27)])), false)
        ->assertSee('<th>5</th>', false)
        ->assertSeeInOrder(['■来客一覧', 'K101', '山田太郎', '3', 'K102', '佐藤花子', '2'], false);

    $this->post(route('admin.promocion.visit.store-list'), ['kikan' => 2, 'submit' => '表示'])
        ->assertOk()
        ->assertSee(e(route('admin.promocion.visit.day', ['shop_cd' => 1, 'kikan' => 1, 'date' => VisitStatsService::at(2026, 10, 1)])), false)
        ->assertSee('<th>6</th>', false);
});
