<?php

require_once __DIR__.'/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Models\Sales\SalesItem;
use Database\Seeders\Demo\Clientes\CatalogoDemoSeeder;
use Database\Seeders\Demo\Clientes\DemoVisitPlan;
use Database\Seeders\Demo\Clientes\VentasDemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Datos de demostración de ventas (S2, D2): catálogo (メニュー, 割引, 商品, 施術カテゴリー) y 売上データ / 経費

beforeEach(function () {
    reservasLogin(); // tienda 1, master cd 1 y staff 10
});

/**
 * Clientes demo (demo{n}@example.com) con una visita por importe de `$amounts` y su ポイント (tb_point_log.kaikei,
 * como SociosDemoSeeder). `$kartes`: [índice de visita => 'カット・トリートメント'] con la カルテ de esa visita.
 */
function demoVentasCustomers(int $count, array $amounts, array $kartes = [], int $shopCd = 1, int $staffCd = 10): void
{
    $now = Carbon::now('Asia/Tokyo');
    $pointCd = (int) DB::table('tb_point_log')->max('cd');
    $offset = (int) DB::table('tb_customer')->count();
    for ($n = $offset + 1; $n <= $offset + $count; $n++) {
        $customer = Customer::factory()->create(['email1' => "demo{$n}@example.com", 'name' => "デモ 太郎{$n}", 'tb_shop_cd' => $shopCd]);
        foreach (array_values($amounts) as $v => $kaikei) {
            $at = $now->copy()->subDays(($v + 1) * 20 + $n)->setTime(11, 0);
            DB::table('tb_point_log')->insert(['cd' => ++$pointCd, 'tb_shop_cd' => $shopCd, 'tb_staff_cd' => $staffCd,
                'tb_customer_cd' => $customer->cd, 'touroku_date' => $at->getTimestamp(), 'kaikei' => $kaikei, 'raiten_shori' => '1']);
            Visit::create(['user_cd' => $customer->cd, 'user_id' => $customer->id, 'shop_cd' => $shopCd, 'tb_point_log_cd' => $pointCd,
                'raiten_time' => (string) $at->getTimestamp(), 'raiten_hiduke' => $at->format('Y/m/d')]);
            if (isset($kartes[$v])) {
                DB::table('tb_karute')->insert(['cd' => $pointCd, 'tb_customer_cd' => $customer->cd, 'tb_shop_cd' => $shopCd,
                    'comment' => $kartes[$v]."\n仕上がりにご満足いただけました。", 'touroku_date' => $at->getTimestamp(), 'disp_flg' => '1']);
            }
        }
    }
}

function demoVentasCounts(): array
{
    return [
        'menus' => DB::table('tb_karute_menu')->count(),
        'discounts' => DB::table('tb_karute_waribiki')->count(),
        'products' => DB::table('sales_items')->count(),
        'categories' => DB::table('tb_sejutsu_cate')->count(),
        'sales' => DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->count(),
        'saleRows' => DB::table('tb_customer_sub')->where('tb_search_cate_cd', -1)->count(),
        'expenses' => DB::table('tb_customer_sub')->where('tb_search_form_cd', -10)->count(),
    ];
}

/** Total de cada venta (fila 合計) junto al kaikei de la ポイント de su visita (cliente + 営業日) */
function demoVentasTotalsVsPoints(): array
{
    return DB::table('tb_raiten_rireki as r')
        ->join('tb_point_log as p', 'p.cd', '=', 'r.tb_point_log_cd')
        ->leftJoin('tb_customer_sub as s', fn ($j) => $j->on('s.tb_customer_cd', '=', 'r.user_cd')
            ->on('s.update_date', '=', 'r.raiten_hiduke')->where('s.tb_search_form_cd', -5))
        ->get(['p.kaikei', 's.float_data', 's.int_data', 'r.shop_cd'])
        ->map(fn ($r) => [(int) $r->kaikei, $r->float_data === null ? null : (int) $r->float_data, (int) $r->int_data, (int) $r->shop_cd])
        ->all();
}

