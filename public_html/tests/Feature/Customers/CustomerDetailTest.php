<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Customers\CustomFieldRenderer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Ficha 顧客登録情報 (legacy: category1/inc/customer_detail.php + detail_tab*.php, detail_tabcustom.php),
// 削除・復元 (customer_delete_register.php, customer_recover_confirm.php) y UID関連付け解除 (customer_card_delete.php)

beforeEach(function () {
    reservasLogin();
});

/** Activa los módulos que muestran pestañas en la barra de la ficha */
function clientesFichaModulos(): void
{
    DB::table('tb_base_system_child')->insert([
        ['cd' => 101, 'tb_base_system_cd' => 1, 'system_name' => '来店履歴管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 102, 'tb_base_system_cd' => 3, 'system_name' => 'マイクーポン管理', 'system_flg' => '1', 'rank' => 2],
        ['cd' => 103, 'tb_base_system_cd' => 3, 'system_name' => 'スタンプ管理', 'system_flg' => '1', 'rank' => 3],
        ['cd' => 104, 'tb_base_system_cd' => 1, 'system_name' => 'スタッフ管理', 'system_flg' => '1', 'rank' => 4],
    ]);
    DB::table('tb_base_system_grandchild')->insert([
        ['cd' => 201, 'tb_base_system_cd' => 1, 'tb_base_system_child_cd' => 101, 'system_name' => 'マイアルバム管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 202, 'tb_base_system_cd' => 3, 'tb_base_system_child_cd' => 102, 'system_name' => 'ポイント管理', 'system_flg' => '1', 'rank' => 2],
        ['cd' => 203, 'tb_base_system_cd' => 1, 'tb_base_system_child_cd' => 101, 'system_name' => '購買履歴', 'system_flg' => '1', 'rank' => 3],
    ]);
    DB::table('tb_base_system')->insert(['cd' => 9, 'system_name' => '予約管理', 'system_flg' => '1', 'narabi' => 9]);
}

/** Categoría personalizada de varios registros con un campo de texto y uno de casillas */
function clientesFichaCategoria(): int
{
    DB::table('tb_search_cate')->insert(['cd' => 50, 'name' => '施術記録', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'record_type' => '1', 'disp_column' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert([
        ['cd' => 60, 'tb_search_cate_cd' => 50, 'name' => 'メニュー名', 'form_type' => 'テキスト', 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1, 'flds_row' => 0, 'data' => null],
        ['cd' => 61, 'tb_search_cate_cd' => 50, 'name' => '部位', 'form_type' => 'チェックボックス', 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 2, 'flds_row' => 3, 'data' => '肩<>腰<>首'],
    ]);

    return 50;
}

it('carga la ficha (pestaña 顧客情報) con los textos del legacy y los botones según permisos', function () {
    clientesFichaModulos();
    $customer = Customer::factory()->create(['name' => '山田 花子', 'id' => '1100123', 'tb_staff_cd' => 10]);

    $this->get(route('admin.clientes.show', $customer->cd))
        ->assertOk()
        ->assertSee('顧客登録情報 - ', false)
        ->assertSee('customerDetail', false)
        ->assertSee('山田 花子')
        ->assertSee('1100123')
        ->assertSee('本店')
        ->assertSee('指名担当者')
        ->assertSee('山田')
        ->assertSee('端末登録無')
        ->assertSee('来店履歴')
        ->assertSee('マイクーポン')
        ->assertSee('マイアルバム')
        ->assertSee('ポイント')
        ->assertSee('スタンプ')
        ->assertSee('購買履歴')
        ->assertSee('予約状況')
        ->assertSee('紹介実績')
        ->assertSee('顧客情報を修正する')
        ->assertSee('顧客情報を削除する')
        ->assertDontSee('顧客情報を復元する')
        ->assertDontSee('有料会員状況');
});

it('abre la ficha desde las URL del legacy: con menú (customer/) y como ventana (inc/)', function () {
    $customer = Customer::factory()->create(['name' => '佐藤 一郎']);

    $this->get('/system/category1/customer/customer_detail.php?kaiin_cd=' . $customer->cd)
        ->assertOk()->assertSee('佐藤 一郎')->assertDontSee('name="close_window"', false);

    $this->post('/system/category1/inc/customer_detail.php', ['kaiin_cd' => $customer->cd, 'tab' => ''])
        ->assertOk()->assertSee('佐藤 一郎')->assertSee('閉じる')->assertSee('name="wintype" value="no_headers"', false);

    // Sin kaiin_cd numérico el legacy hacía exit: página en blanco
    expect($this->get('/system/category1/inc/customer_detail.php?kaiin_cd=abc')->assertOk()->getContent())->toBe('');
});

it('carga cada pestaña sin errores', function (string $tab) {
    clientesFichaModulos();
    clientesFichaCategoria();
    DB::table('admin_master')->where('cd', 1)->update(['related_features' => '1<>1<>50']);
    $customer = Customer::factory()->create(['name' => '鈴木 次郎']);

    $params = ['tab' => $tab] + ($tab === 'custom' ? ['search_cate_cd' => 50] : []);
    $this->get(route('admin.clientes.show', [$customer->cd] + $params))->assertOk()->assertSee('紹介実績')->assertSee('顧客情報を削除する');
})->with(['', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', 'custom', '99']);

it('lista el 来店履歴 con su tienda y el ciclo de visitas', function () {
    $customer = Customer::factory()->create(['shuki_heikin' => 12, 'last_raiten_date' => '2026/11/01']);
    DB::table('tb_raiten_rireki')->insert([
        ['cd' => 1, 'user_cd' => $customer->cd, 'shop_cd' => 1, 'raiten_time' => (string) Carbon::create(2026, 11, 1, 10, 30, 0, 'Asia/Tokyo')->timestamp, 'manual_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 2, 'user_cd' => $customer->cd, 'shop_cd' => 1, 'raiten_time' => (string) Carbon::create(2026, 10, 1, 9, 0, 0, 'Asia/Tokyo')->timestamp, 'manual_flg' => '0', 'disp_flg' => '9'],
    ]);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '4']))
        ->assertOk()
        ->assertSee('■来店履歴一覧')
        ->assertSee('2026/11/01 10:30', false)
        ->assertSee("raiten_delete('1')", false)
        ->assertDontSee('2026/10/01')
        ->assertSee('来店履歴処理実行');
});

it('muestra la ポイント actual y su historial', function () {
    $customer = Customer::factory()->create();
    DB::table('tb_customer_point')->insert(['cd' => 1, 'tb_customer_cd' => $customer->cd, 'service_point' => 1234]);
    DB::table('tb_point_log')->insert(['cd' => 1, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => 1, 'tb_staff_cd' => 1, 'tb_terminal_cd' => -1,
        'touroku_date' => time(), 'kaikei' => 5000, 'jidou_point' => 50, 'sounyu_point' => 0, 'shouhi_point' => 0, 'disp_flg' => '1']);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '8']))
        ->assertOk()
        ->assertSee('1,234')
        ->assertSee('5,000');
});

it('lista y borra una マイクーポン del cliente', function () {
    $customer = Customer::factory()->create();
    DB::table('tb_mycoupon_master')->insert(['cd' => 7, 'title' => '10%OFF', 'comment' => '次回使える', 'disp_flg' => '1']);
    DB::table('tb_mycoupon')->insert(['cd' => 70, 'tb_mycoupon_master_cd' => 7, 'tb_shop_cd' => -1, 'tb_customer_cd' => $customer->cd,
        'time_limit' => (string) (time() + 86400 * 10)]);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '6']))
        ->assertOk()->assertSee('■未使用クーポン一覧')->assertSee('10%OFF')->assertSee('全店舗');

    $this->delete(route('admin.clientes.coupons.destroy', $customer->cd), ['coupon_cd' => '70', 'tab' => '6', 'kaiin_cd' => $customer->cd])
        ->assertRedirect(route('admin.clientes.show', [$customer->cd, 'tab' => '6', 'kaiin_cd' => $customer->cd]));

    expect(DB::table('tb_mycoupon')->where('cd', 70)->exists())->toBeFalse();
});

