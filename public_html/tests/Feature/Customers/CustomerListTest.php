<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Customers\CustomerListService;
use Illuminate\Support\Facades\DB;

// 顧客一覧・検索 (legacy: category1/customer/customer_list.php + inc/search_set2.php) y sus CSV
// (csv_download.php, csv_download_ex02.php)

beforeEach(function () {
    reservasLogin();
});

/** Busca como el formulario del legacy (act=up, se_flg=1) y devuelve la respuesta */
function clientesBuscar(array $filters = [], array $extra = [])
{
    return test()->post(route('admin.clientes.search'), $filters + $extra + ['act' => 'up', 'se_flg' => '1', 'kensu' => '10']);
}

/** search_uniqid de la búsqueda guardada más reciente */
function clientesUltimoUniqid(): string
{
    $keys = array_keys((array) session('customer_search', []));

    return (string) end($keys);
}

/** CSV descargado pasado a UTF-8 */
function clientesCsv($response): string
{
    return mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
}

it('carga el buscador vacío sin resultados', function () {
    $this->get(route('admin.clientes.index'))
        ->assertOk()
        ->assertSee('顧客一覧・検索', false)
        ->assertSee('■顧客検索条件')
        ->assertSee('この条件で検索する')
        ->assertSee('検索条件クリア')
        ->assertSee('■顧客一覧')
        ->assertDontSee('件見つかりました');
});

it('busca por nombre, guarda la búsqueda en sesión y muestra 件数 e 内訳', function () {
    $yamada = Customer::factory()->create(['name' => '山田 太郎', 'mailmaga_email_type' => 'DoCoMo', 'kanri_id' => 'K-001']);
    Customer::factory()->create(['name' => '佐藤 花子', 'mailmaga_email_type' => 'パソコン']);
    Customer::factory()->create(['name' => '山田 次郎', 'disp_flg' => '9']); // borrado: no sale

    $response = clientesBuscar(['name' => '山田'])
        ->assertOk()
        ->assertSee('件見つかりました。')
        ->assertSee('1件～1件目/1件中')
        ->assertSee('DoCoMo：1')
        ->assertSee('山田 太郎')
        ->assertSee('K-001')
        ->assertDontSee('佐藤 花子')
        ->assertDontSee('山田 次郎')
        ->assertSee('この条件でCSV出力する')
        ->assertSee('詳細');

    $uniqid = clientesUltimoUniqid();
    expect(strlen($uniqid))->toBe(16)
        ->and(session("customer_search.$uniqid.params"))->toMatchArray(['name' => '山田'])
        ->and(session("customer_search.$uniqid.display"))->toContain('名前：山田')
        // 「この条件で配信する」 (S3) lee la misma búsqueda
        ->and(session("promocion.audience.$uniqid"))->toBe(session("customer_search.$uniqid"));
    $response->assertSee('value="' . $uniqid . '"', false);
    expect($yamada->cd)->toBeInt();
});

it('pagina con la búsqueda guardada y 表示件数', function () {
    Customer::factory()->count(12)->create();

    clientesBuscar()->assertOk()->assertSee('1件～10件目/12件中');
    $uniqid = clientesUltimoUniqid();

    $this->get(route('admin.clientes.index', ['se_flg' => 1, 'search_uniqid' => $uniqid, 'page' => 2, 'kensu' => 10]))
        ->assertOk()
        ->assertSee('11件～12件目/12件中');

    // Una búsqueda nueva reemplaza la anterior (search_uniqid nuevo, el viejo se borra)
    clientesBuscar(['name' => 'ないなまえ'], ['search_uniqid' => $uniqid])->assertOk()->assertSee('該当する顧客がみつかりません。');
    expect(session("customer_search.$uniqid"))->toBeNull();
});

it('cuenta los 来店回数 cuando 来店履歴管理 está activo', function () {
    DB::table('tb_base_system_child')->insert(['cd' => 10, 'tb_base_system_cd' => 1, 'system_name' => '来店履歴管理', 'system_flg' => '1']);
    $c = Customer::factory()->create(['name' => '来店 一郎', 'felica_no' => 'F123']);
    DB::table('tb_raiten_rireki')->insert([
        ['cd' => 1, 'user_cd' => $c->cd, 'shop_cd' => 1, 'disp_flg' => '1'],
        ['cd' => 2, 'user_cd' => $c->cd, 'shop_cd' => 1, 'disp_flg' => null],
        ['cd' => 3, 'user_cd' => $c->cd, 'shop_cd' => 1, 'disp_flg' => '9'],
    ]);

    clientesBuscar(['name' => '来店'])
        ->assertOk()
        ->assertSee('端末登録有無')
        ->assertSee('端末登録有')
        ->assertSee('2 回');
});

it('el personal que no es master solo ve los clientes de su tienda', function () {
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    Customer::factory()->create(['name' => '本店 客', 'tb_shop_cd' => 1]);
    Customer::factory()->create(['name' => '支店 客', 'tb_shop_cd' => 2]);

    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    // Aunque pida la tienda 2, el servidor fuerza la suya (tb_shop_cd = 1)
    clientesBuscar(['tb_shop_cd' => ['2']])
        ->assertOk()
        ->assertSee('本店 客')
        ->assertDontSee('支店 客');
});

