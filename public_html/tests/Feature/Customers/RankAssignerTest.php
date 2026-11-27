<?php

require_once __DIR__ . '/../Nucleo/helpers.php';

use App\Console\Commands\Clientes\AssignRanks;
use App\Models\Customers\Customer;
use App\Models\Promotions\KingakuRankMaster;
use App\Models\Promotions\RaitenRankMaster;
use App\Services\Customers\RankAssigner;
use App\Services\Customers\RankScheduleException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Reparto de rangos (rank/setCustomerKingakuRank.php, setCustomerRaitenRank.php) → clientes:asignar-rangos

beforeEach(function () {
    seedNucleo();
    Carbon::setTestNow(Carbon::parse('2026-11-30 03:15:00', 'Asia/Tokyo')); // lunes, último día del mes
});

afterEach(function () {
    Carbon::setTestNow();
});

/** admin_master.<tipo>_rank_shori_* (+ related_features ポイント) */
function clientesConfigRangos(string $type, string $date, string $time = '03:15', string $kikan = '30'): void
{
    DB::table('admin_master')->where('cd', 1)->update([
        "{$type}_rank_shori_date" => $date,
        "{$type}_rank_shori_time" => $time,
        "{$type}_rank_shori_kikan" => $kikan,
        'related_features' => '<>1',
    ]);
}

function clientesPuntos(int $customerCd, int $kaikei, string $when = '2026-11-20 12:00', ?string $disp = '1', string $table = 'tb_point_log'): void
{
    static $cd = 1;
    DB::table($table)->insert([
        'cd' => $cd++, 'tb_customer_cd' => $customerCd, 'kaikei' => $kaikei,
        'touroku_date' => Carbon::parse($when, 'Asia/Tokyo')->getTimestamp(), 'disp_flg' => $disp,
    ]);
}

it('decide el día como el legacy: meses × días con 32 = 月末 y días de la semana', function () {
    $assigner = app(RankAssigner::class);
    $today = now();

    clientesConfigRangos('kingaku', '1<>11,12<>15,32');
    expect($assigner->isDueOn('kingaku', $today))->toBeTrue();
    clientesConfigRangos('kingaku', '1<>11<>29,31');
    expect($assigner->isDueOn('kingaku', $today))->toBeFalse();
    clientesConfigRangos('kingaku', '1<>10,12<>30');
    expect($assigner->isDueOn('kingaku', $today))->toBeFalse();
    clientesConfigRangos('kingaku', '2<>1,3'); // lunes
    expect($assigner->isDueOn('kingaku', $today))->toBeTrue();
    clientesConfigRangos('kingaku', '2<>0,6');
    expect($assigner->isDueOn('kingaku', $today))->toBeFalse();

    clientesConfigRangos('kingaku', '');
    expect(fn () => $assigner->isDueOn('kingaku', $today))->toThrow(RankScheduleException::class);
});

