<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Services\Customers\CustomerSearch;
use Illuminate\Support\Facades\DB;

/** Epoch Asia/Tokyo de 'Y-m-d H:i:s' */
function tokyoEpoch(string $date): int
{
    return (new DateTimeImmutable($date, new DateTimeZone('Asia/Tokyo')))->getTimestamp();
}

/** Inserta un socio activo con valores por defecto */
function insertCustomer(int $cd, array $data = []): void
{
    DB::table('tb_customer')->insert(array_merge([
        'cd' => $cd,
        'id' => (string) (1000 + $cd),
        'tb_shop_cd' => 1,
        'name' => '顧客'.$cd,
        'name_kana' => 'コキャク'.$cd,
        'touroku_kubun' => '2',
        'disp_flg' => '1',
        'touroku_date' => (string) tokyoEpoch('2020-01-01 12:00:00'),
        'update_date' => (string) tokyoEpoch('2020-01-01 12:00:00'),
    ], $data));
}

function searchCds(array $params, ?CustomerSearch $search = null): array
{
    return ($search ?? new CustomerSearch)->query($params)->orderBy('cd')->pluck('cd')->map(fn ($cd) => (int) $cd)->all();
}

beforeEach(function () {
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo'));

    insertCustomer(1, [
        'name' => '山田太郎', 'name_kana' => 'ヤマダタロウ', 'tel' => '03-1234-5678', 'kojin_sex' => '男性',
        'kojin_birth' => $now->modify('-30 years -10 days')->format('Y/m/d'),
        'kojin_birth_date' => $now->modify('-30 years -10 days')->getTimestamp(),
        'touroku_date' => (string) tokyoEpoch('2024-05-10 10:00:00'),
        'email1' => 'taro_y@example.jp',
        'last_raiten_date' => $now->modify('-5 days')->format('Y-m-d'),
    ]);
    insertCustomer(2, [
        'name' => '佐藤花子', 'name_kana' => 'サトウハナコ', 'kojin_mobile' => '090(1111)2222', 'kojin_sex' => '女性',
        'kojin_birth' => '1970/03/15',
        'kojin_birth_date' => tokyoEpoch('1970-03-15 00:00:00'),
        'touroku_date' => (string) tokyoEpoch('2024-06-01 09:00:00'),
        'tb_shop_cd' => 2,
        'email1' => 'tarozy@example.jp',
    ]);
    insertCustomer(3, ['name' => '山田次郎', 'disp_flg' => '9', 'tel' => '0312345678']); // 削除済
    insertCustomer(4, ['name' => '山田仮', 'touroku_kubun' => '1']);                      // no es socio
});

it('filtra por nombre y カナ con LIKE y solo socios activos', function () {
    expect(searchCds(['name' => '山田']))->toBe([1])
        ->and(searchCds(['name_kana' => 'ハナ']))->toBe([2])
        ->and(searchCds(['name' => '　山田 ']))->toBe([1])          // recorta espacios como get_form2()
        ->and(searchCds(['name' => '山田', 'disp_flg' => '削除済']))->toBe([3])
        ->and(searchCds([]))->toBe([1, 2]);
});

it('filtra por teléfono, sexo y id', function () {
    expect(searchCds(['tel' => '1234']))->toBe([1])
        ->and(searchCds(['kojin_sex' => '女性']))->toBe([2])
        ->and(searchCds(['id' => '１００１, 1002']))->toBe([1, 2])
        ->and(searchCds(['id' => ',']))->toBe([]);
});

it('escapa el _ en email1 como el legacy', function () {
    // Sin escape, 'taro_y' también encontraría 'tarozy'
    expect(searchCds(['email1' => 'taro_y']))->toBe([1]);
});

it('filtra por edad calculada desde kojin_birth_date y por mes de nacimiento', function () {
    expect(searchCds(['age_1' => '25', 'age_2' => '35']))->toBe([1])
        ->and(searchCds(['age_1' => '50']))->toBe([2])
        ->and(searchCds(['kojin_birth' => '03']))->toBe([2])
        ->and(searchCds(['kojin_birth' => '03', 'kojin_birth2' => '15']))->toBe([2]);
});

