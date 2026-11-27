<?php

require_once __DIR__.'/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Ventas internas (legacy: system/category1/karute_menu/*): メニュー管理, 割引管理, 売上データ作成/削除, 経費管理 e informes

beforeEach(function () {
    reservasLogin();
});

function ventasMenu(array $attributes = []): ServiceMenu
{
    return ServiceMenu::create($attributes + ['tb_shop_cd' => 1, 'name' => 'カット', 'kingaku' => 5000, 'type' => '1', 'rank' => 1, 'disp_flg' => '1', 'hyouji_flg' => '1']);
}

function ventasDiscount(array $attributes = []): Discount
{
    return Discount::create($attributes + ['tb_shop_cd' => 1, 'name' => '学割', 'value' => 10, 'type' => '1', 'buai_flg' => '1', 'rank' => 1, 'disp_flg' => '1', 'hyouji_flg' => '1']);
}

/** Venta grabada con el formulario real; devuelve las filas de tb_customer_sub indexadas por tb_search_form_cd */
function ventasSale(int $customerCd, array $overrides = []): array
{
    test()->post(route('admin.ventas.sales.store'), $overrides + [
        'update_date' => '2026/11/20', 'add_record_verify' => (string) random_int(1, PHP_INT_MAX), 'shop' => 1,
        'kaiin_cd' => (string) $customerCd, 'staff_cd' => '10',
    ])->assertRedirect(route('admin.ventas.sales.form', ['shop' => 1, 'status' => 'saved']));

    return DB::table('tb_customer_sub')->where('tb_customer_cd', $customerCd)->orderBy('cd')->get()->groupBy('tb_search_form_cd')->all();
}

// ---------------------------------------------------------------- メニュー管理 / 割引管理

it('carga メニュー管理 y 割引管理 con el formulario y la lista AJAX', function () {
    ventasMenu();
    ventasDiscount();

    $this->get(route('admin.ventas.menus.index', ['shop' => 1]))->assertOk()
        ->assertSee('■メニュー管理')->assertSee('メニュー登録情報')->assertSee('■本店')->assertSee('この内容で登録する');
    $this->get('/system/category1/karute_menu/karute_waribiki.php?shop=1')->assertOk()
        ->assertSee('■割引管理')->assertSee('割引額を歩合に含む');

    $this->post(route('legacy.ventas.menu-list'), ['shop_cd' => 1])->assertOk()
        ->assertSee('カット')->assertSee('5,000')->assertSee('技術')->assertSee('▲', false);
    $this->post(route('legacy.ventas.discount-list'), ['shop_cd' => 1])->assertOk()
        ->assertSee('学割')->assertSee('10％')->assertSee('割引額を歩合に含む');
    $this->post(route('legacy.ventas.menu-list'), [])->assertOk()->assertContent('');
});

it('da de alta, edita, ordena y borra メニュー como karute_menu_register.php', function () {
    ventasMenu(['name' => 'A', 'rank' => 1]);

    $this->post(route('admin.ventas.menus.store'), ['name' => ' ｶﾗｰ ', 'kingaku' => '8000', 'type' => '2', 'tb_shop_cd' => 1, 'act' => 'new'])
        ->assertRedirect(route('admin.ventas.menus.index', ['shop' => 1]));
    $menu = ServiceMenu::query()->where('name', 'カラー')->firstOrFail(); // form2Db: 半角カナ → 全角 y recorte
    expect($menu->kingaku)->toBe(8000)->and($menu->type)->toBe('2')->and($menu->rank)->toBe(2)
        ->and($menu->hyouji_flg)->toBe('1')->and($menu->touroku_date)->not->toBeEmpty();

    $this->get(route('admin.ventas.menus.index', ['shop' => 1, 'cd' => $menu->cd]))->assertOk()
        ->assertSee('この内容で修正する')->assertSee('value="カラー"', false);
    $this->post(route('admin.ventas.menus.update', $menu->cd), ['name' => 'カラー2', 'kingaku' => '9000', 'type' => '3', 'tb_shop_cd' => 1])
        ->assertRedirect();
    expect($menu->fresh()->name)->toBe('カラー2')->and($menu->fresh()->rank)->toBe(2);

    // ▲: intercambia con el anterior
    $this->post(route('legacy.ventas.menu-list'), ['shop_cd' => 1, 'send_type' => 'rankup', 'send_targetCd' => $menu->cd, 'send_vl' => 2])->assertOk();
    expect($menu->fresh()->rank)->toBe(1)->and(ServiceMenu::query()->where('name', 'A')->value('rank'))->toBe(2);

    // 削除: disp_flg 9, rank null y los siguientes suben
    $a = ServiceMenu::query()->where('name', 'A')->firstOrFail();
    $this->post(route('admin.ventas.menus.destroy', $menu->cd), ['act' => 'del', 'rank' => 1, 'tb_shop_cd' => 1])->assertRedirect();
    $deleted = ServiceMenu::withDeleted()->find($menu->cd);
    expect($deleted->disp_flg)->toBe('9')->and($deleted->rank)->toBeNull()->and($a->fresh()->rank)->toBe(1);
});

