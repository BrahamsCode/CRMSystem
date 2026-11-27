<?php

use App\Services\Operaciones\ShiftBoard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// スケジュール管理: 日別シフト管理 (schedule/staff_shift.php)

function seedShiftBoard(): void
{
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 53, 'tb_base_system_cd' => 5, 'system_name' => 'スケジュール管理', 'system_flg' => '1', 'rank' => 3]);
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    // 10:00 → 02:00 del día siguiente (termina antes de kijun 05:00)
    DB::table('kintai_tb_config')->insert(['cd' => 1, 'tb_shop_cd' => 2, 'kijun_time' => '05<>00', 'start_time' => '10<>00', 'end_time' => '02<>00']);
    DB::table('tb_staff')->insert([
        ['cd' => 20, 'shop_cd' => 2, 'name' => '田中', 'disp_flg' => '1', 'touroku_date' => '1700000000'],
        ['cd' => 21, 'shop_cd' => 2, 'name' => '鈴木', 'disp_flg' => '1', 'touroku_date' => '1700000100'],
        ['cd' => 22, 'shop_cd' => 2, 'name' => '退職', 'disp_flg' => '9', 'touroku_date' => '1700000200'],
    ]);
    DB::table('tb_staff_shift_kind')->insert(['cd' => 3, 'name' => 'ホール', 'color' => '#ff0000', 'rank' => 1, 'disp_flg' => '1']);
}

it('ShiftBoard: horario que cruza la medianoche en franjas de 15 min con horas 24+', function () {
    seedShiftBoard();
    $board = app(ShiftBoard::class);
    $hours = $board->hours(2);
    $slots = $board->slots(CarbonImmutable::create(2026, 3, 5, 0, 0, 0, 'Asia/Tokyo'), $hours);

    expect($hours)->toBe(['start' => [10, 0], 'minutes' => 960])
        ->and($slots)->toHaveCount(65)
        ->and($slots[0]['h'].':'.$slots[0]['m'])->toBe('10:00')
        ->and(end($slots)['h'].':'.end($slots)['m'])->toBe('26:00')
        // Sin kintai_tb_config: los valores por defecto del legacy (08:00 → 10:00)
        ->and($board->hours(1))->toBe(['start' => [8, 0], 'minutes' => 120]);
});

it('日別シフト管理: tablero de la tienda, guardado del día y lectura con horas 24+', function () {
    seedShiftBoard();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/shifts/day?shop_cd=2&d=20260305')
        ->assertOk()
        ->assertSee('<title>日別シフト管理 - shop</title>', false)
        ->assertSee('2026年03月05日 シフト一覧')
        ->assertSee('<option value="2" selected >支店</option>', false)
        ->assertSeeInOrder(['鈴木', '田中']) // touroku_date DESC
        ->assertDontSee('退職')
        ->assertSee('/system/category5/schedule/js/add.shift.js', false)
        ->assertSee('var staffKindAry = {"3":"\u30db', false) // @json escapa el unicode
        ->assertSee('日別シフト管理'); // menú

    $start = CarbonImmutable::create(2026, 3, 5, 23, 0, 0, 'Asia/Tokyo')->getTimestamp();
    $end = $start + 2 * 3600; // 01:00 del 6
    $this->post('/admin/operaciones/shifts/day', [
        'mode' => 'update', 'shop_cd' => '2', 'd' => '20260305',
        'sch' => ["20_{$start}_{$end}_3_遅番 & 締め"],
    ])->assertRedirect('/admin/operaciones/shifts/day?shop_cd=2&d=20260305');

    $row = DB::table('tb_staff_shift')->where('staff_cd', 20)->first();
    $month = CarbonImmutable::create(2026, 3, 1, 0, 0, 0, 'Asia/Tokyo')->getTimestamp();
    expect($row->shop_cd)->toBe(2)
        ->and((int) $row->syukkinbi)->toBe($month)
        ->and($row->gaisyutu_time_5)->toBe((string) $start)
        ->and($row->kisha_time_5)->toBe((string) $end)
        ->and($row->syukkin_kubun_5)->toBe('3')
        ->and($row->komment_5)->toBe('遅番 & 締め')
        // El otro empleado activo queda con el día vacío
        ->and(DB::table('tb_staff_shift')->where('staff_cd', 21)->value('gaisyutu_time_5'))->toBe('');

    $this->get('/admin/operaciones/shifts/day?shop_cd=2&d=20260305')
        ->assertSee('"start":"23:00","end":"25:00"', false)
        ->assertSee('name="sch[0]" class="sch0" value="20_'.$start.'_'.$end.'"', false);
});