it('siembra el catálogo y una venta por visita con el total de su ポイント, sin duplicar en la 2ª ejecución', function () {
    // カット 4950; カット+トリートメント 8250; 0円 (全額サービス); ~300.000円; importe sin combinación exacta (手動割引)
    $amounts = [4950, 8250, 0, 300000, 7777, 6600];
    demoVentasCustomers(3, $amounts, [0 => 'カット', 5 => 'カラー']);

    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);
    $first = demoVentasCounts();

    expect($first['menus'])->toBe(count(CatalogoDemoSeeder::MENUS))
        ->and($first['discounts'])->toBe(count(CatalogoDemoSeeder::DISCOUNTS))
        ->and($first['products'])->toBe(9)
        ->and($first['categories'])->toBe(6)
        ->and($first['sales'])->toBe(18)                       // 3 clientes × 6 visitas
        ->and($first['expenses'])->toBeGreaterThanOrEqual(48)  // 4-6 por mes × 12 meses
        ->and(SalesItem::query()->where('display_title', CatalogoDemoSeeder::MARKER)->count())->toBe(1)
        ->and(mb_strlen(SalesItem::query()->orderByDesc(DB::raw('length(name)'))->value('name')))->toBe(CatalogoDemoSeeder::LONG_PRODUCT_NAME_LENGTH);

    // Para cada visita demo: total de la venta = kaikei de su tb_point_log
    foreach (demoVentasTotalsVsPoints() as [$kaikei, $total, $saleShop, $visitShop]) {
        expect($total)->toBe($kaikei)->and($saleShop)->toBe($visitShop);
    }

    // 0円 con el 割引 100 %; la スタッフ es la de la ポイント; la カルテ manda en los menús
    $zero = DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->where('float_data', 0)->get();
    expect($zero)->toHaveCount(3);
    expect(DB::table('tb_customer_sub')->where('tb_search_form_cd', -3)->where('text_data', '全額サービス')->count())->toBe(3)
        ->and(DB::table('tb_customer_sub')->where('tb_search_form_cd', -1)->distinct()->pluck('array_data')->all())->toBe(['10'])
        ->and(DB::table('tb_customer_sub')->where('tb_search_form_cd', -2)->where('text_data', 'カラー')->count())->toBeGreaterThanOrEqual(3);

    // Cuentas 1..6 por cliente y cd únicos
    expect(DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->distinct()->pluck('category_inner_group')->sort()->values()->all())
        ->toBe(['1', '2', '3', '4', '5', '6'])
        ->and(DB::table('tb_customer_sub')->distinct()->count('cd'))->toBe(DB::table('tb_customer_sub')->count());

    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);
    expect(demoVentasCounts())->toBe($first);
});

it('usa los menús y 割引 de DemoVisitPlan cuando el kaikei de la visita sale de ese plan (SociosDemoSeeder)', function () {
    $this->seed(CatalogoDemoSeeder::class);
    $customer = Customer::factory()->create(['email1' => 'demo1@example.com']);
    $at = Carbon::now('Asia/Tokyo')->subDays(3);
    $plans = [];
    // cd de visita elegidos para cubrir los casos de DemoVisitPlan: normal, importe 0 (cd % 509 = 0) y grande (cd % 701 = 350)
    foreach ([1018 => 0, 350 => 1, 1001 => 2, 1002 => 3, 1003 => 4] as $visitCd => $i) {
        $day = $at->copy()->subDays($i * 7);
        $plan = (new DemoVisitPlan)->for($visitCd, 1);
        $plans[$day->format('Y/m/d')] = $plan;
        DB::table('tb_point_log')->insert(['cd' => $visitCd, 'tb_shop_cd' => 1, 'tb_staff_cd' => 10, 'tb_customer_cd' => $customer->cd,
            'touroku_date' => $day->getTimestamp(), 'kaikei' => $plan['total'], 'raiten_shori' => '1']);
        Visit::create(['cd' => $visitCd, 'user_cd' => $customer->cd, 'user_id' => $customer->id, 'shop_cd' => 1, 'tb_point_log_cd' => $visitCd,
            'raiten_time' => (string) $day->getTimestamp(), 'raiten_hiduke' => $day->format('Y/m/d')]);
    }
    expect(collect($plans)->pluck('kind')->unique()->sort()->values()->all())->toBe(['large', 'normal', 'zero']);

    $this->seed(VentasDemoSeeder::class);

    foreach ($plans as $day => $plan) {
        $rows = DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->where('update_date', $day)->orderBy('cd')->get();
        expect($rows->where('tb_search_form_cd', -2)->pluck('text_data')->all())->toBe($plan['menus']->pluck('name')->all())
            ->and($rows->where('tb_search_form_cd', -3)->pluck('text_data')->all())->toBe($plan['discounts']->pluck('name')->all())
            ->and((int) $rows->firstWhere('tb_search_form_cd', -4)?->float_data)->toBe($plan['other_value'])
            ->and((int) $rows->firstWhere('tb_search_form_cd', -5)->float_data)->toBe($plan['total']);
    }
    foreach (demoVentasTotalsVsPoints() as [$kaikei, $total]) {
        expect($total)->toBe($kaikei);
    }
});

