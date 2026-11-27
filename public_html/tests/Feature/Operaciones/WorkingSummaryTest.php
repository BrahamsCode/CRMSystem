<?php

use App\Models\Operaciones\ShopWork;
use App\Models\Operaciones\WorkTime;
use App\Models\Shop;
use App\Services\Operaciones\LegacyMonth;
use App\Services\Operaciones\Working\WorkingReport;
use App\Services\Operaciones\Working\WorkingTotals;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// 勤怠 (category5/working, S2 W2): スタッフ一覧 mensual, スタッフ勤怠集計一覧, 帳票管理 y CSV 勤怠

/** Timestamp (texto, como tb_work_time) de una hora de Japón */
function summaryTs(string $datetime): string
{
    return (string) CarbonImmutable::parse($datetime, 'Asia/Tokyo')->getTimestamp();
}

/** 勤怠管理 activo, tienda con horario y un empleado con tres días marcados en febrero de 2026 */
function seedWorkingSummary(): void
{
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 51, 'tb_base_system_cd' => 5, 'system_name' => '勤怠管理', 'system_flg' => '1', 'rank' => 2]);
    DB::table('tb_shop')->where('cd', 1)->update(['start_work_time' => '9<>0', 'break_time' => '60', 'kyuyo_marume' => '15']);
    DB::table('tb_koumoku')->insert(['cd' => 1, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'mail_mag_select' => '1']);
    staffUser(2, ['id' => 'S002', 'name' => '山田', 'name_kana' => 'ヤマダ', 'touroku_date' => '100']);
    DB::table('tb_work_time')->insert([
        'cd' => 1, 'shop_cd' => 1, 'staff_cd' => 2, 'syukkinbi' => LegacyMonth::of(2026, 2)->epoch(),
        // 10日: 遅刻 + 残業, dos 外出
        'syukkin_time_10' => summaryTs('2026-02-10 08:52'), 'taikin_time_10' => summaryTs('2026-02-10 19:20'),
        'gaisyutu_time_10' => summaryTs('2026-02-10 12:03').'<>'.summaryTs('2026-02-10 15:00'),
        'kisha_time_10' => summaryTs('2026-02-10 12:58').'<>'.summaryTs('2026-02-10 15:10'),
        'syukkin_flg_10' => '遅刻', 'syukkin_kubun_10' => '残業',
        // 11日: 早出 + 退社
        'syukkin_time_11' => summaryTs('2026-02-11 07:40'), 'taikin_time_11' => summaryTs('2026-02-11 18:05'),
        'syukkin_flg_11' => '早出', 'syukkin_kubun_11' => '退社',
        // 12日: 出勤 + 早退
        'syukkin_time_12' => summaryTs('2026-02-12 08:59'), 'taikin_time_12' => summaryTs('2026-02-12 15:10'),
        'syukkin_flg_12' => '出勤', 'syukkin_kubun_12' => '早退',
    ]);
    // 営業日設定 de la tienda: 1日 y 15日 休日; otra tienda con más 休日 (el legacy leía cualquier fila)
    DB::table('tb_shop_work')->insert([
        ['cd' => 1, 'shop_cd' => 1, 'syukkinbi' => '2026/2', 'syukkin_time_1' => '0', 'syukkin_time_15' => '0', 'syukkin_time_20' => '1'],
        ['cd' => 2, 'shop_cd' => 9, 'syukkinbi' => '2026/2', 'syukkin_time_1' => '0', 'syukkin_time_15' => '0', 'syukkin_time_20' => '0'],
    ]);
}