it('filtra por fecha de alta en epoch Asia/Tokyo', function () {
    expect(searchCds(['touroku_date_1' => '2024/05/10', 'touroku_date_2' => '2024/05/10']))->toBe([1])
        ->and(searchCds(['touroku_date_1' => '2024/05/11']))->toBe([2])
        ->and(searchCds(['touroku_date_2' => '2024/05/31']))->toBe([1])
        ->and(searchCds(['touroku_date_1' => 'basura']))->toBe([1, 2]); // fecha inválida: se ignora
});

it('filtra por tienda según el rango del personal', function () {
    expect(searchCds(['tb_shop_cd' => ['2']]))->toBe([2])
        ->and(searchCds([], (new CustomerSearch)->forStaff(false, 1)))->toBe([1])
        // si no es master, tb_shop_cd[] se ignora y manda su tienda
        ->and(searchCds(['tb_shop_cd' => ['2']], (new CustomerSearch)->forStaff(false, 1)))->toBe([1]);
});

it('combina con OR y niega con 検索条件に一致しないもの', function () {
    expect(searchCds(['name' => '山田', 'kojin_sex' => '女性']))->toBe([])
        ->and(searchCds(['name' => '山田', 'kojin_sex' => '女性', 'search_method1_type' => '2']))->toBe([1, 2])
        ->and(searchCds(['name' => '山田', 'search_method2_type' => '2']))->toBe([2]);
});

it('filtra por días desde la última visita y por número de visitas', function () {
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo'));
    foreach ([[1, 1, '-3 days'], [2, 1, '-10 days'], [3, 2, '-2 days'], [4, 2, '-400 days']] as [$cd, $user, $ago]) {
        DB::table('tb_raiten_rireki')->insert([
            'cd' => $cd, 'user_cd' => $user, 'shop_cd' => 1,
            'raiten_time' => (string) $now->modify($ago)->getTimestamp(), 'disp_flg' => '1',
        ]);
    }

    expect(searchCds(['search_rireki_daysFromLastVisit' => '5']))->toBe([1])
        // 過去一カ月 con 2 visitas o más
        ->and(searchCds(['search_rireki_type_flg' => '2', 'search_rireki_times' => '2']))->toBe([1])
        // 過去全て con 1 visita o más
        ->and(searchCds(['search_rireki_type_flg' => '7', 'search_rireki_times' => '１']))->toBe([1, 2])
        // menos de 2 (NOT IN de los que tienen más de 1) en el último mes
        ->and(searchCds(['search_rireki_type_flg' => '2', 'search_rireki_times2' => '1']))->toBe([2]);
});