it('siembra catálogo y gastos en todas las tiendas activas y completa las tiendas nuevas al repetir', function () {
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '梅田店', 'disp_flg' => '1']);
    DB::table('tb_shop')->insert(['cd' => 9, 'master_cd' => 1, 'kaishamei' => '閉店', 'disp_flg' => '9']);
    demoVentasCustomers(1, [4950, 16500]);
    demoVentasCustomers(1, [8800], [], 2, 1);

    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);

    foreach ([1, 2] as $shop) {
        expect(DB::table('tb_karute_menu')->where('tb_shop_cd', $shop)->count())->toBe(count(CatalogoDemoSeeder::MENUS))
            ->and(DB::table('tb_karute_waribiki')->where('tb_shop_cd', $shop)->count())->toBe(count(CatalogoDemoSeeder::DISCOUNTS))
            ->and(DB::table('tb_customer_sub')->where('tb_search_form_cd', -10)->where('int_data', $shop)->count())->toBeGreaterThanOrEqual(48);
    }
    expect(DB::table('tb_karute_menu')->where('tb_shop_cd', 9)->count())->toBe(0)
        ->and(DB::table('tb_customer_sub')->where('tb_search_form_cd', -10)->where('int_data', 9)->count())->toBe(0)
        ->and(SalesItem::query()->first()->availableShopCds())->toBe([1, 2]);
    foreach (demoVentasTotalsVsPoints() as [$kaikei, $total, $saleShop, $visitShop]) {
        expect($total)->toBe($kaikei)->and($saleShop)->toBe($visitShop);
    }

    // Tienda creada después (p. ej. NucleoDemoSeeder): al repetir recibe su catálogo y los productos la incluyen
    DB::table('tb_shop')->insert(['cd' => 3, 'master_cd' => 1, 'kaishamei' => '京都店', 'disp_flg' => '1']);
    $this->seed(CatalogoDemoSeeder::class);
    expect(DB::table('tb_karute_menu')->where('tb_shop_cd', 3)->count())->toBe(count(CatalogoDemoSeeder::MENUS))
        ->and(DB::table('tb_karute_menu')->count())->toBe(3 * count(CatalogoDemoSeeder::MENUS))
        ->and(SalesItem::query()->first()->availableShopCds())->toBe([1, 2, 3]);
});

it('no siembra ventas sin clientes demo', function () {
    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);

    expect(demoVentasCounts()['sales'])->toBe(0)->and(demoVentasCounts()['expenses'])->toBe(0);
});

it('llena las pantallas de ventas con los datos sembrados', function () {
    demoVentasCustomers(2, [4950, 300000, 0]);
    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);
    $from = Carbon::now('Asia/Tokyo')->subYear()->format('Y/m/d');
    $to = Carbon::now('Asia/Tokyo')->format('Y/m/d');

    $this->post(route('legacy.ventas.menu-list'), ['shop_cd' => 1])->assertOk()->assertSee('縮毛矯正')->assertSee('16,500');
    $this->post(route('legacy.ventas.discount-list'), ['shop_cd' => 1])->assertOk()->assertSee('初回割引')->assertSee('全額サービス');
    $this->post(route('admin.ventas.sales.index'), ['shop' => 1, 'date1' => $from, 'date2' => $to])->assertOk()
        ->assertSee('デモ 太郎1')->assertDontSee('履歴がありません。');
    $this->post(route('legacy.ventas.report'), ['shop_cd' => -1, 'date1' => $from, 'date2' => $to])->assertOk()
        ->assertSee('■全店舗')->assertDontSee('データがありません。');
    $this->post(route('legacy.ventas.staff-total'), ['shop_cd' => 1, 'staff_cd' => 10, 'date1' => $from, 'date2' => $to])
        ->assertOk()->assertSee('デモ 太郎1')->assertSee('609,900'); // 2 × (4950 + 300000 + 0)
    $this->get(route('admin.ventas.expenses.index'))->assertOk()->assertDontSee('データがありません。');
    $this->get(route('admin.ventas.products.index'))->assertOk()->assertSee('モイストシャンプー');
});