it('rechaza メニュー sin 名称 ni 金額 y graba 割引 con buai_flg', function () {
    $this->from(route('admin.ventas.menus.index', ['shop' => 1]))
        ->post(route('admin.ventas.menus.store'), ['name' => '', 'kingaku' => '', 'tb_shop_cd' => 1])
        ->assertSessionHasErrors(['name' => '「名称」が未入力です。', 'kingaku' => '「金額」が未入力です。']);

    $this->post(route('admin.ventas.discounts.store'), ['name' => '500円引き', 'value' => '500', 'type' => '2', 'buai_flg' => '2', 'tb_shop_cd' => 1])
        ->assertRedirect(route('admin.ventas.discounts.index', ['shop' => 1]));
    $discount = Discount::query()->firstOrFail();
    expect($discount->value)->toBe(500)->and($discount->type)->toBe('2')->and($discount->buai_flg)->toBe('2')->and($discount->rank)->toBe(1);
});

// ---------------------------------------------------------------- 売上データ作成

it('carga 売上データ作成 y su formulario emergente', function () {
    ventasMenu();
    ventasDiscount();

    $this->get('/system/category1/karute_menu/karute_new.php?shop=1')->assertOk()
        ->assertSee('■売上データ作成')->assertSee('売上データ作成フォームを開く');
    $this->get(route('admin.ventas.sales.form', ['shop' => 1, 'status' => 'saved']))->assertOk()
        ->assertSee('売上データ内容')->assertSee('担当スタッフ選択')->assertSee('山田')
        ->assertSee('menuData_', false)->assertSee('waribikiData_', false)->assertSee('登録しました。')
        ->assertSee('履歴がありません。');
});

