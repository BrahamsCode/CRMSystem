<?php

require_once __DIR__ . '/helpers.php';

use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// 予約表 (legacy category9/yoyaku: time_table.php, time_table_staff.php, ajax_time_table.php,
// ajax_loadNewYoyakuList.php, ajax_loadNewRaitenList.php, ajax_getYoyakuDatetimeForm.php, ajax_uketsuke_status.php)

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 12, 1, 10, 30, 0, 'Asia/Tokyo'));
    reservasLogin();

    // 山田 (10) y 佐藤 (11) con 施術; 鈴木 (12) sin 施術 no sale en el 予約表
    DB::table('tb_staff')->where('cd', 10)->update(['rank' => 2]);
    DB::table('tb_staff')->insert([
        ['cd' => 11, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'staff11', 'login_pass' => 'x', 'name' => '佐藤', 'disp_flg' => '1', 'rank' => 1],
        ['cd' => 12, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'staff12', 'login_pass' => 'x', 'name' => '鈴木', 'disp_flg' => '1', 'rank' => 3],
    ]);
    DB::table('tb_koumoku')->insert([
        ['cd' => 1, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'tel', 'tb_koumoku_name' => '電話番号', 'koumoku_disp' => '1', 'koumoku_check' => '', 'rank' => 1],
        ['cd' => 2, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'free_koumoku_1', 'tb_koumoku_name' => 'ご要望', 'koumoku_disp' => '1', 'koumoku_check' => '', 'rank' => 2],
    ]);
    DB::table('tb_yoyaku_koumoku_master')->insert([
        ['cd' => 100, 'name' => 'カット', 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '1', 'rank' => 1],
        ['cd' => 101, 'name' => 'シャンプー', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '2', 'rank' => 2],
    ]);
    DB::table('tb_staff_works')->insert([
        ['cd' => 1, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 100, 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 2, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 101, 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 3, 'tb_staff_cd' => 11, 'tb_yoyaku_koumoku_master_cd' => 100, 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1'],
    ]);
    ReservationConfig::create(['tb_shop_cd' => 1, 'start_time' => '09:00', 'end_time' => '20:00']);

    // Franja de 山田 el 2026/12/01: 09:00-18:00, descanso 12:00-13:00
    $this->slot = ReservationSlot::create([
        'tb_staff_cd' => 10, 'moushikomi_date' => '2026/12/01', 'start_time' => 9 * 3600, 'end_time' => 18 * 3600,
        'people' => 3, 'start_break_time' => '12:00', 'end_break_time' => '13:00', 'hyouji_flg' => '1', 'disp_flg' => '1', 'status_flg' => '1',
    ]);
    DB::table('tb_customer')->insert(['cd' => 500, 'id' => 'M500', 'name' => '会員 太郎', 'tel' => '', 'touroku_kubun' => '2', 'disp_flg' => '1']);
});

afterEach(fn () => Carbon::setTestNow());

function timetableReservation(int $cd, int $start, int $end, array $overrides = []): void
{
    DB::table('tb_yoyaku')->insert(array_merge([
        'cd' => $cd, 'tb_yoyaku_uketsuke_day_cd' => test()->slot->cd, 'start_time' => $start, 'end_time' => $end,
        'name' => '田中 花子', 'tel' => '0612345678', 'people' => 1, 'base_tb_yoyaku_koumoku_master_cd' => '100',
        'option_tb_yoyaku_koumoku_master_cd' => '101', 'comment' => "窓側\n希望", 'free_koumoku' => rawurlencode('静か に'),
        'mark' => '★', 'entry_type' => '', 'hyouji_flg' => '1', 'disp_flg' => '1', 'touroku_date' => (string) (1764550000 + $cd),
    ], $overrides));
}

