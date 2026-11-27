<?php

use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\OperacionesDemoSeeder;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// Datos de demostración de 業務支援管理 (CONVENCIONES §4): personal, 営業日, turnos, 勤怠 con casos límite,
// 各種設定, インフォメーション y passcode de la tienda móvil

const OPERACIONES_DEMO_TABLES = [
    'tb_staff', 'tb_belongpost', 'tb_card', 'tb_information', 'tb_shop_work', 'tb_staff_shift', 'tb_staff_shift_kind',
    'tb_work_time', 'kintai_tb_hiduke', 'kintai_tb_kintai', 'kintai_tb_config', 'contents_master',
];

function operacionesDemoCounts(): array
{
    return collect(OPERACIONES_DEMO_TABLES)->mapWithKeys(fn (string $t) => [$t => DB::table($t)->count()])->all();
}

/** Epoch JST */
function demoTs(string $datetime): int
{
    return CarbonImmutable::parse($datetime, 'Asia/Tokyo')->getTimestamp();
}

/** Como deja la BD NucleoDemoSeeder: dos tiendas, staff demo (correo @example.com) y uno borrado */
function seedOperacionesDemoBase(): void
{
    seedOperaciones();
    DB::table('tb_base_system_child')->insert([
        ['cd' => 51, 'tb_base_system_cd' => 5, 'system_name' => '勤怠管理', 'system_flg' => '1', 'rank' => 2],
        ['cd' => 52, 'tb_base_system_cd' => 5, 'system_name' => '初期設定', 'system_flg' => '0', 'rank' => 3],
    ]);
    DB::table('tb_shop')->where('cd', 1)->update(['start_work_time' => '9<>00', 'break_time' => '60', 'kyuyo_marume' => '15']);
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    // Fila de 各種設定 con el horario por defecto del legacy (como las del backup) y otra configurada a mano
    DB::table('kintai_tb_config')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'kijun_time' => '05<>00', 'start_time' => '09<>00', 'end_time' => '20<>00', 'password' => '9999'],
        ['cd' => 2, 'tb_shop_cd' => 2, 'kijun_time' => '05<>00', 'start_time' => '08<>00', 'end_time' => '10<>00', 'password' => '1234'],
    ]);
    DB::table('tb_koumoku')->insert(['cd' => 46, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'name_kana', 'tb_koumoku_name' => '名前(カナ)', 'mail_mag_select' => '']);
    DB::table('tb_staff')->insert([
        ['cd' => 10, 'shop_cd' => 1, 'login_id' => 'tencho_t', 'email1' => 'tencho_t@example.com', 'name' => '山田', 'id' => 'E10', 'felica_no' => '', 'employmentsystem' => '正社員', 'rank' => 1, 'disp_flg' => '1'],
        ['cd' => 11, 'shop_cd' => 1, 'login_id' => 'staff11', 'email1' => 'staff11@example.com', 'name' => '佐藤', 'id' => 'E11', 'felica_no' => '', 'employmentsystem' => 'アルバイト', 'rank' => 2, 'disp_flg' => '1'],
        ['cd' => 12, 'shop_cd' => 2, 'login_id' => 'staff12', 'email1' => 'staff12@example.com', 'name' => '支店員', 'id' => 'E12', 'felica_no' => '', 'employmentsystem' => '', 'rank' => 1, 'disp_flg' => '1'],
        ['cd' => 13, 'shop_cd' => 1, 'login_id' => 'staff13', 'email1' => 'staff13@example.com', 'name' => '退職者', 'id' => 'E13', 'felica_no' => '', 'employmentsystem' => '', 'rank' => 3, 'disp_flg' => '9'],
        ['cd' => 14, 'shop_cd' => 2, 'login_id' => 'staff14', 'email1' => 'staff14@example.com', 'name' => '締め担当', 'id' => 'E14', 'felica_no' => '', 'employmentsystem' => 'パート', 'rank' => 2, 'disp_flg' => '1'],
    ]);
}