it('graba la venta con las filas del legacy y la comisión de tb_staff.kingaku_buai', function () {
    DB::table('tb_staff')->where('cd', 10)->update(['kingaku_buai' => 30]);
    $customer = Customer::factory()->create(['tb_shop_cd' => 1]);
    $cut = ventasMenu(['name' => 'カット', 'kingaku' => 5000, 'type' => '1']);
    $shampoo = ventasMenu(['name' => 'シャンプー', 'kingaku' => 1500, 'type' => '2', 'rank' => 2]);
    $student = ventasDiscount(['name' => '学割', 'value' => 10, 'type' => '1', 'buai_flg' => '1']);
    $coupon = ventasDiscount(['name' => 'クーポン', 'value' => 300, 'type' => '2', 'buai_flg' => '2', 'rank' => 2]);

    $rows = ventasSale($customer->cd, [
        'menu_cd' => [$cut->cd, $shampoo->cd], 'waribiki_cd' => [$student->cd, $coupon->cd],
        'otherWaribikiValue' => '200', 'otherWaribikiType' => '2',
    ]);

    // subtotal 6500; 学割 650 (歩合に含む), クーポン 300 (含まない), その他 200 → total 5350, base comisión 6500-650-200 = 5650
    $staff = $rows[-1]->first();
    expect($staff->form_title)->toBe('スタッフ情報')->and($staff->text_data)->toBe('山田')->and($staff->array_data)->toBe('10')
        ->and((float) $staff->float_data)->toBe(30.0)->and($staff->rank)->toBe(1695) // (int)(5650 * 30 / 100)
        ->and($staff->category_inner_group)->toBe('1')->and($staff->update_date)->toBe('2026/11/20')
        ->and($staff->int_data)->toBe(1)->and($staff->tb_search_cate_cd)->toBe(-1);
    expect($rows[-2]->pluck('text_data')->all())->toBe(['カット', 'シャンプー'])
        ->and($rows[-2]->pluck('form_type')->all())->toBe(['技術', '物販']);
    expect($rows[-3]->pluck('float_data')->map(fn ($v) => (float) $v)->all())->toBe([650.0, 300.0])
        ->and($rows[-3]->pluck('form_type')->all())->toBe(['10％', '300円'])
        ->and($rows[-3]->pluck('pic_flg')->all())->toBe(['1', '2']);
    expect($rows[-4]->first()->form_type)->toBe('200円')->and($rows[-4]->first()->pic_flg)->toBe('2');
    $total = $rows[-5]->first();
    expect((float) $total->float_data)->toBe(5350.0)->and($total->rank)->toBe(5650)->and($total->form_title)->toBe('合計');

    // cd = createHatsuban (epoch + tienda + azar) y consecutivos
    $cds = DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->orderBy('cd')->pluck('cd');
    expect($cds)->toHaveCount(7)
        ->and($cds->last() - $cds->first())->toBe(6)
        ->and(strlen((string) $cds->first()))->toBe(19);

    // Segunda venta del mismo cliente: nº de cuenta 2
    $rows = ventasSale($customer->cd, ['menu_cd' => [$cut->cd]]);
    expect($rows[-5]->pluck('category_inner_group')->sort()->values()->all())->toBe(['1', '2']);
});

it('no graba dos veces el mismo add_record_verify ni ventas sin socio o con total negativo', function () {
    $customer = Customer::factory()->create();
    $menu = ventasMenu(['kingaku' => 1000]);
    $data = ['update_date' => '2026/11/20', 'add_record_verify' => '123', 'shop' => 1, 'kaiin_cd' => (string) $customer->cd, 'staff_cd' => '10', 'menu_cd' => [$menu->cd]];

    $this->post(route('admin.ventas.sales.store'), $data)->assertRedirect();
    $this->post(route('admin.ventas.sales.store'), $data)->assertRedirect();
    expect(DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->count())->toBe(1);

    $this->post(route('admin.ventas.sales.store'), ['kaiin_cd' => '', 'staff_cd' => '10'] + $data)
        ->assertOk()->assertSee('エラー発生しました。もう一度やり直してください。');
    $this->post(route('admin.ventas.sales.store'), ['add_record_verify' => '999', 'otherWaribikiValue' => '2000', 'otherWaribikiType' => '2'] + $data)
        ->assertOk()->assertSee('合計の値がマイナスのため登録出来ません。');
    expect(DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->count())->toBe(1);
});

it('busca socios para el formulario (ajax_search_kaiin.php) y lista la スタッフ (ajax_staff.php)', function () {
    Customer::factory()->create(['name' => '佐藤 花子', 'name_kana' => 'サトウ ハナコ']);
    Customer::factory()->create(['name' => '鈴木 一郎', 'name_kana' => 'スズキ イチロウ']);

    $this->post(route('legacy.ventas.search-kaiin'), ['keyword' => 'さとう', 'shop_cd' => 1])->assertOk()
        ->assertSee('佐藤 花子')->assertDontSee('鈴木 一郎')->assertSee('addKaiin', false);
    $this->post(route('legacy.ventas.search-kaiin'), ['keyword' => 'zzz'])->assertOk()->assertSee('データが見つかりませんでした。');

    $this->post(route('legacy.ventas.staff-options'), ['shop_cd' => 1])->assertOk()
        ->assertSee('▼スタッフ選択')->assertSee('<option value="10">山田</option>', false);
    $this->post(route('legacy.ventas.staff-options'), ['shop_cd' => 99])->assertOk()->assertSee('登録されていません');
});

