<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Database\Seeders\Demo\Clientes\ReservasDemoSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Datos de demostración de 予約管理 (S2, parte D3): configuración, franjas y reservas

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 27, 9, 0, 0, 'Asia/Tokyo'));
    reservasLogin(); // tienda 1, master (cd 1) y 山田 (cd 10)

    foreach (range(1, 6) as $n) {
        Customer::factory()->create(['email1' => "demo{$n}@example.com", 'tb_shop_cd' => 1]);
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

it('siembra configuración y reservas de las próximas 2 semanas y del pasado sin duplicar', function () {
    $this->seed(ReservasDemoSeeder::class);

    // Configuración: 各店舗設定, menús 全店舗, 施術 y 指名許諾 de 山田 (no del master), 設備
    expect(DB::table('tb_yoyaku_config')->where('tb_shop_cd', 1)->value('start_time'))->toBe('10:00')
        ->and(DB::table('tb_yoyaku_koumoku_master')->where('tb_shop_cd', -1)->count())->toBe(12)
        ->and(DB::table('tb_staff_works')->where('tb_staff_cd', 10)->count())->toBe(12)
        ->and(DB::table('tb_staff_works')->where('tb_staff_cd', 1)->count())->toBe(0)
        ->and(DB::table('tb_staff')->where('cd', 10)->value('reserve_flg'))->toBe('1')
        ->and(DB::table('tb_setsubi')->where('tb_shop_cd', 1)->count())->toBe(2);

    // Franjas: 60 días atrás + 14 adelante, sin martes
    $slots = DB::table('tb_yoyaku_uketsuke_day')->where('tb_staff_cd', 10)->pluck('moushikomi_date');
    expect($slots->count())->toBeGreaterThan(60)
        ->and($slots->contains('2026/12/01'))->toBeFalse()  // martes
        ->and($slots->contains('2026/12/10'))->toBeTrue();

    $rows = DB::table('tb_yoyaku as y')->join('tb_yoyaku_uketsuke_day as d', 'd.cd', '=', 'y.tb_yoyaku_uketsuke_day_cd')
        ->get(['y.*', 'd.moushikomi_date']);
    $future = $rows->filter(fn ($r) => $r->moushikomi_date >= '2026/11/27' && $r->moushikomi_date <= '2026/12/10');
    $past = $rows->filter(fn ($r) => $r->moushikomi_date < '2026/11/27');
    expect($future)->toHaveCount(ReservasDemoSeeder::FUTURE_COUNT)
        ->and($past)->toHaveCount(ReservasDemoSeeder::PAST_COUNT)
        ->and($rows->where('mail', ReservasDemoSeeder::MARKER_MAIL))->toHaveCount(1)
        ->and($rows->where('disp_flg', '9')->count())->toBeGreaterThan(0)
        ->and($rows->pluck('entry_type')->unique()->sort()->values()->all())->toContain('', '100')
        ->and($rows->filter(fn ($r) => (int) $r->tb_customer_cd > 0)->count())->toBeGreaterThan(50);

    // Ninguna reserva vigente se solapa con otra de la misma franja
    foreach ($rows->where('disp_flg', '1')->groupBy('tb_yoyaku_uketsuke_day_cd') as $list) {
        $sorted = $list->sortBy('start_time')->values();
        for ($i = 1; $i < $sorted->count(); $i++) {
            expect((int) $sorted[$i]->start_time)->toBeGreaterThanOrEqual((int) $sorted[$i - 1]->end_time);
        }
    }

    // Segunda ejecución: no cambia nada
    $before = reservasDemoCounts();
    $this->seed(ReservasDemoSeeder::class);
    expect(reservasDemoCounts())->toBe($before);
});

it('el 予約表 muestra las reservas sembradas', function () {
    $this->seed(ReservasDemoSeeder::class);

    $row = DB::table('tb_yoyaku as y')->join('tb_yoyaku_uketsuke_day as d', 'd.cd', '=', 'y.tb_yoyaku_uketsuke_day_cd')
        ->join('tb_customer as c', 'c.cd', '=', 'y.tb_customer_cd')
        ->where('y.disp_flg', '1')->where('d.moushikomi_date', '>=', '2026/11/27')
        ->orderBy('d.moushikomi_date')->first(['c.name', 'd.moushikomi_date']);

    $this->get(route('admin.reservas.timetable', ['shop' => 1, 'd' => str_replace('/', '', $row->moushikomi_date)]))
        ->assertOk()
        ->assertSee('kokyaku="' . e($row->name) . '"', false);
});

it('la reserva web r/ ofrece al personal y los menús sembrados', function () {
    $this->seed(ReservasDemoSeeder::class);

    $menus = app(\App\Services\Reservations\WebReservationService::class)->staffMenu(1);
    expect($menus)->toHaveKey(10)
        ->and(collect($menus[10])->pluck('title'))->toContain('カット', 'ヘッドスパ');
});
