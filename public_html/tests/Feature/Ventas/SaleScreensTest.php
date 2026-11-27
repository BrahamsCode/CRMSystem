<?php

use App\Models\Sales\Sale;
use App\Services\Ventas\SaleService;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* 売上データ作成 (karute_new*.php) y 売上データ削除 (karute_delete_*.php) */

beforeEach(function () {
    s4Login();
    ventasStaff(2, ['kingaku_buai' => 10]);
    ventasCustomer(100);
    ventasMenu(1, 'カット', 5000, '1', 1);
    ventasMenu(2, 'トリートメント', 3000, '2', 2);
    ventasDiscount(1, '会員割', 10, '1', '1', 1);
    ventasDiscount(2, 'クーポン', 500, '2', '2', 2);
});

it('carga 売上データ作成 y el formulario emergente con スタッフ, menús, descuentos y 履歴', function () {
    $this->get(route('admin.ventas.sales.create', ['shop' => 1]))
        ->assertOk()
        ->assertSee('<title>売上データ作成 - ', false)
        ->assertSee('売上データ作成フォームを開く')
        ->assertSee(route('admin.ventas.sales.form', ['shop' => 1]), false);

    $this->get(route('admin.ventas.sales.form', ['shop' => 1, 'status' => 'saved']))
        ->assertOk()
        ->assertSee('売上データ内容')
        ->assertSee('jAlert("登録しました。", "売上データ登録");', false)
        ->assertSee('id="staffData_2"', false)
        ->assertSee('担当2')
        ->assertSee('id="menuData_1"', false)
        ->assertSee('id="waribikiData_2"', false)
        ->assertSee('履歴がありません。')
        ->assertSee('/system/category1/karute_menu/ajax_search_kaiin.php', false);
});

it('calcula los totales como el JS y la comisión como (int)(base × kingaku_buai / 100)', function () {
    $totals = app(SaleService::class)->totals([5000, 3000], [
        ['value' => 10, 'type' => '1', 'buai_flg' => '1'],
        ['value' => 500, 'type' => '2', 'buai_flg' => '2'],
    ], 200, '2');

    expect($totals)->toBe(['subtotal' => 8000, 'total' => 6500, 'commission_base' => 7000, 'discounts' => [800, 500], 'manual' => 200])
        ->and(SaleService::commission(7000, 10))->toBe(700)
        ->and(SaleService::commission(999, 12.5))->toBe(124);
});

it('guarda la cuenta en tb_customer_sub (-1) con sus filas, comisión y nº de cuenta máximo + 1', function () {
    ventasStoreSale([
        'kaiin_cd' => 100, 'staff_cd' => 2,
        'menu_cd' => [1, 2], 'menu_kingaku' => [1, 1], // los importes del formulario se ignoran
        'waribiki_cd' => [1, 2], 'otherWaribikiValue' => '200', 'otherWaribikiType' => '2',
    ])->assertRedirect(route('admin.ventas.sales.form', ['shop' => 1, 'status' => 'saved']));

    $rows = Sale::query()->orderBy('cd')->get();
    expect($rows)->toHaveCount(7)
        ->and($rows->pluck('tb_search_form_cd')->all())->toBe([-1, -2, -2, -3, -3, -4, -5])
        ->and($rows->pluck('category_inner_group')->unique()->all())->toBe(['1'])
        ->and($rows->pluck('update_date')->unique()->all())->toBe(['2026/11/20'])
        ->and($rows->pluck('int_data')->unique()->all())->toBe([1])
        ->and($rows->pluck('tb_search_cate_cd')->unique()->all())->toBe([-1])
        ->and((string) $rows[0]->cd)->toStartWith((string) now()->timestamp)
        ->and($rows[1]->cd)->toBe($rows[0]->cd + 1);

    [$staff, $menu1, $menu2, $disc1, $disc2, $manual, $total] = $rows->all();
    expect($staff->only('rank', 'float_data', 'text_data', 'array_data', 'form_title'))
        ->toBe(['rank' => 700, 'float_data' => 10.0, 'text_data' => '担当2', 'array_data' => '2', 'form_title' => 'スタッフ情報'])
        ->and($menu1->only('float_data', 'text_data', 'array_data', 'form_type'))
        ->toBe(['float_data' => 5000.0, 'text_data' => 'カット', 'array_data' => '1', 'form_type' => '技術'])
        ->and($menu2->form_type)->toBe('物販')
        ->and($disc1->only('float_data', 'form_type', 'pic_flg'))->toBe(['float_data' => 800.0, 'form_type' => '10％', 'pic_flg' => '1'])
        ->and($disc2->only('float_data', 'form_type', 'pic_flg'))->toBe(['float_data' => 500.0, 'form_type' => '500円', 'pic_flg' => '2'])
        ->and($manual->only('float_data', 'form_type', 'pic_flg', 'form_title'))->toBe(['float_data' => 200.0, 'form_type' => '200円', 'pic_flg' => '2', 'form_title' => '手動割引情報'])
        ->and($total->only('rank', 'float_data', 'form_title'))->toBe(['rank' => 7000, 'float_data' => 6500.0, 'form_title' => '合計']);

    // Segunda cuenta del mismo cliente: 請求番号 2 (cuenta también las borradas)
    Sale::query()->update(['disp_flg' => '9']);
    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1]]);
    expect(Sale::query()->pluck('category_inner_group')->unique()->all())->toBe(['2'])
        ->and(Sale::query()->form(Sale::FORM_STAFF)->value('rank'))->toBe(500);
});