it('pinta la vista día: personal con 施術, 状況, grises, descanso y barras solapadas', function () {
    timetableReservation(1, 10 * 3600, 11 * 3600 + 1800);
    timetableReservation(2, 11 * 3600, 12 * 3600, ['tb_customer_cd' => 500, 'people' => 2]);
    timetableReservation(3, 14 * 3600, 15 * 3600, ['disp_flg' => '9']);

    $response = $this->get(route('admin.reservas.timetable', ['d' => '20261201']));
    $response
        ->assertOk()
        ->assertSee('トップ -総合業務管理システム-')
        ->assertSee('総合TOPへ')
        ->assertSee('新規予約リスト')
        ->assertSee('予約受付が開始されていません。')   // 佐藤: sin franja
        ->assertDontSee('鈴木')                          // sin 施術
        ->assertSee('<div class="timeBar" style="left: 0px;">09:00</div>', false)
        ->assertSee('<div class="timeBar" style="left: 660px;">20:00</div>', false)
        ->assertSee('.yoyakuField {' . "\n\t" . 'width: 894px;', false)
        ->assertSee('<option value="1" selected>電話受付</option>', false)
        ->assertSee('background:#778899; text-align: center;', false)
        // descanso 12:00-13:00 y fuera de horario a la derecha (18:00-20:00 + 30)
        ->assertSee('width:60px; height:100%; left:180px; top:0px; background: #777777" title="休憩"', false)
        ->assertSee('width:150px; height:100%; right:0px;', false)
        // barra 10:00-11:30 (no socio) y la solapada del socio (fila 2, 2 personas)
        ->assertSee('left: 60px; top: 0px; width: 88px; height: 20px;background: #AACCFF;', false)
        ->assertSee('left: 120px; top: 24px; width: 58px; height: 40px;background: #FFBBBB;', false)
        ->assertSee('kokyaku="会員 太郎"', false)
        ->assertSee('tel="データがありません。"', false)
        ->assertSee('menu="&lt;ul&gt;&lt;li&gt;カット&lt;/li&gt;&lt;li&gt;シャンプー&lt;/li&gt;&lt;/ul&gt;"', false)
        ->assertSee('free_koumoku="静か に"', false)
        ->assertSee('comment="窓側&lt;br /&gt;', false)
        ->assertSee('<div style="width: 1px; height: 74px;"></div>', false)
        ->assertSee('$("#total_yoyaku_cnt").text("2");', false)
        ->assertSee('$("#total_people_cnt").text("3");', false);

    // 佐藤 (rank 1) va antes que 山田 (rank 2)
    expect(strpos($response->getContent(), 'id="st11"'))->toBeLessThan(strpos($response->getContent(), 'id="st10"'));
});

it('avisa si la tienda no tiene 予約設定', function () {
    ReservationConfig::query()->delete();

    $this->get(route('admin.reservas.timetable'))
        ->assertOk()
        ->assertSee('※この店舗の予約設定は未設定です。まず、予約設定を行って下さい。')
        ->assertDontSee('yoyakuListTable');
});

it('ordena primero a quien tiene franja con timeline_sort_type = 1', function () {
    ReservationConfig::query()->update(['timeline_sort_type' => '1']);

    $content = $this->get(route('admin.reservas.timetable', ['d' => '20261201']))->assertOk()->getContent();

    expect(strpos($content, 'id="st10"'))->toBeLessThan(strpos($content, 'id="st11"'));
});

