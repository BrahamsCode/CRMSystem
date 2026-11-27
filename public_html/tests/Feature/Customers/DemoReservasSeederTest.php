<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Database\Seeders\Demo\Clientes\ReservasDemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Datos de demostración de 予約管理 (S2, parte D3): configuración, franjas, reservas y casos límite

beforeEach(function () {
    // Viernes 15:00: hoy hay reservas ya terminadas, en curso y por venir
    Carbon::setTestNow(Carbon::create(2026, 11, 27, 15, 0, 0, 'Asia/Tokyo'));
    reservasLogin(); // tienda 1, master (cd 1) y 山田 (cd 10)

    // Segunda tienda (como 梅田店 de NucleoDemoSeeder) con dos empleados y sin 予約設定
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '梅田店', 'disp_flg' => '1']);
    DB::table('tb_staff')->insert([
        ['cd' => 20, 'office_cd' => 1, 'shop_cd' => 2, 'login_id' => 'umeda01', 'login_pass' => 'x', 'name' => '田中 翔太', 'disp_flg' => '1'],
        ['cd' => 21, 'office_cd' => 1, 'shop_cd' => 2, 'login_id' => 'umeda02', 'login_pass' => 'x', 'name' => '伊藤 さくら', 'disp_flg' => '1'],
    ]);

    foreach (range(1, 12) as $n) {
        Customer::factory()->create(['email1' => "demo{$n}@example.com", 'tb_shop_cd' => $n <= 6 ? 1 : 2]);
    }
});

afterEach(fn () => Carbon::setTestNow());

/** Conteos de todo lo que siembra la parte D3 */
function reservasDemoCounts(): array
{
    return collect(['tb_yoyaku', 'tb_yoyaku_uketsuke_day', 'tb_yoyaku_config', 'tb_yoyaku_koumoku_master',
        'tb_yoyaku_koumoku_category', 'tb_staff_works', 'tb_setsubi'])
        ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
}

/** Reservas sembradas con fecha, tienda y empleado de su franja */
function reservasDemoRows()
{
    return DB::table('tb_yoyaku as y')
        ->join('tb_yoyaku_uketsuke_day as d', 'd.cd', '=', 'y.tb_yoyaku_uketsuke_day_cd')
        ->join('tb_staff as s', 's.cd', '=', 'd.tb_staff_cd')
        ->get(['y.*', 'd.moushikomi_date', 'd.tb_staff_cd', 's.shop_cd']);
}

it('siembra configuración y reservas en todas las tiendas sin duplicar', function () {
    $this->seed(ReservasDemoSeeder::class);

    // Configuración: 各店舗設定 de las dos tiendas, menús 全店舗, 施術 y 指名許諾 (no del master), 設備
    expect(DB::table('tb_yoyaku_config')->whereIn('tb_shop_cd', [1, 2])->count())->toBe(2)
        ->and(DB::table('tb_yoyaku_koumoku_master')->where('tb_shop_cd', -1)->count())->toBe(12)
        ->and(DB::table('tb_staff_works')->where('tb_staff_cd', 10)->count())->toBe(12)
        ->and(DB::table('tb_staff_works')->where('tb_staff_cd', 21)->count())->toBe(12)
        ->and(DB::table('tb_staff_works')->where('tb_staff_cd', 1)->count())->toBe(0)
        ->and(DB::table('tb_staff')->whereIn('cd', [10, 20, 21])->pluck('reserve_flg')->unique()->all())->toBe(['1'])
        ->and(DB::table('tb_setsubi')->where('tb_shop_cd', 2)->count())->toBe(2);

    // Franjas: 60 días atrás + 14 adelante, sin martes salvo hoy
    $slots = DB::table('tb_yoyaku_uketsuke_day')->where('tb_staff_cd', 10)->pluck('moushikomi_date');
    expect($slots->count())->toBeGreaterThan(60)
        ->and($slots->contains('2026/12/01'))->toBeFalse()  // martes
        ->and($slots->contains('2026/12/10'))->toBeTrue();

    $rows = reservasDemoRows();
    $future = $rows->filter(fn ($r) => $r->moushikomi_date >= '2026/11/27' && $r->moushikomi_date <= '2026/12/10');
    $past = $rows->filter(fn ($r) => $r->moushikomi_date < '2026/11/27');
    expect($future->count())->toBeGreaterThanOrEqual(ReservasDemoSeeder::FUTURE_COUNT)
        ->and($past->count())->toBeGreaterThanOrEqual(ReservasDemoSeeder::PAST_COUNT)
        ->and($rows->where('mail', ReservasDemoSeeder::MARKER_MAIL))->toHaveCount(1)
        ->and($rows->filter(fn ($r) => (int) $r->tb_customer_cd > 0)->count())->toBeGreaterThan(50);

    // Cada tienda: reservas de hoy (terminada, en curso, por venir, cancelada), Web 100, フリー 101, クイック 201, pasada cancelada
    $nowSec = 15 * 3600;
    foreach ([1, 2] as $shop) {
        $ofShop = $rows->where('shop_cd', $shop);
        $today = $ofShop->where('moushikomi_date', '2026/11/27');
        $active = $today->where('disp_flg', '1');
        expect($active->filter(fn ($r) => $r->end_time <= $nowSec)->count())->toBeGreaterThan(0)
            ->and($active->filter(fn ($r) => $r->start_time < $nowSec && $r->end_time > $nowSec)->count())->toBeGreaterThan(0)
            ->and($active->filter(fn ($r) => $r->start_time >= $nowSec)->count())->toBeGreaterThan(2)
            ->and($today->where('disp_flg', '9')->count())->toBeGreaterThan(0)
            ->and($ofShop->pluck('entry_type')->unique()->all())->toContain('', '100', '101', '201')
            ->and($ofShop->filter(fn ($r) => $r->moushikomi_date < '2026/11/27' && $r->disp_flg === '9')->count())->toBeGreaterThan(0)
            ->and($ofShop->filter(fn ($r) => $r->moushikomi_date > '2026/11/27' && in_array($r->entry_type, ['100', '201'], true))->count())->toBeGreaterThan(1);

        // Nombre de 50 caracteres con kana de ancho medio, comentario largo y vacío
        expect($ofShop->filter(fn ($r) => mb_strlen((string) $r->name) === 50 && preg_match('/[\x{FF66}-\x{FF9F}]/u', (string) $r->name_kana))->count())->toBe(1)
            ->and($ofShop->filter(fn ($r) => mb_strlen((string) $r->comment) >= 900 && str_contains($r->comment, "\n"))->count())->toBeGreaterThan(0)
            ->and($ofShop->where('comment', '')->count())->toBeGreaterThan(0);

        // Solo la ダブルブッキング se solapa: exactamente un par por tienda
        $overlaps = 0;
        foreach ($ofShop->where('disp_flg', '1')->groupBy('tb_yoyaku_uketsuke_day_cd') as $list) {
            $sorted = $list->sortBy('start_time')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                if ((int) $sorted[$i]->start_time < (int) $sorted[$i - 1]->end_time) {
                    $overlaps++;
                    expect($sorted[$i]->comment)->toBe(ReservasDemoSeeder::DOUBLE_BOOKING)
                        ->and($sorted[$i - 1]->comment)->toBe(ReservasDemoSeeder::DOUBLE_BOOKING);
                }
            }
        }
        expect($overlaps)->toBe(1);
    }

    // Segunda ejecución: no cambia nada
    $before = reservasDemoCounts();
    $this->seed(ReservasDemoSeeder::class);
    expect(reservasDemoCounts())->toBe($before);
});

