<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// C4-b: カルテ de la pestaña 5 (inc/karute_new.php, karute_update.php, karute_frm.php, karute_register.php),
// 美容: 施術カテゴリー (biyou/sejutsu_cate*.php) y カルテ詳細 (biyou/karute_detail.php)

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** 施術: uno de todas las tiendas, uno de la tienda 1, uno de la 2 y uno borrado */
function clientesSejutsuSeed(): void
{
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    DB::table('tb_sejutsu_cate')->insert([
        ['cd' => 1, 'name' => 'カット', 'tb_shop_cd' => null, 'disp_flg' => '1'],
        ['cd' => 2, 'name' => 'カラー&amp;パーマ', 'tb_shop_cd' => 1, 'disp_flg' => '1'],
        ['cd' => 3, 'name' => '支店ヘッドスパ', 'tb_shop_cd' => 2, 'disp_flg' => '1'],
        ['cd' => 4, 'name' => '削除済み', 'tb_shop_cd' => 1, 'disp_flg' => '9'],
    ]);
}

// ---------------------------------------------------------------- カルテ

it('カルテ: la pestaña 5 enlaza al alta, que carga con los textos del legacy y la fecha de hoy', function () {
    clientesSejutsuSeed();
    $customer = Customer::factory()->create(['id' => '1100777', 'name' => '佐藤花子']);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => 5]))->assertOk()
        ->assertSee(route('admin.clientes.kartes.create', $customer->cd), false);

    $this->get(route('admin.clientes.kartes.create', [$customer->cd, 'kaiin_cd' => $customer->cd, 'tab' => 5]))->assertOk()
        ->assertSee('■カルテ 新規登録')->assertSee('1100777')->assertSee('佐藤花子')
        ->assertSee('value="2026/05/20"', false)->assertSee('▼店舗選択')->assertSee('支店')
        ->assertSee('value="この内容で登録する"', false)
        ->assertSee('system/category1/inc/ajax_category.php', false)
        ->assertSee('<input type="hidden" name="tab" value="5">', false);
});

it('カルテ: comprueba como el legacy y registra con hatuban, volviendo a la ficha', function () {
    clientesSejutsuSeed();
    $customer = Customer::factory()->create();

    $this->post(route('admin.clientes.kartes.store', $customer->cd), ['tb_shop_cd' => '', 'hiduke' => '2026/02/30', 'tb_sejutsu_cate_cd' => '', 'act' => 'up'])
        ->assertOk()
        ->assertSee('※店舗を選択して下さい。')->assertSee('※施術を選択して下さい。')->assertSee('※2026/02/30は不適切な形式です。');
    $this->post(route('admin.clientes.kartes.store', $customer->cd), ['tb_shop_cd' => '1', 'hiduke' => '', 'tb_sejutsu_cate_cd' => '2'])
        ->assertSee('※必須入力項目です。')
        // con la tienda elegida, solo sus 施術 y los de todas (sin los borrados)
        ->assertSee('カット')->assertSee('カラー&amp;パーマ', false)->assertDontSee('支店ヘッドスパ')->assertDontSee('削除済み');
    expect(DB::table('tb_karute')->count())->toBe(0);

    DB::table('hatuban')->insert(['koumoku' => 'tb_karute', 'kazu' => 7]);
    $this->post(route('admin.clientes.kartes.store', $customer->cd), [
        'tb_shop_cd' => '1', 'hiduke' => '2026/05/01', 'tb_sejutsu_cate_cd' => '2', 'comment' => " 前回より短め\n\"注意\" ", 'kaiin_cd' => $customer->cd, 'tab' => '5', 'act' => 'up',
    ])->assertOk()->assertSee('登録中です。')
        ->assertSee('karute_cd=7', false)->assertSee('from=reg', false);

    $row = DB::table('tb_karute')->where('cd', 7)->first();
    expect($row->tb_customer_cd)->toBe($customer->cd)
        ->and($row->tb_shop_cd)->toBe(1)
        ->and($row->tb_sejutsu_cate_cd)->toBe(2)
        ->and($row->tb_staff_cd)->toBeNull()
        ->and($row->comment)->toBe("前回より短め\n注意")
        ->and($row->uketsuke_date)->toBe(Carbon::parse('2026-05-01 00:00', 'Asia/Tokyo')->getTimestamp())
        ->and($row->disp_flg)->toBe('1')
        ->and((int) $row->touroku_date)->toBe(now()->getTimestamp())
        ->and((int) DB::table('hatuban')->where('koumoku', 'tb_karute')->value('kazu'))->toBe(8);

    // La ficha muestra la カルテ con el nombre del 施術 sin doble escape
    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => 5, 'karute_cd' => 7]))->assertOk()
        ->assertSee('カルテ番号：　7')->assertSee('カラー&amp;パーマ', false)->assertDontSee('&amp;amp;', false)
        ->assertSee(route('admin.clientes.kartes.edit', [$customer->cd, 7]), false);
});