it('muestra la pestaña de categoría con el último registro y sus campos', function () {
    $cate = clientesFichaCategoria();
    $customer = Customer::factory()->create();
    foreach ([1 => ['カット', '肩<>'], 2 => ['パーマ', '腰<>首<>']] as $group => [$text, $check]) {
        DB::table('tb_customer_sub')->insert([
            ['cd' => $group * 10, 'tb_customer_cd' => $customer->cd, 'tb_search_cate_cd' => $cate, 'tb_search_form_cd' => 60, 'text_data' => $text, 'category_inner_group' => (string) $group, 'disp_flg' => '1', 'touroku_date' => (string) time(), 'update_date' => (string) time()],
            ['cd' => $group * 10 + 1, 'tb_customer_cd' => $customer->cd, 'tb_search_cate_cd' => $cate, 'tb_search_form_cd' => 61, 'text_data' => $check, 'category_inner_group' => (string) $group, 'disp_flg' => '1', 'touroku_date' => (string) time(), 'update_date' => (string) time()],
        ]);
    }

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => 'custom', 'search_cate_cd' => $cate]))
        ->assertOk()
        ->assertSee('施術記録番号：　2', false)
        ->assertSee('腰<br />首<br />', false)
        ->assertSee('■施術記録一覧')
        ->assertSee('カット');

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => 'custom', 'search_cate_cd' => $cate, 'fukusu_group_cd' => 1]))
        ->assertOk()->assertSee('施術記録番号：　1', false)->assertSee('肩<br />', false);
});

