<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Promotions\MailAudience;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// C6-a: 集計ツール (shukei/shukei_list.php) y sus ventanas model1, model1_select, model2, point1, stamp1 y point2

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

function clientesReportEpoch(string $datetime): int
{
    return Carbon::parse($datetime, 'Asia/Tokyo')->getTimestamp();
}

/** Dos socios (男性 1990, 女性 1980; 女性 de la tienda 2) */
function clientesReportCustomers(): array
{
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    $man = Customer::factory()->create(['kojin_sex' => '男性', 'job' => '会社員', 'kojin_birth_date' => clientesReportEpoch('1990-01-01'), 'tb_shop_cd' => 1]);
    $woman = Customer::factory()->create(['kojin_sex' => '女性', 'job' => '', 'kojin_birth_date' => clientesReportEpoch('1980-01-01'), 'tb_shop_cd' => 2]);

    return [$man, $woman];
}

/**
 * Campo 53 (ラジオボタン 'A<>B<>C') de la categoría 5 y respuestas guardadas con cada visita (mismo grupo interno):
 * hombre: A (2026-03-01), B (2026-04-01); mujer: A (2026-04-10). Campo 120 numérico con 3000 / 4500 / 1000.
 */
function clientesReportVisits(Customer $man, Customer $woman): void
{
    DB::table('tb_search_cate')->insert(['cd' => 5, 'name' => 'カルテ', 'category' => 'category1', 'disp_flg' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert([
        ['cd' => 53, 'tb_search_cate_cd' => 5, 'name' => '施術内容', 'form_type' => 'ラジオボタン', 'titles' => 'A<>B<>C', 'disp_flg' => '1', 'rank' => 1, 'unit' => null],
        ['cd' => 120, 'tb_search_cate_cd' => 5, 'name' => '施術時間', 'form_type' => '数値テキスト', 'titles' => '', 'disp_flg' => '1', 'rank' => 2, 'unit' => '分'],
    ]);

    $n = 0;
    foreach ([[$man, 'A', 3000, '2026-03-01 10:00', 1], [$man, 'B', 4500, '2026-04-01 10:00', 1], [$woman, 'A', 1000, '2026-04-10 15:00', 2]] as [$c, $answer, $minutes, $when, $shop]) {
        $n++;
        $epoch = clientesReportEpoch($when);
        DB::table('tb_raiten_rireki')->insert(['cd' => 900 + $n, 'user_cd' => $c->cd, 'shop_cd' => $shop, 'raiten_time' => (string) $epoch, 'disp_flg' => '1',
            'tb_search_cate_cd' => 5, 'tb_customer_sub_inner_group' => "g{$n}", 'tb_point_log_cd' => 700 + $n, 'tb_stamp_log_cd' => 800 + $n]);
        DB::table('tb_customer_sub')->insert([
            ['cd' => 500 + $n * 2, 'tb_customer_cd' => $c->cd, 'tb_search_cate_cd' => 5, 'tb_search_form_cd' => 53, 'text_data' => $answer, 'float_data' => null, 'category_inner_group' => "g{$n}", 'touroku_date' => (string) $epoch],
            ['cd' => 501 + $n * 2, 'tb_customer_cd' => $c->cd, 'tb_search_cate_cd' => 5, 'tb_search_form_cd' => 120, 'text_data' => null, 'float_data' => $minutes / 100, 'category_inner_group' => "g{$n}", 'touroku_date' => (string) $epoch],
        ]);
        DB::table('tb_point_log')->insert(['cd' => 700 + $n, 'tb_shop_cd' => $shop, 'tb_customer_cd' => $c->cd, 'touroku_date' => $epoch, 'kaikei' => $minutes,
            'jidou_point' => $minutes / 100, 'sounyu_point' => 0, 'shouhi_point' => $n === 2 ? 10 : 0, 'disp_flg' => '1']);
        DB::table('tb_stamp_log')->insert(['cd' => 800 + $n, 'tb_shop_cd' => $shop, 'tb_customer_cd' => $c->cd, 'touroku_date' => $epoch, 'kaikei' => $minutes * 2, 'disp_flg' => '1']);
    }
}

// ---------------------------------------------------------------- 集計ツール

it('集計ツール: lista los enlaces visibles por rank en su URL legacy y en la de Laravel', function () {
    DB::table('tb_shukei_link')->insert([
        ['cd' => 1, 'rank' => 2, 'disp_flg' => '1', 'url' => '/system/category1/customer/customer_sumup_list.php', 'title' => '会員登録データ集計', 'naiyou' => '会員登録データからの集計です。'],
        ['cd' => 2, 'rank' => 1, 'disp_flg' => '1', 'url' => '/system/category1/shukei/model1.php?f=53', 'title' => '施術内容集計', 'naiyou' => 'カルテからの集計です。'],
        ['cd' => 3, 'rank' => 3, 'disp_flg' => '9', 'url' => '/x.php', 'title' => '非表示の集計', 'naiyou' => ''],
    ]);

    $this->get('/system/category1/shukei/shukei_list.php')->assertOk()
        ->assertSee('■集計ツール')->assertSee('用途を選択してください')
        ->assertSeeInOrder(['施術内容集計', '会員登録データ集計'])
        ->assertSee("window.open('/system/category1/shukei/model1.php?f=53', 'popShukei2'", false)
        ->assertDontSee('非表示の集計');
    $this->get(route('admin.clientes.reports.index'))->assertOk()->assertSee('■集計ツール');
});

// ---------------------------------------------------------------- model1

it('model1: cuenta las respuestas del campo de opciones, por 男女別 y con filtros de visita', function () {
    [$man, $woman] = clientesReportCustomers();
    clientesReportVisits($man, $woman);

    // Sin cruce: un bloque con todas las visitas (A: 2, B: 1, C: 0)
    $this->get('/system/category1/shukei/model1.php')->assertOk()
        ->assertSee('【施術内容】集計')->assertSee('述べ人数：3人')
        ->assertSee('[<a href="javascript:void(0);" onclick="sendMailConfirm(\'10\');">A</a>]<span style="margin-left: 3px; font-weight: bold;">2</span>人(66.7%)', false)
        ->assertSee('[C]<span style="margin-left: 3px; font-weight: bold;">0</span>人(0.0%)', false)
        ->assertSee('/system/inc/graph_pie.php?w=500&amp;h=200&amp;v=' . rawurlencode('A,2|B,1|C,0'), false)
        ->assertSee('【施術内容】全ての人にメールを送信する');

    // 男女別: un bloque por sexo, con enlaces a 年代別/職業別 del bloque
    $this->get('/system/category1/shukei/model1.php?f=53&cond=0')->assertOk()
        ->assertSee('【施術内容】男女別集計')
        ->assertSeeInOrder(['女性', '述べ人数：1人', '男性', '述べ人数：2人'])
        ->assertSee('?cond=1&amp;sex=' . rawurlencode('男性') . '&amp;f=53', false);

    // 年代別 de los hombres: la edad a la fecha del dato (36 → 30代)
    $this->get('/system/category1/shukei/model1.php?f=53&cond=1&sex=' . urlencode('男性'))->assertOk()
        ->assertSee('男性・年代別集計')->assertSee('30代')->assertDontSee('40代');

    // Periodo de visita y tienda: solo la visita de la mujer en 支店
    $this->get('/system/category1/shukei/model1.php?' . http_build_query(['rireki_start_year' => '2026', 'rireki_start_month' => '04', 'rireki_start_day' => '05', 'rireki_start_time' => '',
        'rireki_end_year' => '', 'rireki_end_month' => '', 'rireki_end_day' => '', 'rireki_end_time' => '', 'shop_cd' => '2']))->assertOk()
        ->assertSee('2026年04月05日 00時から 支店に')->assertSee('述べ人数：1人');

    $this->get('/system/category1/shukei/model1.php?f=999')->assertOk()->assertSee('カスタマイズデータがありません！！！');
    $this->get('/system/category1/shukei/model1.php?f=120')->assertOk()->assertSee('カスタマイズデータから集計を出しません！！！');
});

it('model1: el personal no master solo ve los socios de su tienda', function () {
    [$man, $woman] = clientesReportCustomers();
    clientesReportVisits($man, $woman);
    // Sin filas de permisos, todo contenido de tb_kengen_contents está permitido (como el legacy)
    DB::table('tb_kengen_contents')->insert(['cd' => 1001, 'name' => '顧客管理', 'hyouji_flg' => '1']);
    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    $this->get('/system/category1/shukei/model1.php?touroku_shop_cd=2')->assertOk()
        ->assertSee('述べ人数：2人')
        ->assertSee('<input type="hidden" name="touroku_shop_cd" value="1">', false);
});

it('model1_select: guarda el filtro de la メール (socios activos con la opción) y la メール de S3 lo usa', function () {
    [$man, $woman] = clientesReportCustomers();
    clientesReportVisits($man, $woman);

    $uniqid = $this->post('/system/category1/shukei/model1_select.php', [
        'f' => '53', 'cond' => '0', 'otherCondition' => 'field=sex&value=' . urlencode('男性') . '&option=B', 'conditionText' => '【施術内容】男性・B',
    ])->assertOk()->getContent();

    $audience = app(MailAudience::class);
    expect($audience->has($uniqid))->toBeTrue()
        ->and($audience->display($uniqid))->toBe('【施術内容】男性・B')
        ->and($audience->query($uniqid)->pluck('cd')->all())->toBe([$man->cd]);

    // Opción A sin cruce: los dos; texto SQL del navegador: se ignora (no es field/value/option)
    $uniqid = $this->post('/system/category1/shukei/model1_select.php', ['otherCondition' => "option=A' OR '1'='1", 'conditionText' => 'x'])->getContent();
    expect($audience->query($uniqid)->count())->toBe(0);
    $uniqid = $this->post('/system/category1/shukei/model1_select.php', ['otherCondition' => 'option=A', 'conditionText' => 'x'])->getContent();
    expect($audience->query($uniqid)->pluck('cd')->sort()->values()->all())->toBe(collect([$man->cd, $woman->cd])->sort()->values()->all());
});

// ---------------------------------------------------------------- model2

it('model2: 顧客数, 延べ数 y 合計/平均/最大/最小 del campo numérico con su unidad', function () {
    [$man, $woman] = clientesReportCustomers();
    clientesReportVisits($man, $woman);

    $this->get('/system/category1/shukei/model2.php')->assertOk()
        ->assertSee('【施術時間】集計')
        ->assertSee('[顧客数]<span style="margin-left: 3px; font-weight: bold;">2</span>', false)
        ->assertSee('[顧客延べ数]<span style="margin-left: 3px; font-weight: bold;">3</span>', false)
        ->assertSee('[合計]<span style="margin-left: 3px; font-weight: bold;">85分</span>', false)
        ->assertSee('[平均]<span style="margin-left: 3px; font-weight: bold;">28.33分</span>', false)
        ->assertSee('[最小]<span style="margin-left: 3px; font-weight: bold;">10分</span>', false);

    $this->get(route('admin.clientes.reports.model2', ['cond' => 2]))->assertOk()
        ->assertSeeInOrder(['職業不明', '会社員']);
});

// ---------------------------------------------------------------- point1 / stamp1

it('point1 y stamp1: 会計 de las visitas con puntos o sellos', function () {
    [$man, $woman] = clientesReportCustomers();
    clientesReportVisits($man, $woman);

    $this->get('/system/category1/shukei/point1.php')->assertOk()
        ->assertSee('【会計金額】集計')
        ->assertSee('顧客数：<span style="margin-left: 3px; font-weight: bold;">2人</span>', false)
        ->assertSee('[合計]<span style="margin-left: 3px; font-weight: bold;">8,500円</span>', false)
        ->assertSee('[最大]<span style="margin-left: 3px; font-weight: bold;">4,500円</span>', false);

    $this->get('/system/category1/shukei/stamp1.php?cond=0')->assertOk()
        ->assertSee('【会計金額】男女別集計')
        ->assertSeeInOrder(['女性', '2,000円', '男性', '[合計]<span style="margin-left: 3px; font-weight: bold;">15,000円</span>'], false);
});

// ---------------------------------------------------------------- point2

it('point2: sin periodo pide elegirlo; por mes y por año suma puntos por tienda', function () {
    [$man, $woman] = clientesReportCustomers();
    clientesReportVisits($man, $woman);

    $this->get('/system/category1/shukei/point2.php')->assertOk()
        ->assertSee('【ポイント】集計')->assertSee('集計の方法と期間を指定してください。');

    // 月別 de 2026: 本店 en marzo (30 pts) y abril (45 pts, 10 usados); 支店 en abril (10 pts)
    $response = $this->get('/system/category1/shukei/point2.php?kikan_type=2&type_y_year=2026')->assertOk()
        ->assertSee('2026年 の集計結果&emsp;月別表示', false)
        ->assertSeeInOrder(['本店', '2026年', '3月', '1人', '30', '4月', '1人', '45'])
        ->assertSee('支店');
    expect(substr_count($response->getContent(), '<th>12月</th>'))->toBe(2);

    // 日別 (mes actual, mayo): sin datos
    $this->get('/system/category1/shukei/point2.php?kikan_type=1')->assertOk()->assertSee('該当データがありません。');

    // 年度別 de 支店
    $this->get('/system/category1/shukei/point2.php?kikan_type=3&touroku_shop_cd=2')->assertOk()
        ->assertSee('支店の 年度別 の集計結果', false)->assertSee('<th>2026年</th>', false)->assertDontSee('本店</th>', false);
});