it('el 予約表 de hoy pinta en cada tienda la ダブルブッキング, el nombre largo y el comentario largo', function () {
    $this->seed(ReservasDemoSeeder::class);
    $rows = reservasDemoRows()->where('moushikomi_date', '2026/11/27')->where('disp_flg', '1');

    foreach ([1, 2] as $shop) {
        $ofShop = $rows->where('shop_cd', $shop);
        $double = $ofShop->where('comment', ReservasDemoSeeder::DOUBLE_BOOKING);
        $names = DB::table('tb_customer')->whereIn('cd', $double->pluck('tb_customer_cd'))->pluck('name');
        $longName = $ofShop->first(fn ($r) => mb_strlen((string) $r->name) === 50)->name;

        $response = $this->get(route('admin.reservas.timetable', ['shop' => $shop, 'd' => '20261127']))
            ->assertOk()
            ->assertSee('kokyaku="' . e($longName) . '"', false)
            ->assertSee('&amp;lt;b&amp;gt;薬剤&amp;lt;/b&amp;gt;', false) // comentario largo: escapado dos veces (el JS lo mete con .html())
            ->assertDontSee('<b>薬剤</b>', false);
        expect($double)->toHaveCount(2);
        foreach ($names as $name) {
            $response->assertSee('kokyaku="' . e($name) . '"', false);
        }
    }
});

it('la reserva web r/ ofrece al personal y los menús sembrados en cada tienda', function () {
    $this->seed(ReservasDemoSeeder::class);

    $web = app(\App\Services\Reservations\WebReservationService::class);
    expect($web->staffMenu(1))->toHaveKey(10)
        ->and(collect($web->staffMenu(1)[10])->pluck('title'))->toContain('カット', 'ヘッドスパ')
        ->and(array_keys($web->staffMenu(2)))->toEqualCanonicalizing([20, 21]);
});

it('sembrando de madrugada reparte las reservas de hoy alrededor de media jornada', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 27, 4, 20, 0, 'Asia/Tokyo'));
    $this->seed(ReservasDemoSeeder::class);

    $today = reservasDemoRows()->where('moushikomi_date', '2026/11/27')->where('disp_flg', '1')->where('shop_cd', 2);
    expect($today->filter(fn ($r) => $r->end_time <= 13 * 3600)->count())->toBeGreaterThan(0)
        ->and($today->filter(fn ($r) => $r->start_time >= 14 * 3600)->count())->toBeGreaterThan(1);
});
