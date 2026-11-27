<?php

use App\Models\Customers\Customer;
use App\Services\Customers\CustomerRegistrationService;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Reservations/helpers.php';


/** Campo personalizado テキスト (categoría 50, campo 60) como los de 顧客追加情報設定 */
function clientesAltaCampoTexto(string $hissu = '1'): void
{
    DB::table('tb_search_cate')->insert(['cd' => 50, 'name' => '追加情報', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'record_type' => '', 'disp_column' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert(['cd' => 60, 'tb_search_cate_cd' => 50, 'name' => '趣味', 'form_type' => 'テキスト', 'hissu' => $hissu, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1, 'flds_row' => 0, 'flds_col' => 0]);
}

/** POST mínimo del formulario de alta (form.blade.php) */
function clientesAltaPost(array $extra = []): array
{
    return $extra + [
        'id' => '1100001', 'kanri_id' => 'K-1', 'tb_shop_cd' => '1', 'type' => '個人',
        'name_kana' => 'やまだ はなこ', 'name' => '山田 花子', 'zip' => '1000001', 'pref' => '東京都', 'add' => '千代田区',
        'add_kana' => '', 'add_number' => '1-1', 'add_build_kana' => '', 'add_build' => '',
        'tel' => '03-1234-5678', 'fax' => '', 'email1' => 'hanako@example.com', 'email2' => '',
        'kojin_birth_y' => '1990', 'kojin_birth_m' => '4', 'kojin_birth_d' => '8', 'kojin_sex' => '女性', 'kojin_bloodtype' => 'A',
        'kojin_mobile' => '090-1111-2222', 'kojin_mail' => '', 'job' => '', 'kinmusaki_name_kana' => '', 'kinmusaki_name' => '',
        'kinmusaki_gyoushu' => '', 'kinmusaki_tel' => '', 'kinmusaki_fax' => '',
        'houjin_setsuritsu_y' => '', 'houjin_setsuritsu_m' => '', 'houjin_setsuritsu_d' => '', 'houjin_shihon' => '',
        'daihyousha_name_kana' => '', 'daihyousha_name' => '', 'daihyousha_birth_y' => '', 'daihyousha_birth_m' => '', 'daihyousha_birth_d' => '',
        'houjin_gyoushu' => '', 'houjin_busho' => '', 'tantou_name_kana' => '', 'tantou_name' => '', 'tantou_tel' => '', 'tantou_fax' => '', 'tantou_mail' => '',
        'mailmaga_flg' => '1', 'mailmaga_email_type' => 'パソコン', 'tb_customer_group_cd' => '', 'tb_raiten_douki_master_cd' => '',
        'bikou' => "メモ \"引用\" 'x'", 'searchKaiin_cd' => '', 'searchKaiin_name' => '', 'login_id' => '1100001', 'login_pass' => '1234',
        'touroku_kubun' => '2', 'customer_cd' => '', 'kaiin_cd' => '', 'act' => 'new',
    ];
}

beforeEach(function () {
    reservasLogin();
});

it('muestra el alta con el 会員番号 de createID y la contraseña de 4 cifras', function () {
    Customer::factory()->create(['cd' => 5, 'id' => '1100001']);

    $response = $this->get(route('admin.clientes.create'))
        ->assertOk()
        ->assertSee('■顧客新規登録', false)
        ->assertSee('この内容で確認する')
        ->assertSee('個人・法人')
        ->assertSee('value="1100002"', false);

    expect($response->getContent())->toMatch('/name="login_pass" id="login_pass" value="\d{4}"/');

    // URL del legacy (menú lateral) y ?tel= de CTI
    $this->get('/system/category1/customer/customer_new.php?tel=0311112222')
        ->assertOk()->assertSee('value="0311112222"', false);
});

it('createID cuenta todas las filas de tb_customer y antepone tienda y 1', function () {
    $service = app(CustomerRegistrationService::class);
    expect($service->createId(3))->toBe('3100001');

    Customer::factory()->count(2)->create();
    Customer::factory()->create(['disp_flg' => '9']);
    expect($service->createId(12))->toBe('12100004');
});

it('repinta el formulario con los errores del legacy al confirmar', function () {
    clientesAltaCampoTexto();
    Customer::factory()->create(['email1' => 'hanako@example.com']);

    $this->post(route('admin.clientes.confirm'), clientesAltaPost(['zip' => '123', 'add_kana' => 'ちよだ', 'login_pass' => '', 'tel' => '03-ABC']))
        ->assertOk()
        ->assertSee('■顧客新規登録', false)
        ->assertSee('※既に使用されているメールアドレスです。')
        ->assertSee('※は半角数字7文字で入力して下さい。')
        ->assertSee('※全角カタカナで入力して下さい。')
        ->assertSee('※半角数字で入力して下さい。')
        ->assertSee('※必須入力項目です。')
        ->assertSee('<br /><font color=red>※必須項目です。</font>', false)
        ->assertSee('class="Ng"', false)
        ->assertDontSee('この内容で登録する');
});

it('confirma con los datos normalizados como el legacy', function () {
    clientesAltaCampoTexto();

    $this->post(route('admin.clientes.confirm'), clientesAltaPost(['form60' => ' 読書 ']))
        ->assertOk()
        ->assertSee('■顧客新規登録 確認', false)
        ->assertSee('ヤマダ ハナコ')
        ->assertSee('0312345678')
        ->assertSee('1990/04/08')
        ->assertSee('読書')
        ->assertSee('name="tel" value="0312345678"', false)
        ->assertSee('この内容で登録する')
        ->assertSee('書き直す');
});

it('書き直す vuelve al formulario con lo enviado', function () {
    $this->post(route('admin.clientes.rewrite'), clientesAltaPost(['name' => '山田 花子', 'modoru' => '書き直す', 'searchKaiin_cd' => '7', 'searchKaiin_name' => '【1100007】  紹介 太郎']))
        ->assertOk()
        ->assertSee('■顧客新規登録', false)
        ->assertSee('value="山田 花子"', false)
        ->assertSee('setMember(', false);
});

it('registra el cliente como customer_new_register.php', function () {
    clientesAltaCampoTexto();
    $referrer = Customer::factory()->create(['cd' => 7]);

    $response = $this->post(route('admin.clientes.store'), clientesAltaPost(['form60' => '読書', 'searchKaiin_cd' => '7', 'searchKaiin_name' => 'x']));

    $customer = Customer::query()->where('email1', 'hanako@example.com')->firstOrFail();
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain((string) $customer->cd)->toContain('from=reg');

    // cd de createHatsuban: epoch + tienda 001 + 6 cifras
    expect((string) $customer->cd)->toMatch('/^\d{10}001\d{6}$/')
        ->and($customer->id)->toBe('1100002')
        ->and($customer->login_id)->toBe('1100002')
        ->and($customer->login_pass)->toBe('1234')
        ->and($customer->name)->toBe('山田 花子')
        ->and($customer->name_kana)->toBe('ヤマダ ハナコ')
        ->and($customer->tel)->toBe('0312345678')
        ->and($customer->kojin_mobile)->toBe('09011112222')
        ->and($customer->kojin_birth)->toBe('1990/04/08')
        ->and((int) $customer->kojin_birth_date)->toBe(Illuminate\Support\Carbon::create(1990, 4, 8, 0, 0, 0, 'Asia/Tokyo')->timestamp)
        ->and($customer->bikou)->toBe('メモ 引用 ’x’')
        ->and($customer->touroku_kubun)->toBe('2')
        ->and($customer->disp_flg)->toBe('1')
        ->and($customer->shoukai_tb_customer_cd)->toBe($referrer->cd)
        ->and((int) $customer->error_send_cnt)->toBe(0)
        ->and($customer->touroku_date)->not->toBeEmpty();

    $sub = DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->first();
    expect($sub->text_data)->toBe('読書')
        ->and($sub->tb_search_cate_cd)->toBe(50)
        ->and($sub->form_title)->toBe('追加情報<>趣味')
        ->and($sub->form_type)->toBe('1')
        ->and($sub->disp_flg)->toBe('1');
});

it('no registra si los campos ocultos traen errores', function () {
    $this->post(route('admin.clientes.store'), clientesAltaPost(['zip' => 'abc']))
        ->assertOk()
        ->assertSee('class="Ng"', false);

    expect(Customer::query()->count())->toBe(0);
});

it('une 姓 y 名 con espacio y pone ゲスト si no hay nombre', function () {
    DB::table('contents_master')->insert(['cd' => 1, 'category' => 'tb_c1_customer', 'field' => 'name_input_mode_flg', 'text_data' => '1', 'disp_flg' => '1']);

    $this->get(route('admin.clientes.create'))->assertOk()->assertSee('name="name_sei"', false);

    $this->post(route('admin.clientes.store'), clientesAltaPost(['name_sei' => '山 田', 'name_mei' => '花子', 'name_kana_sei' => 'やまだ', 'name_kana_mei' => 'ハナコ']))->assertRedirect();
    $this->post(route('admin.clientes.store'), clientesAltaPost(['email1' => 'b@example.com', 'name_sei' => '', 'name_mei' => '', 'name_kana_sei' => '', 'name_kana_mei' => '']))->assertRedirect();

    expect(Customer::query()->where('email1', 'hanako@example.com')->value('name'))->toBe('山田 花子')
        ->and(Customer::query()->where('email1', 'hanako@example.com')->value('name_kana'))->toBe('ヤマダ ハナコ')
        ->and(Customer::query()->where('email1', 'b@example.com')->value('name'))->toBe('ゲスト');
});

it('edita el cliente y actualiza su campo personalizado sin duplicarlo', function () {
    clientesAltaCampoTexto('');
    $customer = Customer::factory()->create(['cd' => 900, 'id' => '1100001', 'login_id' => '1100001', 'name' => '鈴木 一郎', 'name_kana' => 'スズキ イチロウ', 'kojin_birth' => '1980/01/02', 'touroku_date' => '1000']);
    DB::table('tb_customer_sub')->insert(['cd' => 1, 'tb_customer_cd' => 900, 'tb_search_cate_cd' => 50, 'tb_search_form_cd' => 60, 'text_data' => '釣り', 'disp_flg' => '1']);

    // Botón 修正 de la ficha del legacy: POST kaiin_cd a customer_new.php
    $this->post('/system/category1/inc/customer_new.php', ['kaiin_cd' => 900])->assertRedirect(route('admin.clientes.edit', 900));

    $this->get(route('admin.clientes.edit', 900))
        ->assertOk()
        ->assertSee('■顧客登録情報 修正', false)
        ->assertSee('value="鈴木 一郎"', false)
        ->assertSee('value="釣り"', false)
        ->assertSee('value="1980"', false);

    $post = clientesAltaPost(['act' => 'up', 'customer_cd' => '900', 'kaiin_cd' => '900', 'name' => '鈴木 次郎', 'form60' => '登山', 'email1' => 'jiro@example.com']);
    $this->post(route('admin.clientes.confirm'), $post)
        ->assertOk()->assertSee('■顧客登録情報 修正 確認', false)->assertSee(route('admin.clientes.update', 900), false);

    $this->put(route('admin.clientes.update', 900), $post)->assertRedirect();

    $customer->refresh();
    expect($customer->name)->toBe('鈴木 次郎')
        ->and($customer->id)->toBe('1100001')
        ->and($customer->touroku_date)->toBe('1000')
        ->and($customer->update_date)->not->toBeEmpty()
        ->and(DB::table('tb_customer_sub')->where('tb_customer_cd', 900)->count())->toBe(1)
        ->and(DB::table('tb_customer_sub')->where('tb_customer_cd', 900)->value('text_data'))->toBe('登山');
});

it('ajax_search_kaiin devuelve la tabla del legacy para 紹介者', function () {
    Customer::factory()->create(['cd' => 31, 'id' => '1100031', 'name' => '佐藤 健', 'name_kana' => 'サトウ ケン']);
    Customer::factory()->create(['cd' => 32, 'id' => '1100032', 'name' => '高橋 誠', 'name_kana' => 'タカハシ マコト']);

    $this->post('/system/category1/inc/ajax_search_kaiin.php', ['w' => 'さとう'])
        ->assertOk()
        ->assertSee('1件中/1件表示')
        ->assertSee('佐藤 健')
        ->assertDontSee('高橋 誠')
        ->assertSee('value="確定"', false);

    $this->post('/system/category1/inc/ajax_search_kaiin.php', ['w' => 'zzz'])
        ->assertOk()->assertSee('データが見つかりませんでした');
});