it('asigna rangos por importe: suma de kaikei del periodo, jyouken1 <= x < jyouken2, el de arriba gana', function () {
    clientesConfigRangos('kingaku', '2<>1', '06:00', '30');
    $gold = KingakuRankMaster::create(['name' => 'ゴールド', 'jyouken1' => 50000, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
    $silver = KingakuRankMaster::create(['name' => 'シルバー', 'jyouken1' => 10000, 'jyouken2' => 50000, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 2]);
    // Oculto (hyouji_flg = 9): no se usa
    KingakuRankMaster::create(['name' => '非表示', 'jyouken1' => 1, 'disp_flg' => '1', 'hyouji_flg' => '9', 'rank' => 3]);

    $rich = Customer::factory()->create(['kingaku_rank_now' => 7]);
    $mid = Customer::factory()->create();
    $poor = Customer::factory()->create(['kingaku_rank_now' => 5]);
    $old = Customer::factory()->create();

    clientesPuntos($rich->cd, 30000);
    clientesPuntos($rich->cd, 25000);
    clientesPuntos($mid->cd, 12000);
    clientesPuntos($mid->cd, 90000, disp: '9');             // borrado: no cuenta
    clientesPuntos($poor->cd, 500);
    clientesPuntos($old->cd, 80000, '2026-10-01 10:00');     // fuera de los 30 días

    $result = app(RankAssigner::class)->assign('kingaku', now());

    expect(collect($result)->pluck('name')->all())->toBe(['シルバー', 'ゴールド']);
    expect($rich->fresh()->kingaku_rank_now)->toBe($gold->cd)
        ->and($rich->fresh()->kingaku_rank_pre)->toBe(7)
        ->and($mid->fresh()->kingaku_rank_now)->toBe($silver->cd)
        // Sin rango que cumpla: conserva el que tenía (el legacy no lo limpia)
        ->and($poor->fresh()->kingaku_rank_now)->toBe(5)
        ->and($poor->fresh()->kingaku_rank_pre)->toBe(5)
        ->and($old->fresh()->kingaku_rank_now)->toBeNull();
});

it('rango solo con jyouken2: los que no llegan, también sin registros; tb_stamp_log en UNION', function () {
    clientesConfigRangos('kingaku', '2<>1', '06:00', '30');
    $low = KingakuRankMaster::create(['name' => 'ビギナー', 'jyouken2' => 1000, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);

    $none = Customer::factory()->create();
    $big = Customer::factory()->create();
    $stamp = Customer::factory()->create();
    clientesPuntos($big->cd, 5000);
    clientesPuntos($stamp->cd, 2000, table: 'tb_stamp_log');

    app(RankAssigner::class)->assign('kingaku', now());

    expect($none->fresh()->kingaku_rank_now)->toBe($low->cd)
        ->and($big->fresh()->kingaku_rank_now)->toBeNull()
        ->and($stamp->fresh()->kingaku_rank_now)->toBeNull();
});

it('el periodo suma un día si la hora es antes de las 5:00', function () {
    // 2026-11-30 - (2 + 1) días = desde el 27/11 00:00
    clientesConfigRangos('kingaku', '2<>1', '03:15', '2');
    $rank = KingakuRankMaster::create(['name' => 'A', 'jyouken1' => 100, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
    $in = Customer::factory()->create();
    $out = Customer::factory()->create();
    clientesPuntos($in->cd, 100, '2026-11-27 00:30');
    clientesPuntos($out->cd, 100, '2026-11-26 23:30');

    app(RankAssigner::class)->assign('kingaku', now());

    expect($in->fresh()->kingaku_rank_now)->toBe($rank->cd)->and($out->fresh()->kingaku_rank_now)->toBeNull();
});

it('kingaku exige related_features como el legacy', function () {
    clientesConfigRangos('kingaku', '2<>1');
    DB::table('admin_master')->where('cd', 1)->update(['related_features' => '']);

    expect(fn () => app(RankAssigner::class)->assign('kingaku', now()))->toThrow(RankScheduleException::class);
});

it('asigna rangos por nº de visitas de tb_raiten_rireki', function () {
    clientesConfigRangos('raiten', '2<>1', '06:00', '10');
    $rank = RaitenRankMaster::create(['name' => '常連', 'jyouken1' => 2, 'jyouken2' => 5, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
    $regular = Customer::factory()->create(['raiten_rank_now' => 3]);
    $once = Customer::factory()->create();

    $visit = fn (int $user, string $day, string $disp = '1') => DB::table('tb_raiten_rireki')->insert([
        'cd' => random_int(1, 999999999), 'user_cd' => $user, 'raiten_hiduke' => $day, 'disp_flg' => $disp,
    ]);
    $visit($regular->cd, '2026/11/25');
    $visit($regular->cd, '2026/11/28');
    $visit($regular->cd, '2026/11/10');   // fuera de los 10 días
    $visit($once->cd, '2026/11/29');
    $visit($once->cd, '2026/11/29', '9'); // borrada

    app(RankAssigner::class)->assign('raiten', now());

    expect($regular->fresh()->raiten_rank_now)->toBe($rank->cd)
        ->and($regular->fresh()->raiten_rank_pre)->toBe(3)
        ->and($once->fresh()->raiten_rank_now)->toBeNull();
});

it('el comando solo reparte el día configurado', function () {
    $rank = KingakuRankMaster::create(['name' => 'A', 'jyouken1' => 100, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);
    $customer = Customer::factory()->create();
    clientesPuntos($customer->cd, 500);

    clientesConfigRangos('kingaku', '2<>2'); // martes: hoy no
    $this->artisan('clientes:asignar-rangos', ['--tipo' => 'kingaku'])->assertSuccessful();
    expect($customer->fresh()->kingaku_rank_now)->toBeNull();

    clientesConfigRangos('kingaku', '1<>11<>32'); // 月末
    $this->artisan('clientes:asignar-rangos', ['--tipo' => 'kingaku'])
        ->expectsOutputToContain('clientes: 1')
        ->assertSuccessful();
    expect($customer->fresh()->kingaku_rank_now)->toBe($rank->cd);

    $this->artisan('clientes:asignar-rangos', ['--tipo' => 'otro'])->assertFailed();
});

it('programa la tarea a la hora de admin_master para cada tipo', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains((string) $e->command, 'clientes:asignar-rangos'));
    expect($events)->toHaveCount(2);

    $kingaku = $events->first(fn ($e) => str_contains((string) $e->command, 'kingaku'));
    $raiten = $events->first(fn ($e) => str_contains((string) $e->command, 'raiten'));

    clientesConfigRangos('kingaku', '2<>1', '03:15');
    clientesConfigRangos('raiten', '', '');
    expect($kingaku->filtersPass(app()))->toBeTrue()
        ->and($raiten->filtersPass(app()))->toBeFalse();

    clientesConfigRangos('kingaku', '2<>1', '03:20');
    expect($kingaku->filtersPass(app()))->toBeFalse();
});