it('filtra por campos personalizados (texto y チェックボックス)', function () {
    DB::table('tb_search_cate')->insert([
        'cd' => 10, 'rank' => 1, 'name' => '追加情報', 'category' => 'category1',
        'disp_flg' => '1', 'serach_flg' => '1', 'hyouji_flg' => '1',
    ]);
    DB::table('tb_search_form')->insert([
        ['cd' => 100, 'rank' => 1, 'flds_row' => 0, 'name' => '趣味', 'form_type' => 'テキスト', 'tb_search_cate_cd' => 10, 'disp_flg' => '1', 'serach_flg' => '1'],
        ['cd' => 101, 'rank' => 2, 'flds_row' => 3, 'name' => '興味', 'form_type' => 'チェックボックス', 'tb_search_cate_cd' => 10, 'disp_flg' => '1', 'serach_flg' => '1'],
    ]);
    DB::table('tb_customer_sub')->insert([
        ['cd' => 1, 'tb_customer_cd' => 1, 'tb_search_cate_cd' => 10, 'tb_search_form_cd' => 100, 'text_data' => 'ゴルフと釣り', 'disp_flg' => '1'],
        ['cd' => 2, 'tb_customer_cd' => 2, 'tb_search_cate_cd' => 10, 'tb_search_form_cd' => 100, 'text_data' => '読書', 'disp_flg' => '1'],
        ['cd' => 3, 'tb_customer_cd' => 1, 'tb_search_cate_cd' => 10, 'tb_search_form_cd' => 101, 'text_data' => 'a<>b<>', 'disp_flg' => '1'],
        ['cd' => 4, 'tb_customer_cd' => 2, 'tb_search_cate_cd' => 10, 'tb_search_form_cd' => 101, 'text_data' => 'b<>c<>', 'disp_flg' => '1'],
    ]);

    expect(searchCds(['form100' => '釣り']))->toBe([1])
        ->and(searchCds(['form101_0' => 'a', 'form101_1' => 'b']))->toBe([1])
        ->and(searchCds(['form101_1' => 'b']))->toBe([1, 2])
        ->and(searchCds(['form101_0' => 'a', 'form101_2' => 'c', 'form101_andor_flg' => 'or']))->toBe([1, 2])
        // otra categoría (category3) no ve los campos de category1
        ->and(searchCds(['form100' => '釣り'], (new CustomerSearch)->forCategory('category3')))->toBe([1, 2]);
});

it('aplica las condiciones sobre una consulta con alias y sobre Eloquent', function () {
    $search = new CustomerSearch;
    $rows = $search->apply(DB::table('tb_customer as c')->where('c.disp_flg', '1'), ['name' => '山田', 'tel' => '1234'])
        ->pluck('c.cd')->map(fn ($cd) => (int) $cd)->all();

    expect($rows)->toBe([1]);

    if (class_exists(App\Models\Customers\Customer::class)) {
        $eloquent = $search->apply(App\Models\Customers\Customer::query(), ['name_kana' => 'サトウ']);
        expect($eloquent)->toBeInstanceOf(Illuminate\Database\Eloquent\Builder::class)
            ->and($eloquent->pluck('cd')->map(fn ($cd) => (int) $cd)->all())->toBe([2]);
    }
});

it('busca por teléfono comparando solo dígitos', function () {
    $rows = (new CustomerSearch)->byPhone('０９０-1111-2222');

    expect($rows)->toHaveCount(1)
        ->and((int) $rows->first()->cd)->toBe(2)
        ->and(array_keys((array) $rows->first()))->toBe(['cd', 'id', 'name', 'name_kana', 'tb_shop_cd'])
        // el cliente 3 tiene el mismo número pero está borrado
        ->and((new CustomerSearch)->byPhone('(03)1234-5678')->pluck('cd')->map(fn ($cd) => (int) $cd)->all())->toBe([1])
        ->and((new CustomerSearch)->byPhone('abc'))->toHaveCount(0);
});

it('describe la búsqueda sin SQL y la reconstruye', function () {
    $search = (new CustomerSearch)->forStaff(false, 1);
    $description = $search->describe(['name' => '山田', 'act' => 'up', 'kensu' => '10', 'kojin_sex' => '', 'form5' => 'x']);

    expect($description['params'])->toBe(['name' => '山田', 'form5' => 'x'])
        ->and($description['display'])->toBe(['名前：山田'])
        ->and($description['staff'])->toBe(['is_master' => false, 'shop_cd' => 1])
        ->and(json_encode($description))->not->toContain('like')
        ->and((new CustomerSearch)->queryFromDescription(json_decode(json_encode($description), true))->pluck('cd')->map(fn ($cd) => (int) $cd)->all())->toBe([1]);
});