it('pinta los campos personalizados como createform_confirm()', function () {
    $r = new CustomFieldRenderer();

    expect($r->render(['form_type' => 'テキスト'], ['text_data' => '<b>x</b>']))->toBe('&lt;b&gt;x&lt;/b&gt;')
        ->and($r->render(['form_type' => 'テキストエリア'], ['text_data' => "a\nb"]))->toBe("a<br />\nb")
        ->and($r->render(['form_type' => '数値テキスト'], ['float_data' => '3.5']))->toBe('3.5')
        ->and($r->render(['form_type' => '年月日'], ['text_data' => '2020/1/']))->toBe('2020年1月')
        ->and($r->render(['form_type' => '月日'], ['text_data' => '3/14']))->toBe('3月14日')
        ->and($r->render(['form_type' => 'チェックボックス', 'flds_row' => 2, 'data' => 'A<>B'], ['text_data' => 'B<>']))->toBe('B<br />')
        ->and($r->render(['form_type' => 'テーブル', 'flds_row' => 2, 'flds_col' => 1, 'titles' => 'select[色]:赤,青<>数', 'unit' => '<>個', 'style_class' => 't'], ['text_data' => '赤<>3']))
        ->toBe("<table class=\"t\">\n<tr><th>色</th><th>数</th>\n</tr><td>赤</td><td>3個</td>\n</tr></table>")
        ->and($r->render(['form_type' => '画像'], ['text_data' => 'x']))->toBe('')
        ->and($r->render(['form_type' => 'テキスト'], null))->toBe('');
});

it('borra el cliente (disp_flg = 9) y libera su tarjeta', function () {
    $customer = Customer::factory()->create(['felica_no' => '0123ABCD']);
    DB::table('tb_user_card')->insert(['felica_no' => '0123ABCD', 'staff_cd' => (string) $customer->cd, 'card_flg' => '1']);

    $this->delete(route('admin.clientes.destroy', $customer->cd), ['delete_flg' => 'del', 'customer_cd' => $customer->cd])
        ->assertOk()
        ->assertSee('■顧客情報 削除')
        ->assertSee('削除中です。')
        ->assertSee(route('admin.clientes.show', $customer->cd), false);

    $row = DB::table('tb_customer')->where('cd', $customer->cd)->first();
    expect($row->disp_flg)->toBe('9')
        ->and($row->felica_no)->toBe('')
        ->and(DB::table('tb_user_card')->where('felica_no', '0123ABCD')->exists())->toBeFalse();

    // La ficha del borrado se sigue viendo, con el botón de 復元 y sin 修正
    $this->get(route('admin.clientes.show', $customer->cd))
        ->assertOk()->assertSee('顧客情報を復元する')->assertDontSee('顧客情報を修正する');
});

it('restaura el cliente salvo que otro socio ya use su correo', function () {
    $customer = Customer::factory()->create(['email1' => 'a@example.com', 'disp_flg' => '9']);

    $this->post(route('admin.clientes.restore', $customer->cd), ['delete_flg' => 'recover'])
        ->assertOk()->assertSee('■顧客情報 復元')->assertSee('復元中です。');
    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('disp_flg'))->toBe('1');

    // Otro socio activo con el mismo correo: no se restaura y la ficha avisa
    DB::table('tb_customer')->where('cd', $customer->cd)->update(['disp_flg' => '9']);
    Customer::factory()->create(['email1' => 'a@example.com']);

    $this->post(route('admin.clientes.restore', $customer->cd), ['wintype' => 'no_headers'])
        ->assertRedirect(route('admin.clientes.show', [$customer->cd, 'wintype' => 'no_headers']));
    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('disp_flg'))->toBe('9');

    $this->get(route('admin.clientes.show', [$customer->cd, 'wintype' => 'no_headers']))
        ->assertSee('すでに再登録されたメールアドレスが存在するため復元できません。');
});

