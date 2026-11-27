<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// C6-c: csv出力管理画面 (shukei/customerDataOutPut.php)

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:34', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** Columnas del CSV (tb_koumoku), un campo único con csv_flg, una categoría de varios registros y dos clientes con visitas */
function clientesVisitCsvSeed(): array
{
    DB::table('tb_koumoku')->insert([
        ['cd' => 1, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'id', 'tb_koumoku_name' => '会員番号', 'koumoku_select' => '1', 'rank' => 1],
        ['cd' => 2, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'koumoku_select' => '1', 'rank' => 2],
        ['cd' => 3, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'tb_shop_cd', 'tb_koumoku_name' => '登録店舗', 'koumoku_select' => '1', 'rank' => 3],
        ['cd' => 4, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'age', 'tb_koumoku_name' => '年齢', 'koumoku_select' => '1', 'rank' => 4],
        ['cd' => 5, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'disp_flg', 'tb_koumoku_name' => '登録区分', 'koumoku_select' => '1', 'rank' => 5],
    ]);
    DB::table('tb_search_cate')->insert([
        ['cd' => 40, 'name' => '基本追加', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'serach_flg' => '0', 'record_type' => null, 'rank' => 1],
        ['cd' => 50, 'name' => '施術記録', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'serach_flg' => '0', 'record_type' => '1', 'rank' => 2],
    ]);
    DB::table('tb_search_form')->insert([
        ['cd' => 41, 'tb_search_cate_cd' => 40, 'name' => '趣味', 'form_type' => 'チェックボックス', 'csv_flg' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1, 'flds_row' => 2, 'data' => '釣り<>読書'],
        ['cd' => 60, 'tb_search_cate_cd' => 50, 'name' => '施術内容', 'form_type' => 'テキスト', 'csv_flg' => null, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1, 'flds_row' => 0, 'data' => null],
        ['cd' => 61, 'tb_search_cate_cd' => 50, 'name' => '施術時間', 'form_type' => '数値テキスト', 'csv_flg' => null, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 2, 'flds_row' => 0, 'data' => null],
    ]);

    $yamada = Customer::factory()->create(['id' => '1100001', 'name' => '山田 花子', 'kojin_birth' => '1990/06/01']);
    $suzuki = Customer::factory()->create(['id' => '1100002', 'name' => '鈴木 一郎', 'kojin_birth' => '']);
    $baja = Customer::factory()->create(['id' => '1100003', 'name' => '退会 太郎', 'disp_flg' => '0']);

    $time = Carbon::parse('2026-05-10 15:20', 'Asia/Tokyo')->timestamp;
    DB::table('tb_raiten_rireki')->insert([
        ['cd' => 1, 'user_cd' => $yamada->cd, 'shop_cd' => 1, 'raiten_time' => (string) $time, 'raiten_hiduke' => '2026/05/10', 'disp_flg' => '1', 'tb_customer_sub_inner_group' => 'g1'],
        ['cd' => 2, 'user_cd' => $suzuki->cd, 'shop_cd' => 1, 'raiten_time' => (string) $time, 'raiten_hiduke' => '2026/05/10', 'disp_flg' => '1', 'tb_customer_sub_inner_group' => null],
        ['cd' => 3, 'user_cd' => $suzuki->cd, 'shop_cd' => 1, 'raiten_time' => (string) $time, 'raiten_hiduke' => '2026/05/10', 'disp_flg' => '9', 'tb_customer_sub_inner_group' => null],
        ['cd' => 4, 'user_cd' => $baja->cd, 'shop_cd' => 1, 'raiten_time' => (string) $time, 'raiten_hiduke' => '2026/05/10', 'disp_flg' => '1', 'tb_customer_sub_inner_group' => null],
    ]);
    DB::table('tb_customer_sub')->insert([
        ['cd' => 1, 'tb_customer_cd' => $yamada->cd, 'tb_search_cate_cd' => 40, 'tb_search_form_cd' => 41, 'text_data' => '釣り<>読書', 'float_data' => null, 'disp_flg' => '1', 'category_inner_group' => null],
        ['cd' => 2, 'tb_customer_cd' => $yamada->cd, 'tb_search_cate_cd' => 50, 'tb_search_form_cd' => 60, 'text_data' => 'カット', 'float_data' => null, 'disp_flg' => '1', 'category_inner_group' => 'g1'],
        ['cd' => 3, 'tb_customer_cd' => $yamada->cd, 'tb_search_cate_cd' => 50, 'tb_search_form_cd' => 61, 'text_data' => null, 'float_data' => 45, 'disp_flg' => '1', 'category_inner_group' => 'g1'],
    ]);

    return [$yamada, $suzuki];
}