it('WorkingTotals: corte de jornada, redondeo de 15 min y totales del mes', function () {
    $at = fn (string $t) => CarbonImmutable::parse($t, 'Asia/Tokyo');

    expect(WorkingTotals::businessDate($at('2026-02-11 04:59:58'), WorkingTotals::MONTHLY_CUTOFF)->toDateString())->toBe('2026-02-10')
        ->and(WorkingTotals::businessDate($at('2026-02-11 04:59:59'), WorkingTotals::MONTHLY_CUTOFF)->toDateString())->toBe('2026-02-11')
        ->and(WorkingTotals::businessDate($at('2026-02-11 04:45:00'), WorkingTotals::TOTALS_CUTOFF)->toDateString())->toBe('2026-02-11')
        ->and(WorkingTotals::businessDate($at('2026-03-01 03:00:00'), WorkingTotals::TOTALS_CUTOFF)->toDateString())->toBe('2026-02-28')
        ->and(WorkingTotals::roundUp(summaryTs('2026-02-10 08:52')))->toBe((int) summaryTs('2026-02-10 09:00'))
        ->and(WorkingTotals::roundUp(summaryTs('2026-02-10 09:15')))->toBe((int) summaryTs('2026-02-10 09:15'))
        ->and(WorkingTotals::roundDown(summaryTs('2026-02-10 19:29')))->toBe((int) summaryTs('2026-02-10 19:15'))
        ->and(WorkingTotals::roundUp(null))->toBe(0)
        ->and(WorkingTotals::workedSeconds(summaryTs('2026-02-10 08:52'), summaryTs('2026-02-10 19:20')))->toBe(615 * 60)
        ->and(WorkingTotals::workedSeconds(summaryTs('2026-02-10 08:52'), null))->toBe(0) // sin salida: 0時間0分
        ->and(WorkingTotals::workedSeconds(summaryTs('2026-02-10 10:00'), summaryTs('2026-02-10 09:00')))->toBe(0);

    $row = new WorkTime([
        'syukkin_time_3' => summaryTs('2026-02-03 09:10'), 'taikin_time_3' => summaryTs('2026-02-03 18:00'), 'syukkin_flg_3' => '出勤',
        'syukkin_time_4' => summaryTs('2026-02-04 09:30'), 'syukkin_flg_4' => '遅刻',
        'gaisyutu_time_3' => summaryTs('2026-02-03 12:00').'<>', 'kisha_time_3' => summaryTs('2026-02-03 13:05'),
    ]);
    $shopWork = new ShopWork(['syukkinbi' => '2026/2', 'syukkin_time_1' => '0', 'syukkin_time_2' => '1', 'syukkin_time_8' => '0']);

    expect(WorkingTotals::month($row, $shopWork, LegacyMonth::of(2026, 2), 3))->toBe([
        'attendanceDays' => 1,
        'workMinutes' => 8 * 60 + 45,
        'closedDays' => 2,
        'absenceDays' => 28 - 2 - 1,
        'outTime' => '12時00分',
        'backTime' => '13時05分',
    ])->and(WorkingTotals::month(null, null, LegacyMonth::of(2026, 2), 3)['absenceDays'])->toBe(28);
});

it('WorkingReport: entrada y salida de cálculo según 出勤状況 / 出勤区分 y min2HourMin', function () {
    $shop = new Shop(['start_work_time' => '9<>0', 'break_time' => '60', 'kyuyo_marume' => '15']);
    $day = CarbonImmutable::parse('2026-02-10', 'Asia/Tokyo');
    $now = CarbonImmutable::parse('2026-02-10 12:00', 'Asia/Tokyo');
    $fig = fn ($in, $out, $flag, $kubun) => WorkingReport::figures($shop, $day, $in ? summaryTs("2026-02-10 {$in}") : null, $out ? summaryTs("2026-02-10 {$out}") : null, $flag, $kubun, $now);

    expect($fig('07:40', '18:05', '早出', '退社'))->toBe(['start' => (int) summaryTs('2026-02-10 07:45'), 'end' => (int) summaryTs('2026-02-10 18:00'), 'hours' => 10.25])
        ->and($fig('09:07', '19:20', '遅刻', '残業'))->toBe(['start' => (int) summaryTs('2026-02-10 09:15'), 'end' => (int) summaryTs('2026-02-10 19:15'), 'hours' => 10.0])
        ->and($fig('08:59', '15:10', '出勤', '早退'))->toBe(['start' => (int) summaryTs('2026-02-10 09:00'), 'end' => (int) summaryTs('2026-02-10 15:00'), 'hours' => 6.0])
        ->and($fig('08:59', null, '出勤', ''))->toBe(['start' => (int) summaryTs('2026-02-10 09:00'), 'end' => null, 'hours' => 3.0]) // aún trabajando
        ->and($fig('08:59', null, '有給', ''))->toBe(['start' => null, 'end' => null, 'hours' => null])
        ->and(WorkingReport::hourMin(1624))->toBe('27:04')
        ->and(WorkingReport::hourMin(0))->toBe('00:00')
        ->and(WorkingReport::hourMin(-5))->toBe('00:00');

    // Sin 給与丸め el legacy dividía por cero: aquí redondea al minuto
    $noRounding = WorkingReport::figures(new Shop(['start_work_time' => '9<>0']), $day, summaryTs('2026-02-10 09:07'), null, '遅刻', '退社', $now);
    expect($noRounding['start'])->toBe((int) summaryTs('2026-02-10 09:07'));
});

