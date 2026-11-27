<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Illuminate\Support\Facades\DB;

// C4-a: 追加情報 de varios registros (add_record_new/_confirm/_register.php, del_record.php),
// <option> de ajax_category.php / ajax_staff.php y 顧客検索 de la カルテ (karute_customer_search.php)

beforeEach(function () {
    reservasLogin();
});

/** Categoría 70 de varios registros con un campo obligatorio (テキスト) y uno de casillas */
function clientesCustomRecordCategory(): void
{
    DB::table('tb_search_cate')->insert(['cd' => 70, 'name' => '施術履歴', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'record_type' => '1', 'disp_column' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert([
        ['cd' => 80, 'tb_search_cate_cd' => 70, 'name' => '施術内容', 'form_type' => 'テキスト', 'hissu' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1, 'flds_row' => 0, 'data' => null, 'titles' => null, 'comment' => '自由入力'],
        ['cd' => 81, 'tb_search_cate_cd' => 70, 'name' => '部位', 'form_type' => 'チェックボックス', 'hissu' => null, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 2, 'flds_row' => 3, 'data' => '肩<>腰<>首', 'titles' => '肩<>腰<>首', 'comment' => null],
    ]);
}

function clientesCustomRecordRows(int $customerCd, string $group): \Illuminate\Support\Collection
{
    return DB::table('tb_customer_sub')->where('tb_customer_cd', $customerCd)->where('tb_search_cate_cd', 70)
        ->where('category_inner_group', $group)->orderBy('tb_search_form_cd')->get();
}

it('add_record_new: formulario con los textos del legacy', function () {
    clientesCustomRecordCategory();
    $customer = Customer::factory()->create(['id' => '1100700', 'name' => '佐藤花子']);

    $this->post(route('admin.clientes.custom-records.create'), ['kaiin_cd' => $customer->cd, 'tab' => 'custom', 'act' => 'new', 'search_cate_cd' => 70])
        ->assertOk()
        ->assertSee('■顧客登録情報')
        ->assertSee('<legend>施術履歴</legend>', false)
        ->assertSee('佐藤花子')
        ->assertSee('name="form80"', false)
        ->assertSee('name="form81_1"', false)
        ->assertSee('value="この内容で確認する"', false);

    // Categoría inexistente
    $this->post(route('admin.clientes.custom-records.create'), ['kaiin_cd' => $customer->cd, 'search_cate_cd' => 999])->assertNotFound();
});

it('add_record_confirm: con errores vuelve al formulario; sin errores confirma', function () {
    clientesCustomRecordCategory();
    $customer = Customer::factory()->create();
    $base = ['kaiin_cd' => $customer->cd, 'tab' => 'custom', 'act' => 'new', 'search_cate_cd' => 70];

    $this->post(route('admin.clientes.custom-records.confirm'), $base + ['form80' => ''])
        ->assertOk()->assertSee('※必須項目です。')->assertSee('自由入力')->assertSee('name="form80"', false);

    $this->post(route('admin.clientes.custom-records.confirm'), $base + ['form80' => 'カット', 'form81_1' => '腰'])
        ->assertOk()
        ->assertDontSee('※必須項目です。')
        ->assertSee('value="この内容で登録する"', false)
        ->assertSee('name="add_record_verify"', false)
        ->assertSee('<input type="hidden" name="form80" value="カット" />', false)
        ->assertSee('value="書き直す"', false);
});

it('add_record_register: crea el registro 1 y luego el 2; act=up corrige el mismo grupo', function () {
    clientesCustomRecordCategory();
    $customer = Customer::factory()->create();
    $base = ['kaiin_cd' => $customer->cd, 'tab' => 'custom', 'act' => 'new', 'search_cate_cd' => 70];

    // Sin add_record_verify el legacy lo rechazaba
    $this->post(route('admin.clientes.custom-records.store'), $base + ['form80' => 'x'])->assertSee('不正アクセス！！');

    $this->post(route('admin.clientes.custom-records.store'), $base + ['form80' => 'カット', 'form81_0' => '肩', 'form81_2' => '首', 'add_record_verify' => '100'])
        ->assertOk()->assertSee('登録中です。')
        ->assertSee('fukusu_group_cd=1', false);
    $rows = clientesCustomRecordRows($customer->cd, '1');
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->text_data)->toBe('カット')
        ->and($rows[0]->form_title)->toBe('施術履歴<>施術内容')
        ->and($rows[0]->disp_flg)->toBe('1')
        ->and($rows[1]->text_data)->toBe('肩<>首<>');

    // Mismo token (doble clic): no registra otra vez
    $this->post(route('admin.clientes.custom-records.store'), $base + ['form80' => 'カット', 'add_record_verify' => '100'])->assertOk();
    expect(DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->count())->toBe(2);

    $this->post(route('admin.clientes.custom-records.store'), $base + ['form80' => 'パーマ', 'add_record_verify' => '101']);
    expect(clientesCustomRecordRows($customer->cd, '2')[0]->text_data)->toBe('パーマ');

    // 修正: el formulario trae los valores guardados y el registro conserva su grupo y su fecha de alta
    $touroku = clientesCustomRecordRows($customer->cd, '1')[0]->touroku_date;
    $this->post(route('admin.clientes.custom-records.create'), ['act' => 'up', 'fukusu_group_cd' => '1'] + $base)
        ->assertOk()->assertSee('value="カット"', false);
    $this->post(route('admin.clientes.custom-records.store'), ['act' => 'up', 'fukusu_group_cd' => '1', 'form80' => 'カラー', 'add_record_verify' => '102'] + $base)
        ->assertOk();
    $rows = clientesCustomRecordRows($customer->cd, '1');
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->text_data)->toBe('カラー')
        ->and($rows[0]->touroku_date)->toBe($touroku)
        ->and($rows[1]->text_data)->toBe('');
});

