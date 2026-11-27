<?php

use App\Services\Operaciones\LegacyMonth;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// 勤怠一覧 (category5/working): working_calendar, work_list, work_list2, ng_work_list, work_list_update, change_syukkin

/** Epoch JST de 2026-02-10 (o el día dado) a las H:M */
function workEpoch(int $hour, int $minute, int $day = 10): string
{
    return (string) CarbonImmutable::create(2026, 2, $day, $hour, $minute, 0, LegacyMonth::TIMEZONE)->getTimestamp();
}

/** 業務支援管理 + 勤怠管理, tienda con jornada 9:00, 60 min de descanso y redondeo de 15 min, dos empleados con marcas del 10/2 */
function seedWorking(): void
{
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 51, 'tb_base_system_cd' => 5, 'system_name' => '勤怠管理', 'system_flg' => '1', 'rank' => 2]);
    DB::table('tb_shop')->where('cd', 1)->update(['start_work_time' => '9<>0', 'break_time' => '60', 'kyuyo_marume' => '15']);
    staffUser(2, ['id' => 'S002', 'name' => '遅刻さん', 'touroku_date' => '2026-01-02 00:00:00']);
    staffUser(3, ['id' => 'S003', 'name' => '早出さん', 'login_id' => 'u3', 'touroku_date' => '2026-01-03 00:00:00']);
    $month = LegacyMonth::of(2026, 2)->epoch();
    DB::table('tb_work_time')->insert([
        ['cd' => 1, 'shop_cd' => 1, 'staff_cd' => 2, 'syukkinbi' => $month, 'syukkin_time_10' => workEpoch(9, 7),
            'taikin_time_10' => workEpoch(19, 20), 'gaisyutu_time_10' => '1<>2', 'syukkin_flg_10' => '遅刻', 'syukkin_kubun_10' => '残業'],
        ['cd' => 2, 'shop_cd' => 1, 'staff_cd' => 3, 'syukkinbi' => $month, 'syukkin_time_10' => workEpoch(8, 40),
            'taikin_time_10' => workEpoch(18, 5), 'gaisyutu_time_10' => '', 'syukkin_flg_10' => '早出', 'syukkin_kubun_10' => '退社'],
    ]);
}

it('勤怠一覧 (calendario): mes, tiendas, enlace a la lista del día y 営業日 por shop_work_flg', function () {
    seedWorking();
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    DB::table('hatuban')->insert(['koumoku' => 'tb_shop_work', 'kazu' => 40]);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/working/calendar?year=2026&mon=2')
        ->assertOk()
        ->assertSee('<title>勤怠一覧 - shop</title>', false)
        ->assertSee('<span class="text3L bold">2026年2月</span>', false)
        ->assertSee('&lt;&lt;&nbsp;2026年1月へ', false)
        ->assertSee('2026年3月へ&nbsp;&gt;&gt;', false)
        ->assertSee('<option value="2">支店</option>', false) // master: todas las tiendas
        ->assertSee('value="2026/2/11"', false)
        ->assertSee('action="'.route('admin.operaciones.working.day').'"', false)
        ->assertSee('<span class="holiday">11</span>', false) // 建国記念日
        ->assertSee('営業日数：28／28')
        ->assertSee('/admin/operaciones/working/calendar'); // menú 勤怠管理 → 勤怠一覧

    // Al volver de la lista: el mes de dd
    $this->get('/admin/operaciones/working/calendar?dd=2026/3/5')->assertSee('2026年3月</span>', false);

    $this->post('/admin/operaciones/working/calendar', ['year' => '2026', 'mon' => '2', 'shop_work_flg' => 'up', '3' => '0'])
        ->assertRedirect('/admin/operaciones/working/calendar?year=2026&mon=2');
    $row = DB::table('tb_shop_work')->where('shop_cd', 1)->where('syukkinbi', '2026/2')->first();
    expect($row->cd)->toBe(40)->and($row->syukkin_time_3)->toBe('0')->and($row->syukkin_time_4)->toBe('1');

    $this->get('/admin/operaciones/working/calendar?year=2026&mon=2')
        ->assertSee('<td class="holidaySet">', false)
        ->assertSee('営業日数：27／28');

    // Marcador del legacy
    $this->get('/system/category5/working/working_calendar.php?year=2026&mon=2')
        ->assertRedirect('/admin/operaciones/working/calendar?year=2026&mon=2');
});