// ---------------------------------------------------------------- 売上データ削除

it('lista y borra ventas (karute_delete_list.php / karute_delete_register.php)', function () {
    $customer = Customer::factory()->create(['name' => '田中 太郎']);
    $menu = ventasMenu(['kingaku' => 3000]);
    ventasSale($customer->cd, ['menu_cd' => [$menu->cd]]);

    $this->get('/system/category1/karute_menu/karute_delete_list.php?shop=1')->assertOk()
        ->assertSee('■売上データ削除')->assertSee('■検索結果')->assertSee('田中 太郎')->assertSee('3,000円')->assertSee('山田');
    $today = Carbon::now('Asia/Tokyo')->format('Y/m/d');
    $this->post(route('admin.ventas.sales.index'), ['shop' => 1, 'date1' => '2000/01/01', 'date2' => '2000/01/02'])
        ->assertOk()->assertSee('履歴がありません。');
    $this->post(route('admin.ventas.sales.index'), ['shop' => 1, 'date1' => $today, 'date2' => $today, 'staff' => '10'])
        ->assertOk()->assertSee('田中 太郎');

    $this->post(route('admin.ventas.sales.destroy'), ['act' => 'del', 'shop' => 1, 'cd' => $customer->cd, 'group' => '1'])
        ->assertRedirect();
    expect(DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->where('disp_flg', '<>', '9')->count())->toBe(0);
    $this->get(route('admin.ventas.sales.index', ['shop' => 1]))->assertOk()->assertSee('履歴がありません。');
});

// ---------------------------------------------------------------- 経費管理

it('da de alta, edita y borra 経費 (keihi*.php)', function () {
    $this->get('/system/category1/karute_menu/keihi.php')->assertOk()
        ->assertSee('■経費管理')->assertSee('■経費一覧')->assertSee('データがありません。');

    $this->post(route('admin.ventas.expenses.store'), ['shop' => '1', 'date1' => '2026/11/10', 'mokuteki' => '消耗品', 'price' => '1200', 'act' => 'new'])
        ->assertRedirect(route('admin.ventas.expenses.index', ['status' => 'saved']));
    $row = DB::table('tb_customer_sub')->where('tb_search_form_cd', -10)->first();
    expect($row->tb_search_cate_cd)->toBe(-2)->and((float) $row->float_data)->toBe(1200.0)->and($row->text_data)->toBe('消耗品')
        ->and($row->update_date)->toBe('2026/11/10')->and($row->int_data)->toBe(1)->and($row->disp_flg)->toBe('1');

    $this->get(route('admin.ventas.expenses.index', ['cd' => $row->cd]))->assertOk()
        ->assertSee('この内容で修正する')->assertSee('消耗品')->assertSee('1,200');
    $this->post(route('admin.ventas.expenses.update', $row->cd), ['shop' => '1', 'date1' => '2026/11/11', 'mokuteki' => '備品', 'price' => '800'])->assertRedirect();
    expect(DB::table('tb_customer_sub')->where('cd', $row->cd)->value('text_data'))->toBe('備品');

    $this->from(route('admin.ventas.expenses.index'))
        ->post(route('admin.ventas.expenses.store'), ['shop' => '', 'date1' => '2026/11/10', 'price' => '0'])
        ->assertSessionHasErrors(['shop' => '・店舗を選択してください。', 'price' => '・使用金額を入力してください。']);

    $this->post(route('admin.ventas.expenses.destroy', $row->cd), ['delete_flg' => 'del', 'cd' => $row->cd])->assertRedirect();
    expect(DB::table('tb_customer_sub')->where('cd', $row->cd)->value('disp_flg'))->toBe('9');
});

// ---------------------------------------------------------------- スタッフ別集計 / 日報・週報・月報