it('genera SQL válido para el resto de filtros del legacy', function () {
    DB::table('tb_search_cate')->insert(['cd' => 20, 'rank' => 1, 'name' => 'X', 'category' => 'category1', 'disp_flg' => '1', 'serach_flg' => '1', 'hyouji_flg' => '1']);
    $types = [200 => '数値テキスト', 201 => '年', 202 => '年月', 203 => '年月日', 204 => '起算日', 205 => '月日', 206 => 'メニュー項目'];
    foreach ($types as $cd => $type) {
        DB::table('tb_search_form')->insert(['cd' => $cd, 'rank' => $cd, 'flds_row' => 0, 'name' => $type, 'form_type' => $type, 'tb_search_cate_cd' => 20, 'disp_flg' => '1', 'serach_flg' => '1']);
    }

    $params = [
        'zip' => '1', 'pref' => '東京都', 'email2' => 'a_b', 'kojin_bloodtype' => 'A', 'tb_staff_cd' => '1',
        'update_date_1' => '2020/01/01', 'update_date_2' => '2030/12/31', 'mailmaga_flg' => '配信あり',
        'revocation_point_date_1' => '2020/01/01', 'revocation_point_date_2' => '2030/01/01',
        'revocation_point_day_1' => '1', 'revocation_point_day_2' => '999',
        'touroku_kubun' => '2', 'tb_customer_group_cd' => '1', 'tb_raiten_douki_master_cd' => 'x', 'terminal_no' => 'T1',
        'stamp_count_1' => '0', 'stamp_count_2' => '99', 'error_send_cnt_1' => '0', 'error_send_cnt_2' => '9',
        'service_point_1' => '0', 'service_point_2' => '100', 'my_colle_use' => '無', 'felica_no' => '有', 'kanri_id' => 'K',
        'kingaku_rank_now' => '1', 'raiten_rank_pre' => '2',
        'distance_shop_cd' => '1', 'distance' => '500', 'interval_gps' => '24',
        'form200_from' => '1', 'form200_to' => '9', 'form201_from' => '2000', 'form202_from' => '2020/1', 'form202_to' => '2021/12',
        'form203_from' => '2020/1/1', 'form203_to' => '2021/12/31', 'form204_from' => '20', 'form204_to' => '30',
        'form205_mmm' => '3', 'form205_ddd' => '15',
        'yoyaku_menu_cd' => [1 => '1', 2 => '1'], 'yoyaku_andor_flg' => 'or', 'yoyaku_raiten_flg' => '直近',
        'search_rireki_type_flg' => '6', 'search_rireki_start_year' => '2024', 'search_rireki_start_month' => '01',
        'search_rireki_start_day' => '01', 'search_rireki_start_time' => '0:00', 'search_rireki_end_year' => '2024',
        'search_rireki_end_month' => '12', 'search_rireki_end_day' => '31', 'search_rireki_end_time' => '23:00',
        'search_rireki_day' => '05', 'search_rireki_dow' => '2', 'search_rireki_shop_cd' => '1',
        'search_rireki_times' => '1', 'search_rireki_times2' => '9',
        'search_rireki_averagePeriodTime1' => '1', 'search_rireki_averagePeriodTime2' => '60',
        'search_raiten_yosoku_1' => '2024/01/01', 'search_raiten_yosoku_2' => '2030/12/31',
        'search_kaikei_start_year' => '2024', 'search_kaikei_start_month' => '01', 'search_kaikei_start_day' => '01',
        'search_kaikei_start_time' => '10', 'search_kaikei_end_year' => '2024', 'search_kaikei_end_month' => '12',
        'search_kaikei_end_day' => '31', 'search_kaikei_end_time' => '24', 'search_kaikei_shop_cd' => '1',
        'search_kaikei_resource' => 'スタンプ会計処理', 'search_kaikei_kingaku1' => '1000', 'search_kaikei_kingaku2' => '5000',
    ];

    foreach (['1', '2'] as $method) {
        expect(searchCds($params + ['search_method1_type' => $method]))->toBeArray();
    }
    expect(searchCds(['search_kaikei_kingaku2' => '100']))->toBe([1, 2]) // nadie con promedio > 100
        ->and(searchCds(['search_rireki_type_flg' => '1', 'search_rireki_times' => '1']))->toBe([])
        ->and((new CustomerSearch)->describe($params)['display'])->not->toBeEmpty();
});
