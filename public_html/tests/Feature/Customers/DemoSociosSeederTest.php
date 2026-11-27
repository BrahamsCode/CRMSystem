<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Customers\CustomerPhotoImageStore;
use Database\Seeders\Demo\Clientes\SociosDemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Datos de demostración de S2, parte D1 (SociosDemoSeeder): socios, visitas, puntos, sellos y pestañas de la ficha

beforeEach(function () {
    reservasLogin(); // tienda 1 「本店」, master (cd 1) y staff 10 「山田」
    Storage::fake('public');
});

/** Conteos de lo que siembra la parte D1 (solo filas de los socios demo) */
function demoSociosConteos(): array
{
    $cds = DB::table('tb_customer')->where('email1', 'like', 'demo%@example.com')->pluck('cd');
    $of = fn (string $table, string $column) => DB::table($table)->whereIn($column, $cds)->count();

    return [
        'customers' => $cds->count(),
        'visits' => $of('tb_raiten_rireki', 'user_cd'),
        'points' => $of('tb_point_log', 'tb_customer_cd'),
        'stamps' => $of('tb_stamp_log', 'tb_customer_cd'),
        'balances' => $of('tb_customer_point', 'tb_customer_cd'),
        'kartes' => $of('tb_karute', 'tb_customer_cd'),
        'photos' => $of('tb_photo_album', 'tb_customer_cd'),
        'custom' => $of('tb_customer_sub', 'tb_customer_cd'),
        'referrals' => DB::table('tb_customer')->whereIn('shoukai_tb_customer_cd', $cds)->count(),
        'ranks' => DB::table('tb_kingaku_rank_master')->count() + DB::table('tb_raiten_rank_master')->count(),
        'categories' => DB::table('tb_search_cate')->where('name', SociosDemoSeeder::CUSTOM_CATEGORY)->count(),
    ];
}

it('siembra 300 socios con visitas, puntos, sellos y datos de pestañas, y la 2ª ejecución no duplica', function () {
    $this->seed(SociosDemoSeeder::class);

    $counts = demoSociosConteos();
    expect($counts['customers'])->toBe(300)
        ->and($counts['visits'])->toBeGreaterThan(900)
        ->and($counts['points'])->toBe($counts['visits'])          // una ポイント con 会計 por visita
        ->and($counts['stamps'])->toBeGreaterThan(0)->toBeLessThanOrEqual($counts['visits'])
        ->and($counts['balances'])->toBeGreaterThan(200)
        ->and($counts['kartes'])->toBeGreaterThan(50)
        ->and($counts['photos'])->toBeGreaterThan(0)
        ->and($counts['custom'])->toBeGreaterThan(400)
        ->and($counts['referrals'])->toBeGreaterThan(0)
        ->and($counts['ranks'])->toBe(8)
        ->and($counts['categories'])->toBe(1);

    // Formatos del legacy y coherencia de los resúmenes
    $customer = Customer::query()->where('email1', 'demo1@example.com')->firstOrFail();
    expect($customer->kojin_mobile)->toBe('090-0000-0001')
        ->and($customer->touroku_kubun)->toBe('2')
        ->and($customer->kojin_birth)->toMatch('#^\d{4}/\d{2}/\d{2}$#')
        ->and($customer->login_id)->toBe($customer->id);

    $withVisits = DB::table('tb_raiten_rireki')->selectRaw('user_cd, max(raiten_hiduke) as last, count(*) as n, min(cast(raiten_time as bigint)) as first')
        ->whereIn('user_cd', DB::table('tb_customer')->where('email1', 'like', 'demo%@example.com')->select('cd'))
        ->groupBy('user_cd')->get();
    $yearAgo = now('Asia/Tokyo')->subYear()->startOfDay()->getTimestamp();
    foreach ($withVisits as $row) {
        $c = DB::table('tb_customer')->where('cd', $row->user_cd)->first();
        expect($c->last_raiten_date)->toBe($row->last)
            ->and((int) $c->stamp_count)->toBe(min((int) $row->n, 20))
            ->and((int) $row->first)->toBeGreaterThanOrEqual($yearAgo)
            ->and($row->n)->toBeLessThanOrEqual(20);
    }
    $points = DB::table('tb_customer_point')->where('tb_customer_cd', $withVisits->first()->user_cd)->value('service_point');
    $log = DB::table('tb_point_log')->where('tb_customer_cd', $withVisits->first()->user_cd)
        ->selectRaw('sum(jidou_point) - sum(shouhi_point) as saldo')->value('saldo');
    expect((int) $points)->toBe((int) $log);

    // Cada visita apunta a su ポイント; las imágenes del álbum están en el disco
    $visit = DB::table('tb_raiten_rireki')->whereNotNull('tb_point_log_cd')->first();
    expect(DB::table('tb_point_log')->where('cd', $visit->tb_point_log_cd)->value('tb_customer_cd'))->toBe($visit->user_cd);
    $photo = DB::table('tb_photo_album')->first();
    Storage::disk('public')->assertExists(CustomerPhotoImageStore::ALBUM_DIR . "/{$photo->cd}/0_l.jpg");
    Storage::disk('public')->assertExists(CustomerPhotoImageStore::ALBUM_DIR . "/{$photo->cd}/0_ss.jpg");

    // Segunda ejecución: marcador presente, nada nuevo
    $this->seed(SociosDemoSeeder::class);
    expect(demoSociosConteos())->toBe($counts);
});

