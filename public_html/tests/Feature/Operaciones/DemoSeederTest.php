<?php

use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\OperacionesDemoSeeder;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// Datos de demostración de 業務支援管理 (CONVENCIONES §4): 営業日, turnos, 勤怠, 各種設定, インフォメーション

const OPERACIONES_DEMO_TABLES = ['tb_shop_work', 'tb_staff_shift', 'tb_staff_shift_kind', 'tb_work_time', 'kintai_tb_hiduke', 'kintai_tb_kintai', 'kintai_tb_config', 'tb_information'];

function operacionesDemoCounts(): array
{
    return collect(OPERACIONES_DEMO_TABLES)->mapWithKeys(fn (string $t) => [$t => DB::table($t)->count()])->all();
}

it('siembra 営業日, turnos y 勤怠 del staff activo y una segunda ejecución no duplica', function () {
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 52, 'tb_base_system_cd' => 5, 'system_name' => '初期設定', 'system_flg' => '0', 'rank' => 3]);
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    DB::table('tb_staff')->insert([
        ['cd' => 10, 'shop_cd' => 1, 'name' => '山田', 'id' => 'E10', 'felica_no' => '', 'rank' => 1, 'disp_flg' => '1'],
        ['cd' => 11, 'shop_cd' => 1, 'name' => '佐藤', 'id' => 'E11', 'felica_no' => '', 'rank' => 2, 'disp_flg' => '1'],
        ['cd' => 12, 'shop_cd' => 2, 'name' => '支店員', 'id' => 'E12', 'felica_no' => '', 'rank' => 1, 'disp_flg' => '1'],
        ['cd' => 13, 'shop_cd' => 1, 'name' => '退職者', 'id' => 'E13', 'felica_no' => '', 'rank' => 3, 'disp_flg' => '9'],
    ]);
    $this->travelTo(CarbonImmutable::parse('2026-11-27 10:00', 'Asia/Tokyo'));

    $this->seed(OperacionesDemoSeeder::class);
    $first = operacionesDemoCounts();

    // 営業日設定: dos tiendas × noviembre y diciembre; 水曜 (4日) y 祝日 (3日 文化の日) cerrados
    $nov = DB::table('tb_shop_work')->where('shop_cd', 1)->where('syukkinbi', '2026/11')->first();
    expect($first['tb_shop_work'])->toBe(4)
        ->and($nov->syukkin_time_3)->toBe('0')
        ->and($nov->syukkin_time_4)->toBe('0')
        ->and($nov->syukkin_time_5)->toBe('1')
        ->and($nov->syukkin_time_23)->toBe('0');

    // Turnos de los tres activos (sin el master ni el borrado) en los dos meses, nunca en día cerrado
    $shifts = DB::table('tb_staff_shift')->get();
    expect($shifts)->toHaveCount(6)
        ->and($shifts->pluck('staff_cd')->unique()->sort()->values()->all())->toBe([10, 11, 12])
        ->and($shifts->where('staff_cd', 10)->firstWhere('syukkinbi', LegacyMonth::of(2026, 11)->epoch())->gaisyutu_time_4)->toBeIn(['', null])
        ->and($first['tb_staff_shift_kind'])->toBe(3);

    // 勤怠: タイムカード y terminal de los últimos 30 días, con horas y banderas del legacy
    $work = DB::table('tb_work_time')->where('staff_cd', 10)->where('syukkinbi', LegacyMonth::of(2026, 11)->epoch())->first();
    $days = collect(range(1, 26))->filter(fn (int $d) => (string) $work->{"syukkin_time_{$d}"} !== '');
    expect($days)->not->toBeEmpty()
        ->and(DB::table('tb_work_time')->whereIn('staff_cd', [1, 13])->count())->toBe(0)
        ->and($work->syukkin_time_27)->toBeNull(); // hoy no
    $d = $days->first();
    expect($work->{"syukkin_flg_{$d}"})->toBeIn(['出勤', '遅刻', '早出'])
        ->and($work->{"syukkin_kubun_{$d}"})->toBeIn(['退社', '残業', '早退'])
        ->and((int) $work->{"taikin_time_{$d}"})->toBeGreaterThan((int) $work->{"syukkin_time_{$d}"})
        ->and((int) $work->{"kisha_time_{$d}"})->toBeGreaterThan((int) $work->{"gaisyutu_time_{$d}"});

    $hiduke = DB::table('kintai_tb_hiduke')->where('tb_staff_cd', 10)->where('kintai_flg', '!=', '欠勤')->where('kintai_flg', '!=', '有給休暇')->first();
    expect($first['kintai_tb_hiduke'])->toBeGreaterThan(30)
        ->and(DB::table('kintai_tb_kintai')->where('kintai_tb_hiduke_cd', $hiduke->cd)->orderBy('kintai_time')->pluck('kintai_flg')->all())->toBe(['1', '3', '4', '2'])
        ->and(DB::table('kintai_tb_config')->where('tb_shop_cd', 2)->value('end_time'))->toBe('22<>00')
        ->and($first['tb_information'])->toBe(3)
        ->and(DB::table('tb_base_system_child')->where('cd', 52)->value('system_flg'))->toBe('1');

    // Las pantallas los muestran: lista del día del terminal
    loginAs('master', 'secreto1');
    $date = CarbonImmutable::createFromTimestamp((int) $hiduke->hiduke, 'Asia/Tokyo');
    $this->get('/admin/operaciones/kintai?'.http_build_query(['yy' => $date->format('Y'), 'mm' => $date->format('m'), 'dd' => $date->format('d'), 'shop' => 1]))
        ->assertOk()
        ->assertSee('山田');
    $this->get('/admin/operaciones/shifts/month')->assertOk();
    $this->get('/admin/operaciones/working/totals')->assertOk()->assertSee('佐藤');
    $this->get('/admin/operaciones/working/calendar?year=2026&mon=11')->assertOk()->assertSee('営業日数：24／30');

    // Segunda ejecución: nada nuevo
    $this->seed(OperacionesDemoSeeder::class);
    expect(operacionesDemoCounts())->toBe($first);
});