it('勤怠一覧 (calendario): quien no es master solo ve su tienda (campo oculto)', function () {
    seedWorking();
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    loginAs('u2', 'p');

    $this->get('/admin/operaciones/working/calendar?year=2026&mon=2')->assertOk()
        ->assertDontSee('支店')
        ->assertSee('<input type="hidden" name="shop_cd" value="1" />', false);
});

it('work_list: horas redondeadas, 勤務時間, 休憩回数 y paginación del legacy', function () {
    seedWorking();
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/working/day?shop_cd=1&dd=2026/2/10')
        ->assertOk()
        ->assertSee('2026年2月10日<BR>1件～3件目/3件中', false)
        // 遅刻 9:07 → 9:15; 残業 19:20 con fin 18:00 (9:00 + 8 h + 60 min) → 19:15; 10.0 h
        ->assertSee('09:07(09:15)')
        ->assertSee('19:20(19:15)')
        ->assertSee('10.0　時間')
        ->assertSee('<td id="gaisyutu_cnt2">2(60分)</td>', false)
        // 早出 8:40 → 8:45; 退社 → 18:00 - 8:45 = 9.25 h
        ->assertSee('08:40(08:45)')
        ->assertSee('18:05(18:00)')
        ->assertSee('9.3　時間')
        ->assertSee('<td id="staffID1" number="3">S003</td>', false) // más nuevo primero (touroku_date; el master sin fecha va antes)
        ->assertSee(trim(json_encode(route('admin.operaciones.working.update')), '"'), false);

    $this->get('/admin/operaciones/working/day?shop_cd=1&dd=2026/2/10&kensu=2&page=2')
        ->assertSee('3件～3件目/3件中')
        ->assertSee('<li class="now"><b>2</b></li>', false)
        ->assertSee('alt="戻る"', false);

    $this->get('/system/category5/working/work_list.php?shop_cd=1&dd=2026/2/10')
        ->assertRedirect('/admin/operaciones/working/day?shop_cd=1&dd=2026%2F2%2F10');
});

it('work_list2 y ng_work_list: su propio cálculo y formato', function () {
    seedWorking();
    loginAs('master', 'secreto1');

    // work_list2: fin = 9:00 + 8 h; 遅刻 + 残業 → salida - inicio redondeado = 10.08
    $this->get('/admin/operaciones/working/day2?shop_cd=1&dd=2026/2/10')
        ->assertOk()
        ->assertSee('<th>労働時間</th>', false)
        ->assertSee('10.08')
        ->assertSee('8.25')
        ->assertSee('1件～20件目/3件中')
        ->assertSee(json_encode(route('legacy.operaciones.working-change')), false);

    // ng_work_list: 労働時間 en HH:MM y la entrada con su redondeo
    $this->get('/admin/operaciones/working/ng?shop_cd=1&dd=2026/2/10')
        ->assertOk()
        ->assertSee('<td>08:15</td>', false)
        ->assertSee('<td>08:40(08:45)</td>', false)
        ->assertSee('<td>18:05</td>', false);
});