it('スタッフ一覧: día de trabajo, horas redondeadas, búsqueda en sesión y paginación', function () {
    seedWorkingSummary();
    $this->travelTo(CarbonImmutable::parse('2026-02-11 04:30', 'Asia/Tokyo')); // antes de las 5: el día 10
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/working/monthly')
        ->assertOk()
        ->assertSee('<title>スタッフ一覧 - shop</title>', false)
        ->assertSee('<p class="h2Title">■スタッフ一覧</p>', false)
        ->assertSee('<small>名前</small>', false)
        ->assertSee('<span class="attention">1件～10件目/2件中</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;10日', false)
        ->assertSeeInOrder(['S002', '山田', '08時52分', '19時20分', '10時間15分', '2回', '遅刻', '残業', '編集', '管理者', '<font color="#FF7700">休</font>'], false)
        ->assertSee('勤怠一覧'); // menú 勤怠管理

    // この条件で抽出する: guarda la búsqueda y vuelve a la lista
    $this->post('/admin/operaciones/working/monthly/search', ['name' => '山', 'kensu' => '10', 'se_flg' => '1', 'search_frm' => 'この条件で抽出する'])
        ->assertRedirect('/admin/operaciones/working/monthly?kensu=10&se_flg=1&act=up');
    $this->get('/admin/operaciones/working/monthly?kensu=10&se_flg=1&act=up')
        ->assertSee('1件～10件目/1件中')
        ->assertSee('value="山"', false)
        ->assertDontSee('>管理者</span></td>', false);

    // Entrada por el menú (sin se_flg): se olvida la búsqueda
    $this->get('/admin/operaciones/working/monthly')->assertSee('1件～10件目/2件中');

    // 表示件数 1: dos páginas, la 2 funciona (el legacy siempre mostraba la 1)
    $this->get('/admin/operaciones/working/monthly?kensu=1&se_flg=1&act=up&page=2')
        ->assertSee('2件～2件目/2件中')
        ->assertSee('<li class="now"><b>2</b></li>', false)
        ->assertSee('管理者');

    // El marcador del legacy lleva a la pantalla nueva
    $this->get('/system/category5/working/working_list.php')->assertRedirect('/admin/operaciones/working/monthly');
});

it('スタッフ勤怠集計一覧: días, horas y 欠勤 con el 営業日設定 de la tienda del empleado', function () {
    seedWorkingSummary();
    $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00', 'Asia/Tokyo'));
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/working/totals')
        ->assertOk()
        ->assertSee('<title>スタッフ勤怠集計一覧 - shop</title>', false)
        ->assertSee('<th>今月の休日出勤時間</th>', false)
        // 出勤日数 1 (solo 出勤), 26時間30分, 欠勤 28 − 2 休日 − 1 = 25, primer 外出 / 帰社 del día 10
        ->assertSeeInOrder(['S002', '<td>1</td>', '<td>26時間30分</td>', '<td>25</td>', '<td>12時03分</td>', '<td>12時58分</td>', '管理者', '<td>0</td>', '<td>0時間0分</td>', '<td>26</td>'], false);

    $this->get('/system/category5/working/working_total_list.php?kensu=20')->assertRedirect('/admin/operaciones/working/totals?kensu=20');
});

