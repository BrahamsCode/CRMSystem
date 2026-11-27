<?php

namespace Database\Seeders\Demo\Clientes;

use App\Models\Customers\CustomerSub;
use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use App\Services\Nucleo\Hatuban;
use App\Services\Sales\ExpenseService;
use App\Services\Sales\SaleService;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración (S2, parte D2): 売上データ y 経費 de ventas internas (karute_menu).
 *
 * - Una venta por cada visita (`tb_raiten_rireki`, la crea SociosDemoSeeder) de los clientes demo
 *   (`email1` demo%@example.com): mismo cliente, tienda y 営業日 (`raiten_hiduke`), registrada al terminar
 *   la visita. En el legacy grabar una venta (karute_new_register.php) NO crea el 来店: aquí tampoco.
 *   Las filas siguen el formato de SaleService::store() (staff -1, menús -2, 割引 -3, 手動割引 -4, 合計 -5)
 *   y los importes salen de SaleService::calculate(), igual que el formulario.
 * - Gastos (経費, form -10) de los últimos 12 meses en cada tienda activa.
 *
 * Idempotente: si algún cliente demo ya tiene ventas (tb_search_cate_cd = -1), no hace nada. Sin clientes
 * demo (SociosDemoSeeder no ejecutado) tampoco siembra nada.
 */
class VentasDemoSeeder extends Seeder
{
    private const SEED = 20261203;

    private const CHUNK = 500;

    /** Menús principales de la visita y su peso (de CatalogoDemoSeeder::MENUS) */
    private const MAIN_MENUS = ['カット' => 45, 'カラー' => 18, 'リタッチカラー' => 8, 'パーマ' => 8, 'デジタルパーマ' => 4,
        '縮毛矯正' => 5, 'キッズカット' => 4, '前髪カット' => 4, 'ブロー' => 4];

    /** Gastos: [使用目的, importe mínimo, máximo] */
    private const EXPENSES = [
        ['薬剤仕入れ', 30000, 90000],
        ['タオル・消耗品', 3000, 15000],
        ['店舗備品', 2000, 20000],
        ['広告宣伝費', 10000, 50000],
        ['水道光熱費', 25000, 60000],
        ['クリーニング代', 5000, 12000],
    ];

    private Generator $faker;

    /** Contador de la parte final de los cd de venta (createHatsuban: epoch + tienda + 6 cifras) */
    private int $sequence = 0;

    public function __construct(private Hatuban $hatuban) {}

    public function run(): void
    {
        $customers = DB::table('tb_customer')->where('email1', 'like', 'demo%@example.com')
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")->get(['cd', 'tb_shop_cd', 'tb_staff_cd'])->keyBy('cd');
        if ($customers->isEmpty()) {
            $this->command?->warn('VentasDemoSeeder: no hay clientes demo (demo%@example.com); se omite.');

            return;
        }
        if (DB::table('tb_customer_sub')->where('tb_search_cate_cd', CustomerSub::SALE_CATEGORY)
            ->whereIn('tb_customer_cd', $customers->keys())->exists()) {
            $this->command?->info('VentasDemoSeeder: ya sembrado (los clientes demo tienen ventas), se omite.');

            return;
        }

        $this->faker = FakerFactory::create('ja_JP');
        $this->faker->seed(self::SEED);

        DB::transaction(function () use ($customers) {
            $this->seedSales($customers);
            $this->seedExpenses();
        });
    }

