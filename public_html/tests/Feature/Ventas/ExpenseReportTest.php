<?php

use App\Models\Sales\Expense;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* 経費管理 (keihi*.php), スタッフ別集計 (staff_total.php), 日報・週報・月報 (report.php) y redirecciones legacy */

beforeEach(function () {
    s4Login();
});

it('da de alta, edita y borra gastos de 経費管理 (tb_customer_sub -2 / -10)', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_customer_sub', 'kazu' => 40]);

    $this->get(route('admin.ventas.expenses.index'))
        ->assertOk()
        ->assertSee('<title>経費管理 - ', false)
        ->assertSee('使用した経費の情報を入力してください')
        ->assertSee('value="'.now()->format('Y/m/d').'"', false)
        ->assertSee('データがありません。');

    $this->post(route('admin.ventas.expenses.store'), ['act' => 'new', 'shop' => 1, 'date1' => '2026/11/20', 'mokuteki' => ' ﾀｵﾙ購入 ', 'price' => '3,000'])
        ->assertRedirect(route('admin.ventas.expenses.index', ['status' => 'saved']));

    $expense = Expense::query()->find(40);
    expect($expense->only('tb_search_cate_cd', 'tb_search_form_cd', 'float_data', 'text_data', 'update_date', 'int_data', 'disp_flg'))
        ->toBe(['tb_search_cate_cd' => -2, 'tb_search_form_cd' => -10, 'float_data' => 3000.0, 'text_data' => 'タオル購入',
            'update_date' => '2026/11/20', 'int_data' => 1, 'disp_flg' => '1'])
        ->and($expense->touroku_date)->not->toBeEmpty();

    $this->get(route('admin.ventas.expenses.index'))
        ->assertSee('タオル購入')->assertSee('3,000')->assertSee('btn_revise_off.gif', false);

    $this->get(route('admin.ventas.expenses.index', ['cd' => 40]))
        ->assertOk()->assertSee('この内容で修正する')->assertSee('value="タオル購入"', false);
    $this->post(route('admin.ventas.expenses.store'), ['act' => 'up', 'cd' => 40, 'shop' => 1, 'date1' => '2026/11/21', 'mokuteki' => '洗剤', 'price' => '1200']);
    expect($expense->fresh()->only('text_data', 'float_data', 'update_date'))->toBe(['text_data' => '洗剤', 'float_data' => 1200.0, 'update_date' => '2026/11/21'])
        ->and(Expense::query()->count())->toBe(1);

    $this->post(route('admin.ventas.expenses.store'), ['act' => 'new', 'shop' => 1, 'date1' => '2026/11/21', 'price' => '0'])
        ->assertSessionHasErrors('price');

    $this->post(route('admin.ventas.expenses.destroy'), ['delete_flg' => 'del', 'cd' => 40])->assertRedirect();
    expect(Expense::withDeleted()->find(40)->disp_flg)->toBe('9');
});

/** Dos cuentas de 担当2 y una de 担当3 en dos 営業日 + gastos */
function ventasReportData(): void
{
    ventasStaff(2, ['kingaku_buai' => 10]);
    ventasStaff(3, ['kingaku_buai' => 20]);
    ventasCustomer(100);
    ventasCustomer(101);
    ventasMenu(1, 'カット', 5000, '1', 1);
    ventasMenu(2, 'カラー', 3000, '1', 2);
    ventasDiscount(1, '会員割', 10, '1', '1');

    // 2026/11/20: 担当2 → 8000 − 10 % = 7200 (歩合 720); 担当3 → 5000 − 300 manual = 4700 (歩合 940)
    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1, 2], 'waribiki_cd' => [1], 'update_date' => '2026/11/20']);
    ventasStoreSale(['kaiin_cd' => 101, 'staff_cd' => 3, 'menu_cd' => [1], 'otherWaribikiValue' => '300', 'otherWaribikiType' => '2', 'update_date' => '2026/11/20']);
    // 2026/11/21: 担当2 → 5000 (歩合 500)
    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1], 'update_date' => '2026/11/21']);

    test()->post(route('admin.ventas.expenses.store'), ['act' => 'new', 'shop' => 1, 'date1' => '2026/11/21', 'mokuteki' => '備品', 'price' => '1000']);
    test()->post(route('admin.ventas.expenses.store'), ['act' => 'new', 'shop' => 1, 'date1' => '2026/11/22', 'mokuteki' => '備品', 'price' => '400']);
}