it('llena la lista y las pestañas de la ficha con los socios demo', function () {
    $this->seed(SociosDemoSeeder::class);

    $cd = DB::table('tb_raiten_rireki')->groupBy('user_cd')->orderByRaw('count(*) desc')->value('user_cd');
    $customer = Customer::query()->findOrFail($cd);

    // 顧客一覧・検索
    $this->post(route('admin.clientes.search'), ['name' => $customer->name, 'act' => 'up', 'se_flg' => '1', 'kensu' => '10'])
        ->assertOk()->assertSee('件見つかりました。')->assertSee($customer->name);

    $tab = fn (string $tab, array $extra = []) => $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => $tab] + $extra))->assertOk();

    $tab('')->assertSee($customer->name)->assertSee('090-0000-');
    $lastVisit = DB::table('tb_raiten_rireki')->where('user_cd', $cd)->orderByDesc('raiten_time')->value('raiten_time');
    $tab('4')->assertSee(Carbon::createFromTimestamp((int) $lastVisit, 'Asia/Tokyo')->format('Y/m/d H:i'))->assertSee('本店');
    $tab('8')->assertSee(number_format((int) DB::table('tb_point_log')->where('tb_customer_cd', $cd)->orderByDesc('touroku_date')->value('kaikei')));
    $tab('10')->assertSee('<span class="genzaiStamp">' . $customer->stamp_count . '</span>', false);

    $karte = DB::table('tb_karute')->first();
    $this->get(route('admin.clientes.show', [$karte->tb_customer_cd, 'tab' => '5']))->assertOk()
        ->assertSee(explode("\n", $karte->comment)[0]);

    $photo = DB::table('tb_photo_album')->first();
    $this->get(route('admin.clientes.show', [$photo->tb_customer_cd, 'tab' => '7', 'photoalbum_cd' => $photo->cd]))->assertOk()
        ->assertSee("photoalbum/{$photo->cd}/0_l.jpg", false)->assertSee($photo->comment);

    $referrer = DB::table('tb_customer')->whereNotNull('shoukai_tb_customer_cd')->where('email1', 'like', 'demo%')->first();
    $this->get(route('admin.clientes.show', [$referrer->shoukai_tb_customer_cd, 'tab' => '13']))->assertOk()
        ->assertSee($referrer->name);

    $sub = DB::table('tb_customer_sub')->where('form_title', 'like', SociosDemoSeeder::CUSTOM_CATEGORY . '<>髪質')->first();
    $this->get(route('admin.clientes.show', [$sub->tb_customer_cd, 'tab' => 'custom', 'search_cate_cd' => $sub->tb_search_cate_cd]))->assertOk()
        ->assertSee(SociosDemoSeeder::CUSTOM_CATEGORY)->assertSee($sub->text_data);
});