it('calcula スタッフ別集計 y 日報・週報・月報 como ajax_staffTotal.php / ajax_report.php', function () {
    DB::table('tb_staff')->where('cd', 10)->update(['kingaku_buai' => 10]);
    $customer = Customer::factory()->create(['name' => '高橋 次郎']);
    $menu = ventasMenu(['name' => 'カット', 'kingaku' => 4000]);
    $discount = ventasDiscount(['value' => 500, 'type' => '2', 'buai_flg' => '1']);
    ventasSale($customer->cd, ['menu_cd' => [$menu->cd], 'waribiki_cd' => [$discount->cd], 'update_date' => '2026/11/01']);
    ventasSale($customer->cd, ['menu_cd' => [$menu->cd], 'otherWaribikiValue' => '10', 'otherWaribikiType' => '1', 'update_date' => '2026/11/02']);
    $this->post(route('admin.ventas.expenses.store'), ['shop' => '1', 'date1' => '2026/11/02', 'mokuteki' => '消耗品', 'price' => '1000']);

    $this->get('/system/category1/karute_menu/staff_total.php')->assertOk()->assertSee('■スタッフ別集計')->assertSee('(最大60日分)');
    $this->get('/system/category1/karute_menu/report.php')->assertOk()->assertSee('■日報・週報・月報')->assertSee('全店舗');

    // 3500 + 3600 = 7100 de 売上; comisión (int)(3500*10/100) + (int)(3600*10/100) = 350 + 360
    $this->post(route('legacy.ventas.staff-total'), ['shop_cd' => 1, 'staff_cd' => 10, 'date1' => '2026/11/01', 'date2' => '2026/12/31'])
        ->assertOk()->assertSee('■山田&emsp;2026/11/01 ～ 2026/12/31の集計', false)
        ->assertSee('7,100')->assertSee('710')->assertSee('高橋 次郎')->assertSee('カット')->assertSee('8,000');
    $this->post(route('legacy.ventas.staff-total'), ['staff_cd' => 10])->assertOk()->assertSee('集計期間を入力してください。');
    $this->post(route('legacy.ventas.staff-total'), ['staff_cd' => 10, 'date1' => '2026/13/01'])->assertOk()->assertSee('日付：不適切な形式です。');

    // 売上 8000 - 割引 500 - その他 400 - 経費 1000 = 純利益 6100; - 歩合 710 = 粗利 5390. Sin límite de 60 días
    $this->post(route('legacy.ventas.report'), ['shop_cd' => -1, 'date1' => '2026/01/01', 'date2' => '2026/12/31'])
        ->assertOk()->assertSee('■全店舗')->assertSee('8,000円')->assertSee('500円')->assertSee('400円')->assertSee('1,000円')
        ->assertSee('6,100円')->assertSee('710円')->assertSee('5,390円')->assertSee('2026/11/02');
    $this->post(route('legacy.ventas.report'), ['shop_cd' => 1, 'date1' => '2020/01/01'])->assertOk()->assertSee('■本店')->assertSee('データがありません。');
});

it('obliga a la tienda de la sesión al personal que no es master', function () {
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert(['cd' => 1033, 'hyouji_flg' => '1']); // sin filas en tb_kengen: todos los permisos
    ventasMenu(['name' => '支店メニュー', 'tb_shop_cd' => 2]);
    ventasMenu(['name' => '本店メニュー', 'tb_shop_cd' => 1]);
    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    $this->get(route('admin.ventas.menus.index', ['shop' => 2]))->assertOk()
        ->assertSee('■本店')->assertDontSee('※登録・閲覧したい店舗を選択してください。');
    $this->post(route('legacy.ventas.menu-list'), ['shop_cd' => 2])->assertOk()
        ->assertSee('本店メニュー')->assertDontSee('支店メニュー');

    $this->post(route('admin.ventas.menus.store'), ['name' => '新メニュー', 'kingaku' => '100', 'type' => '1', 'tb_shop_cd' => 2])->assertRedirect();
    expect(ServiceMenu::query()->where('name', '新メニュー')->value('tb_shop_cd'))->toBe(1);

    $this->post(route('admin.ventas.expenses.store'), ['shop' => '2', 'date1' => '2026/11/10', 'mokuteki' => 'x', 'price' => '100'])->assertRedirect();
    expect(DB::table('tb_customer_sub')->where('tb_search_form_cd', -10)->value('int_data'))->toBe(1);
});