it('カルテ: modifica la existente y conserva la ventana emergente', function () {
    clientesSejutsuSeed();
    $customer = Customer::factory()->create();
    DB::table('tb_karute')->insert(['cd' => 3, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => 1, 'tb_sejutsu_cate_cd' => 1, 'comment' => '旧メモ',
        'uketsuke_date' => Carbon::parse('2026-04-10', 'Asia/Tokyo')->getTimestamp(), 'touroku_date' => 100, 'disp_flg' => '1']);

    $this->get(route('admin.clientes.kartes.edit', [$customer->cd, 3, 'wintype' => 'no_headers']))->assertOk()
        ->assertSee('value="2026/04/10"', false)->assertSee('旧メモ')->assertSee('name="_method" value="PUT"', false)
        ->assertDontSee('leftNavi', false);

    $this->put(route('admin.clientes.kartes.update', [$customer->cd, 3]), [
        'tb_shop_cd' => '1', 'hiduke' => '2026/04/11', 'tb_sejutsu_cate_cd' => '2', 'comment' => '新メモ', 'tab' => '5', 'wintype' => 'no_headers',
    ])->assertOk()->assertSee('wintype=no_headers', false)->assertSee('karute_cd=3', false);

    $row = DB::table('tb_karute')->where('cd', 3)->first();
    expect($row->tb_sejutsu_cate_cd)->toBe(2)->and($row->comment)->toBe('新メモ')
        ->and((int) $row->touroku_date)->toBe(100)
        ->and($row->uketsuke_date)->toBe(Carbon::parse('2026-04-11', 'Asia/Tokyo')->getTimestamp());

    // La カルテ de otro cliente no se puede abrir con este
    $other = Customer::factory()->create();
    $this->get(route('admin.clientes.kartes.edit', [$other->cd, 3]))->assertNotFound();
});

it('カルテ: sin master la tienda es la de la sesión y no se abren カルテ de otra tienda', function () {
    clientesSejutsuSeed();
    $customer = Customer::factory()->create();
    DB::table('tb_karute')->insert(['cd' => 5, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => 2, 'tb_sejutsu_cate_cd' => 3, 'uketsuke_date' => 0, 'disp_flg' => '1']);
    auth('admin')->logout();
    loginAs('staff10', 'x');

    $this->get(route('admin.clientes.kartes.create', $customer->cd))->assertOk()
        ->assertSee('<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="1">', false)->assertDontSee('支店');
    $this->post(route('admin.clientes.kartes.store', $customer->cd), ['tb_shop_cd' => '2', 'hiduke' => '2026/05/02', 'tb_sejutsu_cate_cd' => '1'])->assertOk();
    expect(DB::table('tb_karute')->where('cd', '<>', 5)->value('tb_shop_cd'))->toBe(1);

    $this->get(route('admin.clientes.kartes.edit', [$customer->cd, 5]))->assertForbidden();
});