it('siembra personal, 営業日, turnos y 勤怠 con casos límite, y una segunda ejecución no duplica', function () {
    seedOperacionesDemoBase();
    $this->travelTo(CarbonImmutable::parse('2026-11-27 10:00', 'Asia/Tokyo'));

    $this->seed(OperacionesDemoSeeder::class);
    $first = operacionesDemoCounts();

    // Personal: ficha del staff demo rellenada sin pisar lo que ya tenía; ni staff nuevo ni el master
    $yamada = DB::table('tb_staff')->where('cd', 10)->first();
    expect($first['tb_staff'])->toBe(6)
        ->and($yamada->belongpost)->toBe('本部') // tencho_ → 本部 / 主任
        ->and($yamada->officialposition)->toBe('主任')
        ->and($yamada->employmentsystem)->toBe('正社員')
        ->and(mb_strlen($yamada->remarks))->toBe(255)
        ->and(DB::table('tb_staff')->where('cd', 1)->value('belongpost'))->toBeIn(['', null]) // el master no
        ->and($first['tb_belongpost'])->toBe(4)
        ->and(DB::table('tb_koumoku')->where('cd', 46)->value('mail_mag_select'))->toBe('1');

    // Tarjetas: 3 asignadas (felica_no en la ficha) y 2 libres
    expect($first['tb_card'])->toBe(5)
        ->and(DB::table('tb_card')->where('card_flg', '1')->count())->toBe(3)
        ->and($yamada->felica_no)->toBe('0114DE0000000001')
        ->and(DB::table('tb_card')->where('felica_no', '0114DE0000000001')->value('staff_cd'))->toBe('10');

    // インフォメーション (12, por tienda y por cargo) y passcode de la tienda móvil por tienda
    expect($first['tb_information'])->toBe(12)
        ->and(DB::table('tb_information')->where('shop_cd', 2)->count())->toBeGreaterThan(0)
        ->and(DB::table('tb_information')->where('officialposition', '主任 部長')->count())->toBe(1)
        ->and(DB::table('contents_master')->where('field', 'mobileshop_auth_passcode')->orderBy('int_data')->pluck('text_data')->all())->toBe(['4321', '04321']);

    // 営業日設定: dos tiendas × noviembre y diciembre; 水曜 (4日) y 祝日 (3日 文化の日, 23日) cerrados
    $nov = DB::table('tb_shop_work')->where('shop_cd', 1)->where('syukkinbi', '2026/11')->first();
    expect($first['tb_shop_work'])->toBe(4)
        ->and([$nov->syukkin_time_3, $nov->syukkin_time_4, $nov->syukkin_time_5, $nov->syukkin_time_23])->toBe(['0', '0', '1', '0'])
        ->and(DB::table('kintai_tb_config')->where('tb_shop_cd', 2)->first(['start_time', 'end_time', 'password']))->toEqual((object) ['start_time' => '08<>00', 'end_time' => '02<>00', 'password' => '1234']) // horario por defecto → el de la demo
        ->and(DB::table('kintai_tb_config')->where('tb_shop_cd', 1)->value('end_time'))->toBe('20<>00') // configurado: no se toca
        ->and(DB::table('tb_base_system_child')->where('cd', 52)->value('system_flg'))->toBe('1');

    // Turnos de los 4 activos (sin el master ni el de baja) en los dos meses; 締め (el 4.º) cruza la medianoche;
    // días con dos turnos (hueco o solape); nunca en 水曜 salvo que se haya trabajado
    $shifts = DB::table('tb_staff_shift')->get();
    $novEpoch = LegacyMonth::of(2026, 11)->epoch();
    $lateNov = $shifts->where('staff_cd', 14)->firstWhere('syukkinbi', $novEpoch);
    $crossesMidnight = collect(range(1, 30))->contains(fn (int $d) => (string) $lateNov->{"gaisyutu_time_{$d}"} !== ''
        && CarbonImmutable::createFromTimestamp((int) $lateNov->{"kisha_time_{$d}"}, 'Asia/Tokyo')->day !== $d);
    $double = $shifts->contains(fn ($row) => collect(range(1, 28))->contains(fn (int $d) => str_contains((string) $row->{"gaisyutu_time_{$d}"}, '<>')));
    expect($shifts)->toHaveCount(8)
        ->and($shifts->pluck('staff_cd')->unique()->sort()->values()->all())->toBe([10, 11, 12, 14])
        ->and($shifts->firstWhere('staff_cd', 10)->gaisyutu_time_4)->toBeIn(['', null])
        ->and($crossesMidnight)->toBeTrue()
        ->and($double)->toBeTrue()
        ->and($first['tb_staff_shift_kind'])->toBe(3);

    // 勤怠 de 92 días: banderas del legacy, salidas sin fichar, 休日出勤
    $work = DB::table('tb_work_time')->where('staff_cd', 10)->where('syukkinbi', $novEpoch)->first();
    $days = collect(range(1, 26))->filter(fn (int $d) => (string) $work->{"syukkin_time_{$d}"} !== '');
    $d = $days->first(fn (int $d) => (string) $work->{"taikin_time_{$d}"} !== '');
    expect($days)->not->toBeEmpty()
        ->and($work->{"syukkin_flg_{$d}"})->toBeIn(['出勤', '遅刻', '早出'])
        ->and($work->{"syukkin_kubun_{$d}"})->toBeIn(['退社', '残業', '早退'])
        ->and((int) $work->{"taikin_time_{$d}"})->toBeGreaterThan((int) $work->{"syukkin_time_{$d}"})
        ->and(DB::table('tb_work_time')->whereIn('staff_cd', [1, 13])->count())->toBe(0)
        ->and(DB::table('tb_work_time')->where('syukkinbi', LegacyMonth::of(2026, 8)->epoch())->count())->toBeGreaterThan(0)
        ->and(DB::table('kintai_tb_hiduke')->where('bikou', 'like', '休日出勤%')->count())->toBeGreaterThan(0)
        ->and(DB::table('kintai_tb_hiduke')->whereIn('kintai_flg', ['欠勤', '有給休暇'])->count())->toBeGreaterThan(0);
    $noClockOut = DB::table('kintai_tb_hiduke as h')->where('h.kintai_flg', '!=', '欠勤')->where('h.kintai_flg', '!=', '有給休暇')
        ->where('h.hiduke', '<', (string) demoTs('2026-11-27 05:00'))
        ->whereNotExists(fn ($q) => $q->from('kintai_tb_kintai as k')->whereColumn('k.kintai_tb_hiduke_cd', 'h.cd')->where('k.kintai_flg', '2'))
        ->count();
    expect($noClockOut)->toBeGreaterThan(0);

    // Fin de año: 31/12 con 残業 hasta el 1/1 (en la fila de diciembre de 2025) y el 1/1 (祝日) trabajado
    $dec = DB::table('tb_work_time')->where('staff_cd', 10)->where('syukkinbi', LegacyMonth::of(2025, 12)->epoch())->first();
    expect((int) $dec->syukkin_time_31)->toBeLessThan(demoTs('2025-12-31 17:00'))
        ->and((int) $dec->taikin_time_31)->toBeGreaterThan(demoTs('2026-01-01 00:00'))
        ->and(DB::table('tb_work_time')->where('staff_cd', 10)->where('syukkinbi', LegacyMonth::of(2026, 1)->epoch())->value('syukkin_time_1'))->not->toBeNull()
        ->and(DB::table('kintai_tb_hiduke')->where('tb_staff_cd', 11)->where('hiduke', '>=', (string) demoTs('2025-12-31 05:00'))
            ->where('hiduke', '<', (string) demoTs('2026-01-01 05:00'))->count())->toBe(1);

    // Hoy (10:00): solo jornadas en curso, sin salida
    $today = DB::table('kintai_tb_hiduke')->where('hiduke', '>=', (string) demoTs('2026-11-27 05:00'))->pluck('cd');
    expect(DB::table('kintai_tb_kintai')->whereIn('kintai_tb_hiduke_cd', $today)->where('kintai_flg', '!=', '1')->count())->toBe(0);

    // Las pantallas los muestran
    loginAs('master', 'secreto1');
    $hiduke = DB::table('kintai_tb_hiduke')->where('tb_staff_cd', 10)->where('kintai_flg', '出勤')->orderBy('cd')->first();
    $date = CarbonImmutable::createFromTimestamp((int) $hiduke->hiduke, 'Asia/Tokyo');
    $this->get('/admin/operaciones/kintai?'.http_build_query(['yy' => $date->format('Y'), 'mm' => $date->format('m'), 'dd' => $date->format('d'), 'shop' => 1]))
        ->assertOk()->assertSee('山田');
    $this->get('/admin/operaciones/shifts/month')->assertOk();
    $this->get('/admin/operaciones/shifts/day?shop_cd=1')->assertOk();
    $this->get('/admin/operaciones/working/totals')->assertOk()->assertSee('佐藤');
    $this->get('/admin/operaciones/working/calendar?year=2026&mon=11')->assertOk()->assertSee('営業日数：24／30');
    $this->get('/admin/operaciones/staff/10?tab=2')->assertOk()->assertSee('0114DE0000000001')->assertSee('関連付けを解除する');
    $this->get('/admin/operaciones/information')->assertOk()->assertSee('来月分の希望シフト');

    // Segunda ejecución: nada nuevo
    $this->seed(OperacionesDemoSeeder::class);
    expect(operacionesDemoCounts())->toBe($first);
});

it('funciona solo con la tienda y el staff del backup', function () {
    seedOperaciones();
    $this->travelTo(CarbonImmutable::parse('2026-11-27 10:00', 'Asia/Tokyo'));

    $this->seed(OperacionesDemoSeeder::class);

    // Solo el master: él recibe turnos y 勤怠; sin staff demo no se rellena ninguna ficha ni se asignan tarjetas
    expect(DB::table('tb_staff_shift')->where('staff_cd', 1)->count())->toBe(2)
        ->and(DB::table('kintai_tb_hiduke')->where('tb_staff_cd', 1)->count())->toBeGreaterThan(0)
        ->and(DB::table('tb_staff')->count())->toBe(1)
        ->and(DB::table('tb_card')->where('card_flg', '1')->count())->toBe(0)
        ->and(DB::table('tb_card')->count())->toBe(5);
});