it('muestra 週間 y 月間 con una cabecera por día y 終了しました en los días pasados', function () {
    ReservationSlot::create(['tb_staff_cd' => 10, 'moushikomi_date' => '2026/11/30', 'start_time' => 9 * 3600, 'end_time' => 18 * 3600, 'people' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1']);

    $week = $this->get(route('admin.reservas.timetable', ['ichiran' => 2, 'date' => '2026-W49']));
    $week
        ->assertOk()
        ->assertSee('11月30日～<br>12月06日', false)
        ->assertSee('終了しました')
        ->assertSee('class="date-header" rel="20261130"', false)
        ->assertSee('value="2026-W49"', false);
    expect(substr_count($week->getContent(), 'class="date-header"'))->toBe(7);

    $month = $this->get(route('admin.reservas.timetable', ['ichiran' => 1, 'date' => '2026-12']))
        ->assertOk()
        ->assertSee('12月');
    expect(substr_count($month->getContent(), 'class="date-header"'))->toBe(31);
});

it('admite franjas que pasan de medianoche (horas > 24)', function () {
    ReservationConfig::query()->update(['start_time' => '18:00', 'end_time' => '26:00']);
    $this->slot->update(['start_time' => 18 * 3600, 'end_time' => 26 * 3600, 'start_break_time' => '', 'end_break_time' => '']);
    timetableReservation(1, 24 * 3600 + 1800, 25 * 3600 + 1800);

    $this->get(route('admin.reservas.timetable', ['d' => '20261201']))
        ->assertOk()
        ->assertSee('<div class="timeBar" style="left: 360px;">00:00</div>', false)
        ->assertSee('<div class="timeBar" style="left: 480px;">02:00</div>', false)
        ->assertSee('left: 390px; top: 0px; width: 58px;', false)
        ->assertSee('startTime="00:30"', false)
        ->assertSee('endTime="01:30"', false);
});

it('publica las URL de entrada del legacy (category9/ y time_table.php)', function () {
    $this->get('/system/category9/')->assertRedirect(route('admin.reservas.timetable'));
    $this->get('/system/category9/yoyaku/time_table.php?d=20261201')->assertOk()->assertSee('yoyakuListTable');
    $this->get('/system/category9/yoyaku/time_table_staff.php?staff=10&d=20261201')->assertOk()->assertSee('予約状況');
});

it('muestra el mes del empleado (スタッフ別予約表) y vuelve al 予約表 si no es de la tienda', function () {
    timetableReservation(1, 10 * 3600, 11 * 3600);

    $r = $this->get(route('admin.reservas.timetable.staff', ['shop' => 1, 'staff' => 10, 'd' => '20261201']));
    $r->assertOk()
        ->assertSee('[2026<small>年</small>12<small>月</small>] 山田 <small>予約状況</small>', false)
        ->assertSee('d=20261130&amp;bd=20261201', false)          // 前月: último día del mes anterior
        ->assertSee('d=20270101&amp;bd=20261201', false)          // 次月
        ->assertSee('javascript:wopen_config(&#039;' . $this->slot->cd . '&#039;);', false)
        ->assertSee('javascript:wopen_config_day(&#039;20261202&#039;);', false)
        ->assertSee('<input class="day_check day3" type="checkbox" name="days[]" value="2026/12/02" dow="3" />', false)
        ->assertSee('status_flg_20261201', false)
        ->assertSee('kokyaku="田中 花子"', false)
        ->assertSee('選択した日で一括受付開始する')
        ->assertSee('action="' . route('admin.reservas.slots.bulk') . '"', false);

    $this->get(route('admin.reservas.timetable.staff', ['shop' => 1, 'staff' => 12]))
        ->assertRedirect(route('admin.reservas.timetable'));
});

it('devuelve el popup de edición (ajax_time_table.php) con menús, horas y もしかして', function () {
    timetableReservation(1, 10 * 3600, 11 * 3600 + 1800, ['tel' => '0311112222']);
    DB::table('tb_customer')->insert(['cd' => 501, 'name' => '田中 花子', 'tel' => '0311112222', 'touroku_kubun' => '2', 'disp_flg' => '1']);

    $r = $this->post('/system/category9/yoyaku/ajax_time_table.php', ['cd' => 1]);
    $r->assertOk()
        ->assertSee('顧客情報')
        ->assertSee('name="yoyakuName" id="yoyakuName" value="田中 花子"', false)
        ->assertSee('<option value="10" selected>10</option>', false)
        ->assertSee('<option value="11" selected>11</option>', false)
        ->assertSee('<option value="30" selected>30</option>', false)
        ->assertSee('※予約受付可能時間：09:00 ～ 18:00')
        ->assertSee('name="menu[100]" id="checkbox_menu_100" value="100"', false)
        ->assertSee('ベースメニュー')
        ->assertSee('オプションメニュー')
        ->assertSee('name="free_koumoku[1]" value="静か に"', false)
        ->assertSee("relatedCustomer('1','501')", false)
        ->assertSee('会員「田中 花子」さんとして扱う')
        ->assertSee('詳細検索から登録会員を探す')
        ->assertSee("saveData('1')", false);

    $this->post('/system/category9/yoyaku/ajax_time_table.php', ['cd' => 999])->assertOk()->assertSeeText('ERROR:01');
});

it('muestra los botones del socio en el popup (顧客詳細)', function () {
    timetableReservation(1, 10 * 3600, 11 * 3600, ['tb_customer_cd' => 500]);

    $this->post(route('admin.reservas.timetable.data'), ['cd' => 1])
        ->assertOk()
        ->assertSee('会員ID：')
        ->assertSee('M500')
        ->assertSee('value="顧客詳細"', false)
        ->assertDontSee('詳細検索から登録会員を探す');
});

it('devuelve el formulario de fecha y hora (ajax_getYoyakuDatetimeForm.php)', function () {
    timetableReservation(1, 10 * 3600 + 1500, 11 * 3600);

    $r = $this->get('/system/category9/yoyaku/ajax_getYoyakuDatetimeForm.php?cd=1');
    $r->assertOk()
        ->assertSee('開始日時を選択してください')
        ->assertSee('<option value="2026" selected>2026</option>', false)
        ->assertSee('<option value="2027" >2027</option>', false)
        ->assertSee('<option value="12" selected>12</option>', false)
        ->assertSee('<option value="10" selected>10</option>', false)
        ->assertSee('<option value="25" selected>25</option>', false)
        ->assertSee("changeReserveDatetime('1')", false);
});

it('carga 新規予約リスト y 来店リスト (cookies del panel)', function () {
    timetableReservation(1, 10 * 3600, 11 * 3600);
    timetableReservation(2, 12 * 3600, 13 * 3600, ['tb_customer_cd' => 500]);
    timetableReservation(3, 14 * 3600, 15 * 3600, ['hyouji_flg' => '0', 'name' => '非表示']);

    $response = $this->post('/system/category9/yoyaku/ajax_loadNewYoyakuList.php', ['tb_shop_cd' => 1, 'type' => 'yoyakuList'])
        ->assertOk()
        ->assertSee('新しいリストをロードしました。')
        ->assertSee('予約者名')
        ->assertSee('会員 太郎')
        ->assertSee('田中 花子')
        ->assertDontSee('非表示')
        ->assertCookie('c9_yoyaku_control_panel', 'yoyakuList')
        ->assertCookie('c9_yoyaku_new_record', '2');
    expect(strpos($response->getContent(), '会員 太郎'))->toBeLessThan(strpos($response->getContent(), '田中 花子'));

    // Misma última reserva: sin aviso
    $this->withCookie('c9_yoyaku_new_record', '2')
        ->post('/system/category9/yoyaku/ajax_loadNewYoyakuList.php', ['tb_shop_cd' => 1, 'type' => 'yoyakuList'])
        ->assertOk()
        ->assertDontSee('新しいリストをロードしました。');

    DB::table('tb_raiten_rireki')->insert(['cd' => 1, 'user_cd' => 500, 'shop_cd' => 1, 'raiten_time' => (string) Carbon::create(2026, 12, 1, 9, 5, 7, 'Asia/Tokyo')->getTimestamp(), 'raiten_hiduke' => '2026/12/01', 'disp_flg' => '1']);
    $this->post('/system/category9/yoyaku/ajax_loadNewRaitenList.php?d=20261201', ['tb_shop_cd' => 1, 'type' => 'raitenList'])
        ->assertOk()
        ->assertSee('12月01日 09時05分07秒')
        ->assertSee('会員 太郎')
        ->assertCookie('c9_yoyaku_control_panel', 'raitenList');
    $this->post('/system/category9/yoyaku/ajax_loadNewRaitenList.php?d=20261202', ['tb_shop_cd' => 1, 'type' => 'raitenList'])
        ->assertOk()
        ->assertSee('2026年12月02日の来店履歴はありません。');
});

it('abre el panel guardado en la cookie que escribe el JS (sin cifrar)', function () {
    // El navegador manda la cookie tal cual en la cabecera Cookie (EncryptCookies no puede descifrarla)
    $content = $this->withHeader('Cookie', 'c9_yoyaku_control_panel=ichiran')
        ->get(route('admin.reservas.timetable'))
        ->assertOk()
        ->getContent();

    // El último loadNewYoyakuList() del ready es el del panel guardado
    expect($content)->toMatch('/loadNewYoyakuList\("ichiran"\);\s+\$\("\*"\)\.click/');
});

it('cambia el 状況 desde la URL legacy ajax_uketsuke_status.php', function () {
    $sessionId = \Illuminate\Support\Str::random(40);
    $this->withCookie(config('session.cookie'), $sessionId)
        ->get('/system/category9/yoyaku/ajax_uketsuke_status.php?' . http_build_query([
            'td' => '20261201', 'sess' => $sessionId, 'staffCd' => '10', 'statusFlg' => '2',
        ]))->assertOk()->assertContent('');

    expect($this->slot->fresh()->status_flg)->toBe('2');

    $this->get('/system/category9/yoyaku/ajax_uketsuke_status.php?td=20261201&sess=x&staffCd=10&statusFlg=')
        ->assertOk()->assertContent('不正アクセス！！');
});

it('escapa los datos de la reserva en el 予約表', function () {
    timetableReservation(1, 10 * 3600, 11 * 3600, ['name' => '<b>悪</b>', 'comment' => '<script>x</script>']);

    $this->get(route('admin.reservas.timetable', ['d' => '20261201']))
        ->assertOk()
        ->assertDontSee('<b>悪</b>', false)
        ->assertDontSee('<script>x</script>', false)
        ->assertSee('&lt;b&gt;悪&lt;/b&gt;', false);
});
