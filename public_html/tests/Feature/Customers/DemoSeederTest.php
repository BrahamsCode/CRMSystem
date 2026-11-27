<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\CustomerSub;
use App\Services\Sales\SaleService;
use Database\Seeders\Demo\Clientes\CatalogoDemoSeeder;
use Database\Seeders\Demo\Clientes\ReservasDemoSeeder;
use Database\Seeders\Demo\Clientes\SociosDemoSeeder;
use Database\Seeders\Demo\Clientes\VentasDemoSeeder;
use Database\Seeders\Demo\ClientesDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Datos de demostración de S2 completos (ClientesDemoSeeder: catálogo → socios → ventas → reservas).
// Las partes que aún no existan se saltan, como en el seeder.

beforeEach(function () {
    reservasLogin(); // tienda 1 「本店」, master (cd 1) y staff 10 「山田」
    // Disco en /tmp del contenedor, único por prueba: Storage::fake() borra y recrea el directorio en el
    // montaje de Windows y el mkdir siguiente falla a veces (UnableToCreateDirectory)
    Storage::set('public', Storage::createLocalDriver(['root' => sys_get_temp_dir() . '/demo-public-' . uniqid('', true)]));
});

/** Filas de las tablas que tocan las cuatro partes */
function demoClientesConteos(): array
{
    $tables = [
        'tb_customer', 'tb_raiten_rireki', 'tb_point_log', 'tb_stamp_log', 'tb_customer_point', 'tb_karute', 'tb_photo_album',
        'tb_customer_sub', 'tb_search_cate', 'tb_search_form', 'tb_kingaku_rank_master', 'tb_raiten_rank_master',
        'tb_karute_menu', 'tb_karute_waribiki', 'sales_items', 'tb_sejutsu_cate',
        'tb_yoyaku', 'tb_yoyaku_config', 'tb_yoyaku_koumoku_master', 'tb_yoyaku_koumoku_category', 'tb_setsubi', 'tb_staff_works',
    ];

    return collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
}

it('siembra las cuatro partes de S2 en orden y la 2ª ejecución no duplica', function () {
    $this->seed(ClientesDemoSeeder::class);
    $counts = demoClientesConteos();

    if (class_exists(CatalogoDemoSeeder::class)) {
        expect($counts['tb_karute_menu'])->toBeGreaterThan(0)
            ->and($counts['tb_karute_waribiki'])->toBeGreaterThan(0)
            ->and($counts['sales_items'])->toBeGreaterThan(0);
    }

    if (class_exists(SociosDemoSeeder::class)) {
        expect(DB::table('tb_customer')->where('email1', 'like', 'demo%@example.com')->count())->toBe(SociosDemoSeeder::TOTAL + count(SociosDemoSeeder::EDGE_CASES))
            ->and($counts['tb_raiten_rireki'])->toBeGreaterThan(0)
            ->and($counts['tb_point_log'])->toBe($counts['tb_raiten_rireki'])
            ->and($counts['tb_karute'])->toBeGreaterThan(0);
        // La カルテ usa los 施術カテゴリー del catálogo (no crea otros si ya los hay)
        if (class_exists(CatalogoDemoSeeder::class)) {
            expect(DB::table('tb_sejutsu_cate')->where('disp_flg', '1')->distinct()->count('name'))->toBe($counts['tb_sejutsu_cate']);
        }
    }

    if (class_exists(VentasDemoSeeder::class)) {
        expect(DB::table('tb_customer_sub')->where('tb_search_cate_cd', CustomerSub::SALE_CATEGORY)->count())->toBeGreaterThan(0);

        // Cuadre: por cada socio activo, los 合計 de sus ventas = los kaikei de las ポイント de sus visitas
        $active = DB::table('tb_customer')->where('email1', 'like', 'demo%@example.com')->where('disp_flg', '1')->pluck('cd');
        $kaikei = DB::table('tb_raiten_rireki as r')->join('tb_point_log as p', 'p.cd', '=', 'r.tb_point_log_cd')
            ->whereIn('r.user_cd', $active)->orderBy('r.user_cd')->orderByRaw('r.raiten_time::bigint')->orderBy('r.cd')
            ->get(['r.user_cd', 'p.kaikei'])->groupBy('user_cd')->map(fn ($rows) => $rows->pluck('kaikei')->map(fn ($v) => (int) $v)->all());
        $totals = DB::table('tb_customer_sub')->where('tb_search_cate_cd', CustomerSub::SALE_CATEGORY)
            ->where('tb_search_form_cd', SaleService::FORM_TOTAL)->whereIn('tb_customer_cd', $active)
            ->orderBy('tb_customer_cd')->orderByRaw('category_inner_group::int')
            ->get(['tb_customer_cd', 'float_data'])->groupBy('tb_customer_cd')->map(fn ($rows) => $rows->pluck('float_data')->map(fn ($v) => (int) $v)->all());
        expect($totals->all())->toEqual($kaikei->all());
        expect($kaikei->flatten()->filter(fn ($v) => $v === 0)->count())->toBeGreaterThanOrEqual(2)
            ->and($kaikei->flatten()->max())->toBeGreaterThanOrEqual(300000);
    }

    if (class_exists(ReservasDemoSeeder::class)) {
        expect($counts['tb_yoyaku'])->toBeGreaterThan(0);
    }

    $this->seed(ClientesDemoSeeder::class);
    expect(demoClientesConteos())->toBe($counts);
});