it('work_list_update: guarda el día con el corte de las 5:00 y vuelve a la lista', function () {
    seedWorking();
    loginAs('master', 'secreto1');

    $this->post('/admin/operaciones/working/update?page=1&shop_cd=1&dd=2026/2/10&kensu=20&scd=2&sh=22&sm=30&th=02&tm=15&sf=出勤&sk=退社')
        ->assertOk()
        ->assertSee('<title>勤怠情報 - shop</title>', false)
        ->assertSee('更新中です。')
        ->assertSee('content="1; URL='.e(route('admin.operaciones.working.day', ['page' => 1, 'shop_cd' => 1, 'dd' => '2026/2/10', 'kensu' => 20])).'"', false);

    $row = DB::table('tb_work_time')->where('staff_cd', 2)->first();
    expect($row->syukkin_time_10)->toBe(workEpoch(22, 30))
        ->and($row->taikin_time_10)->toBe(workEpoch(2, 15, 11)) // 2:15 < 5:00 → día siguiente
        ->and($row->syukkin_flg_10)->toBe('出勤')
        ->and($row->syukkin_kubun_10)->toBe('退社')
        ->and(DB::table('tb_work_time')->where('staff_cd', 3)->value('syukkin_flg_10'))->toBe('早出');

    // Hora vacía: se borra
    $this->post('/admin/operaciones/working/update?dd=2026/2/10&scd=2&sh=&sm=&th=&tm=&sf=&sk=')->assertOk();
    $row = DB::table('tb_work_time')->where('staff_cd', 2)->first();
    expect($row->syukkin_time_10)->toBe('')->and($row->taikin_time_10)->toBe('');

    // Escribir con GET no está permitido
    $this->get('/admin/operaciones/working/update?dd=2026/2/10&scd=2')->assertStatus(405);
});

it('change_syukkin: misma respuesta JSON; sin horas no toca las marcas', function () {
    seedWorking();
    loginAs('master', 'secreto1');

    // ng_work_list: solo 状況/区分
    $this->post('/system/category5/working/change_syukkin.php', [
        'staff_cd' => 2, 'nengappi' => '2026/2/10', 'syukkin_flg' => '早出', 'syukkin_kubun' => '早退',
    ])->assertOk()->assertExactJson(['shukkin_flg' => '早出', 'shukkin_kubun' => '早退']);
    $row = DB::table('tb_work_time')->where('staff_cd', 2)->first();
    expect($row->syukkin_time_10)->toBe(workEpoch(9, 7))->and($row->syukkin_flg_10)->toBe('早出');

    // work_list2: con horas (4:59 → día siguiente)
    $this->post('/system/category5/working/change_syukkin.php', [
        'staff_cd' => 3, 'nengappi' => '2026/2/10', 'syukkin_hour' => '04', 'syukkin_min' => '59',
        'taikin_hour' => '', 'taikin_min' => '', 'syukkin_flg' => '出勤', 'syukkin_kubun' => '',
    ])->assertExactJson(['shukkin_flg' => '出勤', 'shukkin_kubun' => '']);
    $row = DB::table('tb_work_time')->where('staff_cd', 3)->first();
    expect($row->syukkin_time_10)->toBe(workEpoch(4, 59, 11))->and($row->taikin_time_10)->toBe('');

    // Sin fila del mes: vacío, como el legacy (UPDATE sin filas)
    $this->post('/system/category5/working/change_syukkin.php', ['staff_cd' => 99, 'nengappi' => '2026/2/10'])
        ->assertExactJson(['shukkin_flg' => '', 'shukkin_kubun' => '']);

    // Día inválido (change_syukkin2 metía el día en el nombre de columna)
    $this->post('/system/category5/working/change_syukkin.php', ['staff_cd' => 2, 'nengappi' => '2026/2/10;drop'])->assertStatus(422);
    $this->post('/system/category5/working/change_syukkin.php', ['staff_cd' => 2, 'nengappi' => '2026/2/30'])->assertStatus(422);
});

it('勤怠一覧: sin sesión redirige al login', function () {
    seedWorking();

    $this->get('/admin/operaciones/working/calendar')->assertRedirect();
    $this->post('/system/category5/working/change_syukkin.php', ['staff_cd' => 2, 'nengappi' => '2026/2/10'])->assertRedirect();
    expect(DB::table('tb_work_time')->where('staff_cd', 2)->value('syukkin_flg_10'))->toBe('遅刻');
});