    /** Una venta por visita, en orden cronológico por cliente (category_inner_group = máximo + 1) */
    private function seedSales(Collection $customers): void
    {
        $visits = DB::table('tb_raiten_rireki')->whereIn('user_cd', $customers->keys())
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")->whereRaw("raiten_time ~ '^[0-9]+$'")
            ->orderBy('user_cd')->orderByRaw('raiten_time::bigint')->get(['user_cd', 'shop_cd', 'raiten_time', 'raiten_hiduke']);

        $menusByShop = [];
        $discountsByShop = [];
        $staffByShop = [];
        $groups = [];
        $rows = [];

        foreach ($visits as $visit) {
            $shopCd = (int) ($visit->shop_cd ?: $customers[$visit->user_cd]->tb_shop_cd);
            $menus = $menusByShop[$shopCd] ??= ServiceMenu::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->get();
            $discounts = $discountsByShop[$shopCd] ??= Discount::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')->get();
            $staffList = $staffByShop[$shopCd] ??= $this->staffOf($shopCd);
            if ($menus->isEmpty() || $staffList->isEmpty()) {
                continue; // sin catálogo o sin personal en la tienda no hay venta posible
            }

            $customer = $customers[$visit->user_cd];
            $staff = $staffList->firstWhere('cd', (int) $customer->tb_staff_cd)
                ?? $staffList[(int) $customer->cd % $staffList->count()];
            $groups[$customer->cd] = ($groups[$customer->cd] ?? 0) + 1;
            $epoch = (int) $visit->raiten_time + $this->faker->numberBetween(40, 150) * 60; // la caja, al terminar
            $businessDay = $visit->raiten_hiduke ?: Carbon::createFromTimestamp((int) $visit->raiten_time, 'Asia/Tokyo')->format('Y/m/d');

            array_push($rows, ...$this->saleRows((int) $customer->cd, $staff, $shopCd, $businessDay, $epoch,
                $groups[$customer->cd], $this->pickMenus($menus, $staff), $this->pickDiscounts($discounts)));

            if (count($rows) >= self::CHUNK) {
                DB::table('tb_customer_sub')->insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            DB::table('tb_customer_sub')->insert($rows);
        }
    }

    /** Personal activo de la tienda; si no hay, el activo de cualquier tienda; si tampoco, cualquiera (master) */
    private function staffOf(int $shopCd): Collection
    {
        $columns = ['cd', 'name', 'kingaku_buai'];
        $staff = DB::table('tb_staff')->where('shop_cd', $shopCd)->where('disp_flg', '1')->orderBy('cd')->get($columns);
        if ($staff->isEmpty()) {
            $staff = DB::table('tb_staff')->where('disp_flg', '1')->orderBy('cd')->get($columns);
        }
        if ($staff->isEmpty()) {
            $staff = DB::table('tb_staff')->orderBy('cd')->limit(1)->get($columns);
        }

        return $staff->values();
    }

    /** Menú principal + a veces トリートメント/ヘッドスパ, 物販 y 指名料 (si el catálogo los tiene) */
    private function pickMenus(Collection $menus, object $staff): Collection
    {
        $byName = $menus->keyBy('name');
        $picked = collect();

        $main = $this->faker->randomElement($this->weighted(self::MAIN_MENUS));
        $picked->push($byName[$main] ?? $menus->where('type', ServiceMenu::TYPE_SERVICE)->first() ?? $menus->first());
        foreach (['トリートメント' => 30, 'ヘッドスパ' => 15, '指名料' => 35] as $name => $chance) {
            if (isset($byName[$name]) && $this->faker->boolean($chance)) {
                $picked->push($byName[$name]);
            }
        }
        $retail = $menus->where('type', ServiceMenu::TYPE_RETAIL)->values();
        if ($retail->isNotEmpty() && $this->faker->boolean(15)) {
            $picked->push($retail[$this->faker->numberBetween(0, $retail->count() - 1)]);
        }

        return $picked;
    }

    /** ~20 % con un 割引 del catálogo */
    private function pickDiscounts(Collection $discounts): Collection
    {
        if ($discounts->isEmpty() || ! $this->faker->boolean(20)) {
            return collect();
        }

        return collect([$discounts[$this->faker->numberBetween(0, $discounts->count() - 1)]]);
    }

    /**
     * Filas de una venta con el formato de SaleService::store(). ~5 % lleva además un 手動割引 (その他の割引) de 500円.
     * Si el total saliera negativo, se graba sin descuentos (el formulario no permitía grabarla).
     */
    private function saleRows(int $customerCd, object $staff, int $shopCd, string $businessDay, int $epoch, int $group,
        Collection $menus, Collection $discounts): array
    {
        $otherValue = $this->faker->boolean(5) ? 500 : 0;
        $amounts = app(SaleService::class)->calculate($menus, $discounts, $otherValue, '2');
        if ($amounts['total_float'] < 0) {
            $amounts = app(SaleService::class)->calculate($menus, collect(), 0, '2');
        }
        $rate = (float) $staff->kingaku_buai;

        $cd = $this->saleCd($epoch, $shopCd);
        $common = [
            'tb_customer_cd' => $customerCd, 'tb_search_cate_cd' => CustomerSub::SALE_CATEGORY, 'disp_flg' => '1',
            'category_inner_group' => (string) $group, 'touroku_date' => (string) $epoch, 'update_date' => $businessDay,
            'int_data' => $shopCd, 'pic_flg' => null,
        ];
        $row = function (int $form, string $title, array $values) use (&$cd, $common): array {
            return ['cd' => $cd++, 'tb_search_form_cd' => $form, 'form_title' => $title] + $values + $common;
        };

        $rows = [$row(SaleService::FORM_STAFF, 'スタッフ情報', [
            'rank' => (int) ($amounts['commission_base'] * $rate / 100), 'float_data' => $rate,
            'text_data' => (string) $staff->name, 'array_data' => (string) $staff->cd, 'form_type' => '',
        ])];
        foreach ($menus as $menu) {
            $rows[] = $row(SaleService::FORM_MENU, 'メニュー情報', [
                'rank' => null, 'float_data' => (float) $menu->kingaku, 'text_data' => (string) $menu->name,
                'array_data' => (string) $menu->cd,
                'form_type' => ['1' => '技術', '2' => '物販'][(string) $menu->type] ?? 'その他',
            ]);
        }
        foreach ($amounts['discounts'] as $discount) {
            $rows[] = $row(SaleService::FORM_DISCOUNT, '割引情報', [
                'rank' => null, 'float_data' => $discount['amount'], 'text_data' => (string) $discount['discount']->name,
                'array_data' => (string) $discount['discount']->cd, 'form_type' => $discount['label'],
                'pic_flg' => (string) $discount['discount']->buai_flg,
            ]);
        }
        if ($amounts['other']) {
            $rows[] = $row(SaleService::FORM_OTHER_DISCOUNT, '手動割引情報', [
                'rank' => null, 'float_data' => $amounts['other']['amount'], 'text_data' => '', 'array_data' => '',
                'form_type' => $amounts['other']['label'], 'pic_flg' => '2',
            ]);
        }
        $rows[] = $row(SaleService::FORM_TOTAL, '合計', [
            'rank' => $amounts['commission_base'], 'float_data' => $amounts['total'], 'text_data' => '',
            'array_data' => '', 'form_type' => '',
        ]);

        return $rows;
    }

    /** createHatsuban() del legacy (epoch + tienda 3 cifras + 6 cifras) con secuencia propia: sin choques entre ventas */
    private function saleCd(int $epoch, int $shopCd): int
    {
        $this->sequence += 10; // cada venta usa como mucho ~8 cd consecutivos

        return (int) ($epoch.sprintf('%03d', $shopCd % 1000).sprintf('%06d', $this->sequence % 1000000));
    }

    /** 経費: 4-6 por mes y tienda en los últimos 12 meses (cd con hatuban 'tb_customer_sub', como ExpenseService) */
    private function seedExpenses(): void
    {
        $today = Carbon::now('Asia/Tokyo')->startOfDay();
        $rows = [];

        foreach (CatalogoDemoSeeder::activeShopCds() as $shopCd) {
            for ($month = 11; $month >= 0; $month--) {
                $start = $today->copy()->startOfMonth()->subMonthsNoOverflow($month);
                $lastDay = $month === 0 ? $today->day : $start->daysInMonth;
                foreach ($this->faker->randomElements(self::EXPENSES, $this->faker->numberBetween(4, 6)) as [$purpose, $min, $max]) {
                    $day = $start->copy()->day($this->faker->numberBetween(1, $lastDay));
                    $rows[] = [
                        'cd' => $this->hatuban->next('tb_customer_sub'),
                        'tb_search_cate_cd' => CustomerSub::EXPENSE_CATEGORY,
                        'tb_search_form_cd' => ExpenseService::FORM,
                        'float_data' => (float) (intdiv($this->faker->numberBetween($min, $max), 100) * 100),
                        'text_data' => $purpose,
                        'touroku_date' => (string) $day->copy()->setTime(20, 0)->getTimestamp(),
                        'update_date' => $day->format('Y/m/d'),
                        'int_data' => $shopCd,
                        'disp_flg' => '1',
                    ];
                }
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('tb_customer_sub')->insert($chunk);
        }
    }

    /** ['a' => 2, 'b' => 1] → ['a', 'a', 'b'] para randomElement */
    private function weighted(array $weights): array
    {
        $list = [];
        foreach ($weights as $value => $weight) {
            array_push($list, ...array_fill(0, $weight, $value));
        }

        return $list;
    }
}