it('responde en la URL del legacy, también con act=up por GET (ctiWindow.php)', function () {
    Customer::factory()->create(['name' => '電話 客', 'tel' => '0312345678']);

    $this->get('/system/category1/customer/customer_list.php')
        ->assertOk()
        ->assertSee('■顧客検索条件')
        ->assertDontSee('件見つかりました');

    $this->get('/system/category1/customer/customer_list.php?tel=0312345678&kojin_mobile=0312345678&search_method1_type=2&act=up&kensu=10&se_flg=1')
        ->assertOk()
        ->assertSee('電話 客');

    $this->post('/system/category1/customer/customer_list.php', ['name' => '電話', 'act' => 'up', 'se_flg' => '1'])
        ->assertOk()
        ->assertSee('電話 客');
});

it('exporta el CSV estándar con tb_koumoku y campos personalizados csv_flg', function () {
    DB::table('tb_koumoku')->insert([
        ['cd' => 1, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'id', 'tb_koumoku_name' => '会員番号', 'koumoku_select' => '1'],
        ['cd' => 2, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'koumoku_select' => '1'],
        ['cd' => 3, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'tb_shop_cd', 'tb_koumoku_name' => '登録店舗', 'koumoku_select' => '1'],
        ['cd' => 4, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'touroku_date', 'tb_koumoku_name' => '会員登録日', 'koumoku_select' => '1'],
        ['cd' => 5, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'tel', 'tb_koumoku_name' => '電話番号', 'koumoku_select' => '0'],
    ]);
    DB::table('tb_search_cate')->insert(['cd' => 7, 'name' => '追加', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'serach_flg' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert([
        ['cd' => 70, 'tb_search_cate_cd' => 7, 'name' => '趣味', 'form_type' => 'テキスト', 'disp_flg' => '1', 'csv_flg' => '1', 'rank' => 1],
        ['cd' => 71, 'tb_search_cate_cd' => 7, 'name' => '非出力', 'form_type' => 'テキスト', 'disp_flg' => '1', 'csv_flg' => '0', 'rank' => 2],
    ]);
    $touroku = \Illuminate\Support\Carbon::create(2024, 5, 6, 10, 0, 0, 'Asia/Tokyo')->timestamp;
    $a = Customer::factory()->create(['id' => '1100001', 'name' => '山田 "太郎"', 'name_kana' => 'ア', 'touroku_date' => (string) $touroku]);
    Customer::factory()->create(['id' => '1100002', 'name' => '佐藤 花子', 'name_kana' => 'イ']);
    DB::table('tb_customer_sub')->insert(['cd' => 1, 'tb_customer_cd' => $a->cd, 'tb_search_form_cd' => 70, 'text_data' => '釣り']);

    clientesBuscar(['name' => '山田'])->assertOk();
    $uniqid = clientesUltimoUniqid();

    $response = $this->get(route('admin.clientes.export', ['format' => 'standard', 'search_uniqid' => $uniqid]));
    $response->assertOk()->assertDownload('user.csv');
    $lines = explode("\r\n", clientesCsv($response));

    expect($lines[0])->toBe('"会員番号","名前","登録店舗","会員登録日","趣味"')
        ->and($lines[1])->toBe("\"\t1100001\",\"\t山田 \"\"太郎\"\"\",\"\t本店\",\"\t2024/05/06\",\"\t釣り\"")
        ->and($lines[2])->toBe('')
        ->and(count($lines))->toBe(3);

    // Sin búsqueda guardada: los filtros de la URL
    $lines = explode("\r\n", clientesCsv($this->get(route('admin.clientes.export', ['format' => 'standard', 'name' => '佐藤']))));
    expect($lines[1])->toStartWith("\"\t1100002\",\"\t佐藤 花子\"")->and(count($lines))->toBe(3);
});

it('exporta el DM用CSV (ex02) sin cabecera y solo con dirección', function () {
    Customer::factory()->create(['name' => '住所 有', 'name_kana' => 'ア', 'zip' => '1000001', 'pref' => '東京都', 'add' => '千代田区', 'add_number' => '1-1', 'add_build' => 'ビル']);
    Customer::factory()->create(['name' => '住所 無', 'name_kana' => 'イ', 'zip' => '', 'add' => null]);

    clientesBuscar()->assertOk();
    $response = $this->get('/system/category1/customer/csv_download_ex02.php?search_uniqid=' . clientesUltimoUniqid());
    $response->assertOk()->assertDownload('CRM_DM_List.csv');

    expect(clientesCsv($response))->toBe("住所 有,1000001,東京都千代田区1-1,ビル,,,,住所 有,様\r\n");
});

it('niega el CSV sin el permiso 1002/閲覧', function () {
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => '一般', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert([
        ['cd' => 1001, 'name' => '顧客管理', 'hyouji_flg' => '1'],
        ['cd' => 1002, 'name' => 'CSV', 'hyouji_flg' => '1'],
    ]);
    DB::table('tb_kengen')->insert([
        ['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1001, 'etsuran_flg' => '1', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0'],
        ['cd' => 2, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1002, 'etsuran_flg' => '0', 'shinki_flg' => '0', 'shusei_flg' => '0', 'sakujo_flg' => '0'],
    ]);
    DB::table('tb_staff')->where('cd', 10)->update(['tb_staff_group_cd' => 5]);
    Customer::factory()->create(['name' => '権限 客']);

    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    clientesBuscar()->assertOk()->assertSee('権限 客')->assertDontSee('この条件でCSV出力する');
    $this->get(route('admin.clientes.export', ['format' => 'standard']))->assertForbidden();
});

it('la búsqueda guardada sirve para reconstruir la consulta', function () {
    Customer::factory()->create(['name' => '再現 客']);
    clientesBuscar(['name' => '再現'])->assertOk();

    $list = app(CustomerListService::class);
    $query = $list->query(auth('admin')->user(), $list->description(clientesUltimoUniqid()));
    expect($query->pluck('name')->all())->toBe(['再現 客']);
});