it('日別シフト管理: quien no es master solo ve su tienda', function () {
    seedShiftBoard();
    staffUser(5, ['shop_cd' => 1]);
    loginAs('u5', 'p');

    $this->get('/admin/operaciones/shifts/day?shop_cd=2')
        ->assertOk()
        ->assertDontSee('店舗選択 :')
        ->assertDontSee('田中')
        ->assertSee('一般'); // él mismo, de la tienda 1
});

it('月別シフト管理: un empleado, una fila por día del mes, guardado de todo el mes', function () {
    seedShiftBoard();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/shifts/month?shop_cd=2&d=20260201')
        ->assertOk()
        ->assertSee('<title>月別シフト管理 - shop</title>', false)
        ->assertSee('鈴木 2026年02月 シフト一覧') // sin staff_cd: el primero (touroku_date DESC)
        ->assertSee('data-day="28"', false)
        ->assertDontSee('data-day="29"', false);

    $day3 = CarbonImmutable::create(2026, 2, 3, 12, 0, 0, 'Asia/Tokyo')->getTimestamp();
    $this->post('/admin/operaciones/shifts/month', [
        'mode' => 'update', 'shop_cd' => '2', 'staff_cd' => '20', 'd' => '20260201',
        'sch' => ['2_'.$day3.'_'.($day3 + 3600).'_3_', '2_'.($day3 + 7200).'_'.($day3 + 9000).'__'],
    ])->assertRedirect('/admin/operaciones/shifts/month?shop_cd=2&staff_cd=20&d=20260201');

    $row = DB::table('tb_staff_shift')->where('staff_cd', 20)->first();
    expect($row->gaisyutu_time_3)->toBe($day3.'<>'.($day3 + 7200))
        ->and($row->syukkin_kubun_3)->toBe('3<>')
        ->and($row->gaisyutu_time_4)->toBe('');

    $this->get('/admin/operaciones/shifts/month?shop_cd=2&staff_cd=20&d=20260201')
        ->assertSee('田中 2026年02月 シフト一覧')
        ->assertSee('"cd":"2","start":"12:00","end":"13:00"', false);

    // Un staff_cd que no es de la tienda no se guarda
    $this->post('/admin/operaciones/shifts/month', ['shop_cd' => '2', 'staff_cd' => '22', 'd' => '20260201', 'sch' => ['0_1_2__']]);
    expect(DB::table('tb_staff_shift')->where('staff_cd', 22)->exists())->toBeFalse();
});

it('日別シフト管理: calendarios del mes actual y los dos siguientes y la fecha como enlace (QA contra la muestra)', function () {
    seedShiftBoard();
    loginAs('master', 'secreto1');
    $this->travelTo(CarbonImmutable::create(2026, 11, 27, 12, 0, 0, 'Asia/Tokyo'));

    $this->get('/admin/operaciones/shifts/day?shop_cd=2&d=20260305')
        ->assertOk()
        ->assertSeeInOrder(['2026年11月', '2026年12月', '2027年1月'])
        ->assertDontSee('2026年10月')
        ->assertSee('<div id="dateDisp"><a href="'.e(route('admin.operaciones.shifts.day', ['d' => '20260305', 'shop_cd' => 2])).'">03/05<span>', false);
});