/** Contenido de la descarga en UTF-8 */
function clientesVisitCsvBody($response): string
{
    return mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
}

it('carga csv出力管理画面 con los textos del legacy (también en su URL)', function () {
    $this->get(route('admin.clientes.reports.visit-csv'))->assertOk()
        ->assertSee('csv出力管理画面 ( 来店履歴抽出数 : 0件 )')
        ->assertSee('件づつCSVファイルに出力する。')
        ->assertSee('value="検索条件クリア"', false)
        ->assertDontSee('検索結果 : CSVファイル取得ボタン');
    $this->get('/system/category1/shukei/customerDataOutPut.php')->assertOk()->assertSee('csv出力管理画面');
});

it('buscar cuenta las visitas activas de socios y pinta un botón por bloque', function () {
    clientesVisitCsvSeed();

    $this->post('/system/category1/shukei/customerDataOutPut.php', ['mode' => 'search', 'csv_limit' => '100'])->assertOk()
        ->assertSee('来店履歴抽出数 : 2件')
        ->assertSee('検索結果 : CSVファイル取得ボタン')
        ->assertSee('value="1件～2件をcsvファイルで出力"', false);

    // Con filtro de nombre (se guarda en sesión)
    $this->post(route('admin.clientes.reports.visit-csv'), ['mode' => 'search', 'name' => '鈴木'])->assertSee('来店履歴抽出数 : 1件');
    expect(session('clientes.visit_csv.search'))->toMatchArray(['name' => '鈴木']);

    // 検索条件クリア
    $this->post(route('admin.clientes.reports.visit-csv'), ['mode' => 'search_clear'])->assertSee('来店履歴抽出数 : 0件');
    expect(session('clientes.visit_csv.search'))->toBeNull();
});

it('descarga el bloque en SJIS-win con las columnas y el formato del legacy', function () {
    [$yamada, $suzuki] = clientesVisitCsvSeed();
    $this->post(route('admin.clientes.reports.visit-csv'), ['mode' => 'search']);

    $response = $this->post('/system/category1/shukei/customerDataOutPut.php', ['mode' => 'csv', 'page' => '0', 'view' => '100']);
    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/x-csv')
        ->and(rawurldecode((string) $response->headers->get('Content-Disposition')))->toContain('顧客データ(1件～2件)[2026年05月20日12時34分出力].csv');

    $lines = explode("\r\n", clientesVisitCsvBody($response));
    expect($lines[0])->toBe('会員番号,名前,登録店舗,年齢,登録区分,趣味,来店日時,来店時間,施術内容,施術時間')
        // orden cstm.cd DESC: primero 鈴木 (sin fecha de nacimiento: edad vacía, sin 追加情報)
        ->and($lines[1])->toBe("\"\t1100002\",\"\t鈴木 一郎\",\"\t本店\",\"\t\",\"\t登録済み\",,\"\t2026/05/10\",\"\t15:20\",,")
        ->and($lines[2])->toBe("\"\t1100001\",\"\t山田 花子\",\"\t本店\",\"\t35\",\"\t登録済み\",\"\t釣り\n読書\",\"\t2026/05/10\",\"\t15:20\",\"\tカット\",\"\t45\"");
});

it('la descarga por la ruta de pantalla respeta el bloque (page/view)', function () {
    clientesVisitCsvSeed();
    $this->post(route('admin.clientes.reports.visit-csv'), ['mode' => 'search']);

    $response = $this->post(route('admin.clientes.reports.visit-csv.download'), ['page' => '1', 'view' => '100']);
    $response->assertOk();
    expect(clientesVisitCsvBody($response))->toBe("会員番号,名前,登録店舗,年齢,登録区分,趣味,来店日時,来店時間,施術内容,施術時間\r\n");
});