it('no registra dos veces el mismo add_record_verify ni totales negativos ni datos inválidos', function () {
    $data = ['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1], 'add_record_verify' => '123'];
    ventasStoreSale($data);
    ventasStoreSale($data);
    expect(Sale::query()->form(Sale::FORM_TOTAL)->count())->toBe(1);

    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [2], 'otherWaribikiValue' => '9999', 'otherWaribikiType' => '2'])
        ->assertStatus(422)->assertSee('・合計の値がマイナスのため登録出来ません。');

    ventasStoreSale(['kaiin_cd' => 'x', 'staff_cd' => 2])
        ->assertStatus(422)->assertSee('エラー発生しました。もう一度やり直してください。');

    expect(Sale::query()->form(Sale::FORM_TOTAL)->count())->toBe(1);
});

it('muestra el 履歴 del 営業日 en el formulario', function () {
    $this->travelTo(now()->setTime(12, 0));
    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1, 2], 'update_date' => now()->format('Y/m/d')]);

    $this->get(route('admin.ventas.sales.form', ['shop' => 1]))
        ->assertOk()
        ->assertSee('ID：&nbsp;M0100', false)
        ->assertSee('8,000円')
        ->assertSee('1人');
});

it('busca socios por palabra clave (ajax_search_kaiin.php)', function () {
    ventasCustomer(101, ['name' => '山田花子', 'tel' => '0312345678']);
    ventasCustomer(102, ['name' => '仮登録', 'touroku_kubun' => '1']);

    $this->post(route('legacy.ventas.ajax-search-kaiin'), ['keyword' => '山田', 'shop_cd' => 1])
        ->assertOk()
        ->assertSee('id="kaiinData_101"', false)
        ->assertSee('山田花子')
        ->assertDontSee('顧客100');

    $this->post(route('legacy.ventas.ajax-search-kaiin'), ['keyword' => '０３１２', 'shop_cd' => 1])
        ->assertSee('山田花子');

    $this->post(route('legacy.ventas.ajax-search-kaiin'), ['keyword' => '存在しない'])
        ->assertSee('データが見つかりませんでした。');
});

it('lista las cuentas en 売上データ削除 con filtros y las borra (disp_flg 9)', function () {
    ventasStaff(3, ['name' => '担当3']);
    ventasCustomer(101);
    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1]]);
    ventasStoreSale(['kaiin_cd' => 101, 'staff_cd' => 3, 'menu_cd' => [2]]);

    $this->get(route('admin.ventas.sales.index'))
        ->assertOk()
        ->assertSee('<title>売上追加・修正・削除</title>', false)
        ->assertSee('※登録・閲覧したい店舗を選択してください。')
        ->assertDontSee('■検索結果');

    $this->get(route('admin.ventas.sales.index', ['shop' => 1]))
        ->assertOk()
        ->assertSee('■検索結果')
        ->assertSee('M0100')
        ->assertSee('M0101')
        ->assertSee('5,000円')
        ->assertSee('deleteHandler(100,1)', false);

    $this->post(route('admin.ventas.sales.index'), ['shop' => 1, 'staff' => '3', 'date1' => now()->format('Y/m/d'), 'date2' => now()->format('Y/m/d')])
        ->assertOk()
        ->assertSee('M0101')
        ->assertDontSee('M0100');

    $this->post(route('admin.ventas.sales.index'), ['shop' => 1, 'date1' => now()->addDays(2)->format('Y/m/d')])
        ->assertSee('履歴がありません。');

    $this->post(route('admin.ventas.sales.destroy'), ['act' => 'del', 'cd' => 100, 'group' => 1, 'shop' => 1, 'staff' => '', 'date1' => '', 'date2' => ''])
        ->assertRedirect(route('admin.ventas.sales.index', ['shop' => 1]));

    expect(Sale::withDeleted()->where('tb_customer_cd', 100)->pluck('disp_flg')->unique()->all())->toBe(['9'])
        ->and(Sale::withDeleted()->where('tb_customer_cd', 100)->pluck('update_date')->unique()->all())->toBe(['2026/11/20'])
        ->and(Sale::query()->where('tb_customer_cd', 101)->count())->toBe(3);

    $this->get(route('admin.ventas.sales.index', ['shop' => 1]))->assertDontSee('M0100');
});

it('no toca filas de otros usos de tb_customer_sub', function () {
    DB::table('tb_customer_sub')->insert(['cd' => 1, 'tb_customer_cd' => 100, 'tb_search_cate_cd' => 5, 'tb_search_form_cd' => 7, 'category_inner_group' => '9', 'disp_flg' => '1']);
    ventasStoreSale(['kaiin_cd' => 100, 'staff_cd' => 2, 'menu_cd' => [1]]);

    expect(Sale::query()->pluck('category_inner_group')->unique()->all())->toBe(['1'])
        ->and(Sale::query()->where('cd', 1)->exists())->toBeFalse();
});