it('carga スタッフ別集計 y devuelve el集計 de una スタッフ con su CSV', function () {
    ventasReportData();

    $this->get(route('admin.ventas.reports.staff'))
        ->assertOk()
        ->assertSee('<title>スタッフ別集計 - ', false)
        ->assertSee('/system/category1/karute_menu/ajax_staffTotal.php', false);

    $this->post(route('legacy.ventas.ajax-staff'), ['shop_cd' => 1])
        ->assertOk()
        ->assertSee('▼スタッフ選択')
        ->assertSee('<option value="2">担当2</option>', false);

    $this->post(route('legacy.ventas.ajax-staff-total'), ['shop_cd' => 1, 'staff_cd' => 2])
        ->assertOk()->assertContent('集計期間を入力してください。');
    $this->post(route('legacy.ventas.ajax-staff-total'), ['shop_cd' => 1, 'staff_cd' => 2, 'date1' => '2026/13/01'])
        ->assertContent('日付：不適切な形式です。');

    $this->post(route('legacy.ventas.ajax-staff-total'), ['shop_cd' => 1, 'staff_cd' => 2, 'date1' => '2026/11/20', 'date2' => '2026/11/21'])
        ->assertOk()
        ->assertSee('■担当2&emsp;2026/11/20 ～ 2026/11/21の集計', false)
        ->assertSee('10%')
        ->assertSeeInOrder(['売上', '12,200', 'スタッフ歩合', '1,220'])
        ->assertSeeInOrder(['2026/11/20', '7,200', '720', '2026/11/21', '5,000', '500'])
        ->assertSeeInOrder(['顧客100', '2', '12,200'])
        ->assertSeeInOrder(['カット', '2', '10,000', 'カラー', '1', '3,000'])
        ->assertSee('CSV出力');

    // Un solo día
    $this->post(route('legacy.ventas.ajax-staff-total'), ['shop_cd' => 1, 'staff_cd' => 2, 'date2' => '2026/11/21'])
        ->assertSee('2026/11/21の集計', false)->assertDontSee('2026/11/20');

    $csv = $this->get(route('admin.ventas.reports.staff.csv', ['staff_cd' => 2, 'date1' => '2026/11/20', 'date2' => '2026/11/21']));
    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toStartWith('text/x-csv');
    $text = mb_convert_encoding($csv->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($text)->toContain('"日付","売上","スタッフ歩合"')
        ->and($text)->toContain('"2026/11/20","7200","720"')
        ->and($text)->toContain('"カット","2","10000"');
});

it('carga 日報・週報・月報 y calcula 純利益 y 粗利 por día, por スタッフ y en CSV', function () {
    ventasReportData();

    $this->get(route('admin.ventas.reports.period'))
        ->assertOk()
        ->assertSee('<title>日報・週報・月報 - ', false)
        ->assertSee('<option value="-1" selected="selected">全店舗</option>', false);

    // 売上 13000 + 5000 = 18000; 割引 800; その他 300; 経費 1400; 純利益 15500; 歩合 720+940+500 = 2160; 粗利 13340
    $this->post(route('legacy.ventas.ajax-report'), ['shop_cd' => -1, 'date1' => '2026/11/20', 'date2' => '2026/11/22'])
        ->assertOk()
        ->assertSee('■全店舗&emsp;2026/11/20 ～ 2026/11/22の集計', false)
        ->assertSeeInOrder(['売上', '18,000円', '割引', '800円', 'その他の割引', '300円', '経費', '1,400円', '純利益', '15,500円'])
        ->assertSeeInOrder(['スタッフ歩合', '2,160円', '粗利', '13,340円'])
        // por día: el 22 solo tiene 経費 y va al final en orden de fecha
        ->assertSeeInOrder(['2026/11/20', '13,000円', '800円', '300円', '0円', '11,900円', '1,660円', '10,240円'])
        ->assertSeeInOrder(['2026/11/21', '5,000円', '0円', '0円', '1,000円', '4,000円', '500円', '3,500円'])
        ->assertSeeInOrder(['2026/11/22', '0円', '0円', '0円', '400円', '-400円', '0円', '-400円'])
        // por スタッフ
        ->assertSeeInOrder(['担当2', '13,000円', '800円', '0円', '12,200円', '1,220円'])
        ->assertSeeInOrder(['担当3', '5,000円', '0円', '300円', '4,700円', '940円']);

    $this->post(route('legacy.ventas.ajax-report'), ['shop_cd' => 1, 'date1' => '2026/10/01', 'date2' => '2026/10/02'])
        ->assertSee('■本店', false)->assertSee('データがありません。');

    $csv = $this->get(route('admin.ventas.reports.period.csv', ['shop_cd' => 1, 'date1' => '2026/11/20', 'date2' => '2026/11/22']));
    $csv->assertOk();
    $text = mb_convert_encoding($csv->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($text)->toContain('"日付","売上","割引","その他の割引","経費","純利益","スタッフ歩合","粗利"')
        ->and($text)->toContain('"合計","18000","800","300","1400","15500","2160","13340"')
        ->and($text)->toContain('"担当3","5000","0","300","4700","940"');
});

it('redirige las URL del legacy del menú de 顧客管理 a las pantallas nuevas', function (string $legacy, string $route) {
    $this->get('/system/category1/karute_menu/'.$legacy.'?shop=1')
        ->assertRedirect(route($route, in_array($route, ['admin.ventas.reports.staff', 'admin.ventas.reports.period'], true) ? [] : ['shop' => 1]));
})->with([
    ['karute_new.php', 'admin.ventas.sales.create'],
    ['karute_new_frm.php', 'admin.ventas.sales.form'],
    ['karute_delete_list.php', 'admin.ventas.sales.index'],
    ['karute_menu.php', 'admin.ventas.menus.index'],
    ['karute_waribiki.php', 'admin.ventas.discounts.index'],
    ['keihi.php', 'admin.ventas.expenses.index'],
    ['staff_total.php', 'admin.ventas.reports.staff'],
    ['report.php', 'admin.ventas.reports.period'],
]);

it('el menú lateral de 顧客管理 muestra los enlaces de 売上管理', function () {
    DB::table('tb_base_system_child')->insert(['cd' => 10, 'tb_base_system_cd' => 1, 'system_name' => 'カルテ管理', 'system_flg' => '1', 'rank' => 1]);

    $this->get(route('admin.ventas.menus.index'))
        ->assertSee('/system/category1/karute_menu/karute_new.php', false)
        ->assertSee('/system/category1/karute_menu/report.php', false);
});

it('un staff sin permiso de ventas no entra', function () {
    // Grupo con kengen 1033 sin ninguna función (el staff sin filas tiene todo, como el legacy)
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => '受付', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert(['cd' => 1033, 'name' => '売上管理', 'hyouji_flg' => '1']);
    DB::table('tb_kengen')->insert(['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1033, 'etsuran_flg' => '0', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0']);
    DB::table('tb_staff')->insert(['cd' => 9, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'uketsuke', 'login_pass' => 'pass9', 'name' => '受付', 'tb_staff_group_cd' => 5, 'disp_flg' => '1']);

    auth('admin')->logout();
    loginAs('uketsuke', 'pass9');

    $this->get(route('admin.ventas.menus.index'))->assertForbidden();
    $this->get(route('admin.ventas.reports.period'))->assertForbidden();
});