it('del_record: borra el registro (disp_flg 9) y la pestaña muestra los botones con las rutas', function () {
    clientesCustomRecordCategory();
    $customer = Customer::factory()->create();
    $base = ['kaiin_cd' => $customer->cd, 'tab' => 'custom', 'act' => 'new', 'search_cate_cd' => 70];
    $this->post(route('admin.clientes.custom-records.store'), $base + ['form80' => 'カット', 'add_record_verify' => '200']);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => 'custom', 'search_cate_cd' => 70]))
        ->assertOk()
        ->assertSee('action="' . route('admin.clientes.custom-records.create') . '"', false)
        ->assertSee('action="' . route('admin.clientes.custom-records.destroy') . '"', false);

    $this->post(route('admin.clientes.custom-records.destroy'), ['kaiin_cd' => $customer->cd, 'tab' => 'custom', 'search_cate_cd' => 70, 'fukusu_group_cd' => '1'])
        ->assertOk()->assertSee('削除中です。')->assertSee('content="2;', false);
    expect(clientesCustomRecordRows($customer->cd, '1')->pluck('disp_flg')->unique()->all())->toBe(['9']);
});

it('ajax_category.php y ajax_staff.php devuelven las <option> del legacy', function () {
    DB::table('tb_sejutsu_cate')->insert([
        ['cd' => 1, 'name' => 'カット', 'tb_shop_cd' => null, 'disp_flg' => '1'],
        ['cd' => 2, 'name' => 'カラー', 'tb_shop_cd' => 1, 'disp_flg' => '1'],
        ['cd' => 3, 'name' => '他店', 'tb_shop_cd' => 2, 'disp_flg' => '1'],
        ['cd' => 4, 'name' => '削除', 'tb_shop_cd' => null, 'disp_flg' => '9'],
    ]);
    $html = $this->post('/system/category1/inc/ajax_category.php', ['shop_cd' => 1])->assertOk()->getContent();
    expect($html)->toContain('<option value="">▼施術選択</option>')
        ->toContain('<option value="1">カット</option>')
        ->toContain('<option value="2">カラー</option>')
        ->not->toContain('他店')->not->toContain('削除');
    $this->post('/system/category1/customer/ajax_category.php', ['shop_cd' => 2])->assertSee('他店');
    $this->post(route('admin.clientes.options.categories'), ['shop_cd' => 1])->assertSee('カラー');
});

it('ajax_staff.php: sin personal con reserva, opción vacía; con él, la lista', function () {
    expect($this->post('/system/category1/customer/ajax_staff.php', ['shop_cd' => 1])->assertOk()->getContent())
        ->toBe('<option value="">&nbsp;</option>');

    DB::table('tb_staff')->where('cd', 10)->update(['reserve_flg' => '1']);
    expect($this->post(route('admin.clientes.options.staff'), ['shop_cd' => 1])->getContent())
        ->toContain('<option value="">▼担当者選択</option>')
        ->toContain('<option value="10">山田</option>');
});

it('karute_customer_search.php: buscador y lista de clientes con el botón 詳細', function () {
    Customer::factory()->create(['id' => '1100801', 'name' => '検索太郎', 'email1' => 'taro@example.com', 'mailmaga_flg' => '1']);

    $this->get('/system/category1/customer/karute_customer_search.php')
        ->assertOk()->assertSee('■顧客検索')->assertSee('value="この条件で検索する"', false)->assertDontSee('■顧客一覧');

    $this->post(route('admin.clientes.lookup'), ['act' => 'up', 'se_flg' => '1', 'kensu' => '10'])
        ->assertOk()
        ->assertSee('件見つかりました。')
        ->assertSee('■顧客一覧')
        ->assertSee('検索太郎')
        ->assertSee('taro@...')
        ->assertSee('icon_syosai.gif');
});
