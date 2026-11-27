<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// C6-b: 顧客情報集計 (customer_sumup_list.php / customer_sumup_list2.php)

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** Socio de registro completo con sexo, nacimiento, tienda, alta (fecha 'Y-m-d'), prefectura y ocupación */
function clientesSumupCustomer(array $attrs): Customer
{
    $touroku = Carbon::parse($attrs['alta'] ?? '2026-05-01', 'Asia/Tokyo')->getTimestamp();
    unset($attrs['alta']);

    $customer = Customer::factory()->create($attrs + [
        'touroku_kubun' => '2', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'kojin_sex' => '男性', 'kojin_birth' => '1990/01/01',
    ]);
    // touroku_date lo pone LegacyTimestamps al crear: se fija después
    DB::table('tb_customer')->where('cd', $customer->cd)->update(['touroku_date' => (string) $touroku]);

    return $customer;
}

it('carga 顧客情報集計 con los textos del legacy (y sus dos URL)', function () {
    $this->get(route('admin.clientes.statistics'))->assertOk()
        ->assertSee('■顧客情報集計')->assertSee('顧客数総計')->assertSee('過去5年の年間登録者数')
        ->assertSee('■顧客検索条件')->assertSee('value="この条件で検索する"', false)
        ->assertSee('2022/01/01～2022/12/31')->assertSee('2026/01/01～2026/12/31')
        ->assertDontSee('検索結果');

    $this->get('/system/category1/customer/customer_sumup_list.php')->assertOk()->assertSee('■顧客情報集計');
    $this->get('/system/category1/customer/customer_sumup_list2.php')->assertOk()->assertSee('■顧客情報集計');
});

it('cuenta por sexo y década, excluye bajas y no socios', function () {
    clientesSumupCustomer(['kojin_sex' => '男性', 'kojin_birth' => '1996/05/21']); // 29 años → 20代
    clientesSumupCustomer(['kojin_sex' => '男性', 'kojin_birth' => '1996/05/20']); // cumple 30 hoy → 30代
    clientesSumupCustomer(['kojin_sex' => '女性', 'kojin_birth' => '1980/01/01']); // 40代
    clientesSumupCustomer(['kojin_sex' => '', 'kojin_birth' => '//']);             // 不明
    clientesSumupCustomer(['disp_flg' => '9']);
    clientesSumupCustomer(['touroku_kubun' => '1']);

    $data = app(\App\Services\Customers\Reports\CustomerStatisticsService::class)->report([], null);

    expect($data['total']['all'])->toBe(4)
        ->and($data['total']['male'])->toBe(2)
        ->and($data['total']['female'])->toBe(1)
        ->and($data['total']['unknown'])->toBe(1)
        ->and($data['total']['male_pct'])->toBe(50)
        ->and($data['total']['male_ages']['ages'][20]['count'])->toBe(1)
        ->and($data['total']['male_ages']['ages'][30]['count'])->toBe(1)
        ->and($data['total']['female_ages']['ages'][40]['count'])->toBe(1)
        ->and($data['total']['female_ages']['other'])->toBe(0);

    $this->get(route('admin.clientes.statistics'))->assertOk()
        ->assertSee('■総数：<span>4</span> 人', false)
        ->assertSee('男性<small>[2人]</small>', false);
});

it('busca por 県別 y periodo (一週間) con act=up', function () {
    DB::table('tb_pref')->insert([
        ['cd' => 1, 'area' => '東京都', 'rank' => 1, 'flg' => '1'],
        ['cd' => 2, 'area' => '大阪府', 'rank' => 2, 'flg' => '1'],
        ['cd' => 3, 'area' => '京都府', 'rank' => 3, 'flg' => '0'],
    ]);
    clientesSumupCustomer(['pref' => '東京都', 'alta' => '2026-05-03']);
    clientesSumupCustomer(['pref' => '東京都', 'alta' => '2026-05-12', 'kojin_sex' => '女性']); // fuera del periodo
    clientesSumupCustomer(['pref' => '大阪府', 'alta' => '2026-05-05', 'kojin_sex' => '女性']);

    $response = $this->post(route('admin.clientes.statistics'), [
        'act' => 'up', 'se_flg' => '1', 'sumup_kind' => '県別', 'touroku_date_1' => '2026/05/01', 'touroku_date_2' => '一週間',
    ])->assertOk()->assertSee('検索結果')->assertSee('■検索ヒット：<span>2</span> 人', false)
        ->assertSee('【東京都】')->assertSee('【大阪府】')->assertDontSee('【京都府】')
        ->assertSee('<option value="県別">県別</option>', false);

    $groups = $response->viewData('groups');
    expect($groups[0]['stats']['all'])->toBe(1)->and($groups[1]['stats']['female'])->toBe(1);
});

it('busca por 業種別: 「その他」 suma la ocupación vacía', function () {
    DB::table('tb_job')->insert([
        ['cd' => 1, 'name' => '会社員', 'rank' => 1, 'disp_flg' => '1'],
        ['cd' => 2, 'name' => 'その他', 'rank' => 2, 'disp_flg' => '1'],
    ]);
    clientesSumupCustomer(['job' => '会社員']);
    clientesSumupCustomer(['job' => '']);
    clientesSumupCustomer(['job' => 'その他']);

    $response = $this->post('/system/category1/customer/customer_sumup_list.php', ['act' => 'up', 'sumup_kind' => '業種別'])
        ->assertOk()->assertSee('【会社員】')->assertSee('【その他】');

    $groups = $response->viewData('groups');
    expect($groups[0]['stats']['all'])->toBe(1)->and($groups[1]['stats']['all'])->toBe(2);
});

it('el personal no master solo ve los clientes de su tienda', function () {
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    clientesSumupCustomer(['tb_shop_cd' => 1]);
    clientesSumupCustomer(['tb_shop_cd' => 2]);
    // Permiso 閲覧 de 顧客管理 (ruta con can:clientes.view): sin filas de tb_kengen, todo contenido existente está permitido
    DB::table('tb_kengen_contents')->insert(['cd' => 1001, 'name' => '顧客管理', 'hyouji_flg' => '1']);

    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    $response = $this->get(route('admin.clientes.statistics'))->assertOk();
    expect($response->viewData('total')['all'])->toBe(1);
});