it('カルテ詳細 (biyou/karute_detail.php) muestra la カルテ, también en su URL legacy', function () {
    clientesSejutsuSeed();
    $customer = Customer::factory()->create(['id' => '1100888', 'name' => '鈴木一郎']);
    DB::table('tb_karute')->insert(['cd' => 9, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => 2, 'tb_sejutsu_cate_cd' => 3, 'comment' => "一行目\n<b>二行目</b>",
        'uketsuke_date' => Carbon::parse('2026-03-03', 'Asia/Tokyo')->getTimestamp(), 'disp_flg' => '1']);

    $this->get(route('admin.clientes.kartes.show', 9))->assertOk()
        ->assertSee('■カルテ 詳細')->assertSee('1100888')->assertSee('鈴木一郎')->assertSee('支店')
        ->assertSee('2026/03/03')->assertSee('支店ヘッドスパ')->assertSee('一行目<br />', false)->assertSee('&lt;b&gt;二行目', false);
    $this->get('/system/category1/biyou/karute_detail.php?cd=9')->assertOk()->assertSee('■カルテ 詳細');
    $this->get(route('admin.clientes.kartes.show', 99))->assertNotFound();
});

// ---------------------------------------------------------------- 施術カテゴリー

it('施術カテゴリー: carga con los textos del legacy (también en su URL legacy)', function () {
    clientesSejutsuSeed();

    $this->get(route('admin.clientes.service-categories.index'))->assertOk()
        ->assertSee('予約カテゴリー管理')->assertSee('■施術カテゴリー登録')->assertSee('■施術カテゴリー一覧')
        ->assertSee('最新から3件')->assertSee('カラー&amp;パーマ', false)->assertDontSee('削除済み')
        ->assertSee('<option value="-1" >全店舗</option>', false)->assertSee('value="この内容で登録する"', false);
    $this->get('/system/category1/biyou/sejutsu_cate.php?cd=1')->assertOk()
        ->assertSee('value="この内容で修正する"', false)->assertSee('value="カット"', false)
        ->assertSee('<option value="-1" selected>全店舗</option>', false);
});

it('施術カテゴリー: registra con hatuban y get_form2(), modifica y borra (disp_flg 9)', function () {
    clientesSejutsuSeed();
    DB::table('hatuban')->insert(['koumoku' => 'tb_sejutsu_cate', 'kazu' => 10]);

    $this->post(route('admin.clientes.service-categories.store'), ['shop_cd' => '-1', 'cate_name' => ' ネイル<ケア> ', 'act' => 'new'])
        ->assertOk()->assertSee('登録中です。')->assertSee('content="1; URL=', false);
    $row = DB::table('tb_sejutsu_cate')->where('cd', 10)->first();
    expect($row->name)->toBe('ネイル&lt;ケア&gt;')->and($row->tb_shop_cd)->toBeNull()->and($row->disp_flg)->toBe('1');

    // Sin nombre no se registra (submitChk del legacy)
    $this->post(route('admin.clientes.service-categories.store'), ['shop_cd' => '1', 'cate_name' => ''])->assertRedirect();
    expect(DB::table('tb_sejutsu_cate')->count())->toBe(5);

    $this->put(route('admin.clientes.service-categories.update', 10), ['shop_cd' => '2', 'cate_name' => 'ネイル', 'act' => 'up', 'cd' => 10])->assertOk();
    $row = DB::table('tb_sejutsu_cate')->where('cd', 10)->first();
    expect($row->name)->toBe('ネイル')->and($row->tb_shop_cd)->toBe(2);

    $this->delete(route('admin.clientes.service-categories.destroy', 10), ['delete_flg' => 'del'])->assertOk()->assertSee('削除中です。');
    expect(DB::table('tb_sejutsu_cate')->where('cd', 10)->value('disp_flg'))->toBe('9');
});

it('施術カテゴリー: sin master solo ve y registra en su tienda', function () {
    clientesSejutsuSeed();
    DB::table('hatuban')->insert(['koumoku' => 'tb_sejutsu_cate', 'kazu' => 20]);
    auth('admin')->logout();
    loginAs('staff10', 'x');

    $this->get(route('admin.clientes.service-categories.index'))->assertOk()
        ->assertSee('<input type="hidden" name="shop_cd" value="1">', false)
        ->assertSee('最新から1件')->assertDontSee('支店ヘッドスパ')->assertDontSee('全店舗</option>', false);

    $this->post(route('admin.clientes.service-categories.store'), ['shop_cd' => '-1', 'cate_name' => '眉カット'])->assertOk();
    expect(DB::table('tb_sejutsu_cate')->where('cd', 20)->value('tb_shop_cd'))->toBe(1);

    $this->delete(route('admin.clientes.service-categories.destroy', 3))->assertNotFound();
});