it('帳票管理: solo con la bandera; mes de un empleado con totales', function () {
    seedWorkingSummary();
    $this->travelTo(CarbonImmutable::parse('2026-02-20 10:00', 'Asia/Tokyo'));
    loginAs('master', 'secreto1');

    config(['working.report_enabled' => false]);
    $this->get('/admin/operaciones/working/report')->assertForbidden();
    $this->get('/admin/operaciones/working/monthly')->assertDontSee('帳票管理');

    config(['working.report_enabled' => true]);
    $this->get('/admin/operaciones/working/monthly')->assertSee('帳票管理');
    $this->get('/admin/operaciones/working/report')
        ->assertOk()
        ->assertSee('<title>日別シフト管理 - shop</title>', false)
        ->assertSee('<p class="h2Title">■帳票管理</p>', false)
        ->assertSee('/system/category5/schedule/js/bootstrap-datepicker.js', false)
        ->assertSee('value="2026/02"', false)
        ->assertSee('<option value="2" >山田</option>', false)
        ->assertSee('<td>---</td>', false)
        ->assertSee('<td>00:00</td>', false);

    $this->get('/admin/operaciones/working/report?ym=2026/02&shop_cd=1&staff_cd=2')
        ->assertOk()
        ->assertSee('<option value="2" selected>山田</option>', false)
        ->assertSeeInOrder(['<td>10</td>', '<td style="color:">火</td>', '<td>08:52(09:00)</td>', '<td>19:20(19:15)</td>'], false)
        ->assertSeeInOrder(['<td>11</td>', '<td style="color:">水</td>', '<td>07:40(07:45)</td>', '<td>18:05(18:00)</td>'], false)
        ->assertSeeInOrder(['<td>12</td>', '<td>08:59(09:00)</td>', '<td>15:10(15:00)</td>'], false)
        ->assertSee('<td style="color:red">日</td>', false)
        // 就業時間合計 628 + 625 + 371 min, 時間外 0, 休出勤 (legacy: 外出 × 休憩) 2 × 60, 出勤日数 3
        ->assertSeeInOrder(['<td>27:04</td>', '<td>00:00</td>', '<td>02:00</td>', '<td>3</td>'], false);

    // Empleado de otra tienda: no se muestra
    staffUser(3, ['shop_cd' => 9, 'name' => '他店']);
    $this->get('/admin/operaciones/working/report?ym=2026/02&staff_cd=3')->assertOk()->assertDontSee('他店');

    $this->get('/system/category5/working/working_chouhyou.php?ym=2026/02')->assertRedirect('/admin/operaciones/working/report?ym=2026%2F02');
});

it('CSV 勤怠: Shift_JIS, cabecera con la tienda y horas de cálculo del día', function () {
    seedWorkingSummary();
    $this->travelTo(CarbonImmutable::parse('2026-02-20 10:00', 'Asia/Tokyo'));
    loginAs('master', 'secreto1');

    $response = $this->post('/system/category5/working/csv_download.php', ['dd' => '2026/2/10', 'shop_cd' => '1', 'exp' => 'ＣＳＶダウンロード'])
        ->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/x-csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('staff_kintai.csv');

    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($csv)->toBe(implode("\r\n", [
        '本店',
        'スタッフID,名前,出勤時刻,退勤時刻,勤務時間,休憩回数,出勤状況,出勤区分',
        ',管理者,,,,,,',
        'S002,山田,09:00,19:15,10.3,2,遅刻,残業',
    ])."\r\n");

    // Ruta nueva y la del Flash (csv_file_out.php): mismo CSV; sin día válido, sin valores
    $other = $this->get('/admin/operaciones/working/csv?dd=2026/2/31&shop_cd=1')->assertOk();
    expect(mb_convert_encoding($other->streamedContent(), 'UTF-8', 'SJIS-win'))->toContain("S002,山田,,,,0,,\r\n");
    $flash = $this->get('/system/category5/working/csv_download/csv_file_out.php?dd=2026/2/11&shop_cd=1')->assertOk();
    expect(mb_convert_encoding($flash->streamedContent(), 'UTF-8', 'SJIS-win'))->toContain("S002,山田,07:45,18:00,10.3,0,早出,退社\r\n");
});
