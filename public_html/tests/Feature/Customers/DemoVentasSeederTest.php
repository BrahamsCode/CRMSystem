<?php

require_once __DIR__.'/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Models\Sales\SalesItem;
use Database\Seeders\Demo\Clientes\CatalogoDemoSeeder;
use Database\Seeders\Demo\Clientes\VentasDemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Datos de demostración de ventas (S2, D2): catálogo (メニュー, 割引, 商品, 施術カテゴリー) y 売上データ / 経費

beforeEach(function () {
    reservasLogin(); // tienda 1, master cd 1 y staff 10
});

/** Clientes demo (demo{n}@example.com) con `$visits` visitas cada uno en los últimos meses */
function demoVentasCustomers(int $count, int $visits): void
{
    $now = Carbon::now('Asia/Tokyo');
    for ($n = 1; $n <= $count; $n++) {
        $customer = Customer::factory()->create(['email1' => "demo{$n}@example.com", 'name' => "デモ 太郎{$n}"]);
        for ($v = 1; $v <= $visits; $v++) {
            $at = $now->copy()->subDays($v * 20 + $n)->setTime(11, 0);
            Visit::create(['user_cd' => $customer->cd, 'user_id' => $customer->id, 'shop_cd' => 1,
                'raiten_time' => (string) $at->getTimestamp(), 'raiten_hiduke' => $at->format('Y/m/d')]);
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

it('siembra el catálogo y una venta por visita de los clientes demo, sin duplicar en la 2ª ejecución', function () {
    demoVentasCustomers(3, 4);

    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);
    $first = demoVentasCounts();

    expect($first['menus'])->toBe(count(CatalogoDemoSeeder::MENUS))
        ->and($first['discounts'])->toBe(count(CatalogoDemoSeeder::DISCOUNTS))
        ->and($first['products'])->toBe(8)
        ->and($first['categories'])->toBe(6)
        ->and($first['sales'])->toBe(12)                       // 3 clientes × 4 visitas
        ->and($first['expenses'])->toBeGreaterThanOrEqual(48)  // 4-6 por mes × 12 meses
        ->and(SalesItem::query()->where('display_title', CatalogoDemoSeeder::MARKER)->count())->toBe(1);

    // Coherencia con las visitas: 営業日 de la visita, cuentas 1..4 por cliente, cd únicos y total = menús - 割引
    $visitDays = DB::table('tb_raiten_rireki')->pluck('raiten_hiduke')->sort()->values()->all();
    expect(DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->pluck('update_date')->sort()->values()->all())->toBe($visitDays)
        ->and(DB::table('tb_customer_sub')->where('tb_search_form_cd', -5)->distinct()->pluck('category_inner_group')->sort()->values()->all())->toBe(['1', '2', '3', '4'])
        ->and(DB::table('tb_customer_sub')->distinct()->count('cd'))->toBe(DB::table('tb_customer_sub')->count());
    $sums = DB::table('tb_customer_sub')->where('tb_search_cate_cd', -1)->selectRaw('tb_search_form_cd, SUM(float_data) AS s')
        ->groupBy('tb_search_form_cd')->pluck('s', 'tb_search_form_cd');
    expect((int) $sums[-5])->toBeLessThanOrEqual((int) $sums[-2] - (int) ($sums[-3] ?? 0) - (int) ($sums[-4] ?? 0) + 1)
        ->and((int) $sums[-5])->toBeGreaterThan(0);

    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);
    expect(demoVentasCounts())->toBe($first);
});

it('no siembra ventas sin clientes demo', function () {
    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);

    expect(demoVentasCounts()['sales'])->toBe(0)->and(demoVentasCounts()['expenses'])->toBe(0);
});

it('llena las pantallas de ventas con los datos sembrados', function () {
    demoVentasCustomers(2, 3);
    $this->seed(CatalogoDemoSeeder::class);
    $this->seed(VentasDemoSeeder::class);
    $from = Carbon::now('Asia/Tokyo')->subYear()->format('Y/m/d');
    $to = Carbon::now('Asia/Tokyo')->format('Y/m/d');

    $this->post(route('legacy.ventas.menu-list'), ['shop_cd' => 1])->assertOk()->assertSee('縮毛矯正')->assertSee('16,500');
    $this->post(route('legacy.ventas.discount-list'), ['shop_cd' => 1])->assertOk()->assertSee('初回割引');
    $this->post(route('admin.ventas.sales.index'), ['shop' => 1, 'date1' => $from, 'date2' => $to])->assertOk()
        ->assertSee('デモ 太郎1')->assertDontSee('履歴がありません。');
    $this->post(route('legacy.ventas.report'), ['shop_cd' => -1, 'date1' => $from, 'date2' => $to])->assertOk()
        ->assertSee('■全店舗')->assertDontSee('データがありません。');
    $this->post(route('legacy.ventas.staff-total'), ['shop_cd' => 1, 'staff_cd' => 1, 'date1' => $from, 'date2' => $to])
        ->assertOk()->assertSee('デモ 太郎');
    $this->get(route('admin.ventas.expenses.index'))->assertOk()->assertDontSee('データがありません。');
    $this->get(route('admin.ventas.products.index'))->assertOk()->assertSee('モイストシャンプー');
});