it('exige el permiso 削除 (kengen 1001/4) para borrar y no muestra el botón', function () {
    $customer = Customer::factory()->create();
    // Staff no master con la matriz de permisos sin 削除
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => '一般', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert(['cd' => 1001, 'name' => '顧客管理', 'hyouji_flg' => '1']);
    DB::table('tb_kengen')->insert(['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1001, 'etsuran_flg' => '1', 'shinki_flg' => '1', 'shusei_flg' => '1', 'sakujo_flg' => '0']);
    DB::table('tb_staff')->where('cd', 10)->update(['tb_staff_group_cd' => 5]);
    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    $this->get(route('admin.clientes.show', $customer->cd))->assertOk()->assertDontSee('顧客情報を削除する');
    $this->delete(route('admin.clientes.destroy', $customer->cd))->assertForbidden();
    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('disp_flg'))->toBe('1');
});

it('quita la relación de la tarjeta (UID関連付け解除)', function () {
    $customer = Customer::factory()->create(['felica_no' => 'FFEE0011']);
    DB::table('tb_user_card')->insert(['felica_no' => 'FFEE0011', 'staff_cd' => (string) $customer->cd, 'card_flg' => '1']);

    $this->get(route('admin.clientes.show', $customer->cd))->assertSee('端末登録有')->assertSee('FFEE0011');

    $this->delete(route('admin.clientes.felica.destroy', $customer->cd), ['kaiin_cd' => $customer->cd, 'separate_flg' => 'separate'])
        ->assertOk()->assertSee('■顧客登録情報 UID関連付け解除')->assertSee('解除中です。');

    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('felica_no'))->toBe('')
        ->and(DB::table('tb_user_card')->where('felica_no', 'FFEE0011')->exists())->toBeFalse();
});

it('responde vacío a las llamadas al POS de la pestaña 購買履歴', function () {
    $this->post('/system/category1/inc/ajax_connectPosData.php?shop_cd=1&tb_customer_cd=1')->assertOk()->assertExactJson([]);
});

it('muestra la pestaña カルテ (関連情報) con la visita, su punto enlazado y la línea de tiempo', function () {
    clientesFichaModulos();
    $cate = clientesFichaCategoria();
    DB::table('admin_master')->where('cd', 1)->update(['related_features' => '1<>1<>' . $cate]);
    $customer = Customer::factory()->create();
    $at = Carbon::create(2026, 11, 2, 15, 0, 0, 'Asia/Tokyo')->timestamp;
    DB::table('tb_point_log')->insert([
        ['cd' => 5, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => 1, 'tb_staff_cd' => 1, 'tb_terminal_cd' => -1, 'touroku_date' => $at, 'kaikei' => 3000, 'jidou_point' => 30, 'sounyu_point' => 0, 'shouhi_point' => 0, 'disp_flg' => '1'],
        ['cd' => 6, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => 1, 'tb_staff_cd' => 1, 'tb_terminal_cd' => -1, 'touroku_date' => $at - 86400, 'kaikei' => 0, 'jidou_point' => 0, 'sounyu_point' => 15, 'shouhi_point' => 0, 'disp_flg' => '1'],
    ]);
    DB::table('tb_raiten_rireki')->insert(['cd' => 9, 'user_cd' => $customer->cd, 'shop_cd' => 1, 'raiten_time' => (string) $at, 'disp_flg' => '1',
        'tb_point_log_cd' => 5, 'tb_search_cate_cd' => $cate, 'tb_customer_sub_inner_group' => '1']);
    DB::table('tb_customer_sub')->insert(['cd' => 90, 'tb_customer_cd' => $customer->cd, 'tb_search_cate_cd' => $cate, 'tb_search_form_cd' => 60,
        'text_data' => 'ヘッドスパ', 'category_inner_group' => '1', 'disp_flg' => '1', 'touroku_date' => (string) $at, 'update_date' => (string) $at]);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '9']))
        ->assertOk()
        ->assertSee('ポイントのみを表示')
        ->assertSee('施術記録のみを表示')
        ->assertSee('■施術記録情報')
        ->assertSee('ヘッドスパ')
        ->assertSee('3,000')
        ->assertSee('2026/11/02 15:00', false)
        ->assertSee('ポイント加算');
});

it('lista las reservas desde hoy en 予約状況', function () {
    clientesFichaModulos();
    $customer = Customer::factory()->create();
    $day = Carbon::now('Asia/Tokyo')->addDays(3)->format('Y/m/d');
    DB::table('tb_yoyaku_uketsuke_day')->insert(['cd' => 1, 'tb_staff_cd' => 10, 'moushikomi_date' => $day, 'disp_flg' => '1']);
    DB::table('tb_yoyaku')->insert(['cd' => 1, 'tb_yoyaku_uketsuke_day_cd' => 1, 'tb_customer_cd' => $customer->cd, 'start_time' => 36000, 'end_time' => 39600,
        'base_tb_yoyaku_koumoku_master_cd' => '0', 'comment' => '駐車場希望', 'disp_flg' => '1']);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '12']))
        ->assertOk()
        ->assertSee('10:00&nbsp;～&nbsp;11:00', false)
        ->assertSee('山田')
        ->assertSee('本店')
        ->assertSee('駐車場希望');
});
