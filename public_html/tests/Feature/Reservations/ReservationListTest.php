<?php

require_once __DIR__ . '/helpers.php';

use App\Models\Reservations\ReservationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// 予約一覧 (legacy category9/yoyaku: view_shop.php, yoyaku_list_calendar.php, pop_calendar.php,
// print/yoyaku_list_print.php, print/csv_download.php, yoyakuPrint.php, ajaxYoyakuPrint.php)

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 12, 1, 10, 30, 0, 'Asia/Tokyo'));
    reservasLogin();

    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '駅前店', 'disp_flg' => '1']);
    DB::table('tb_staff')->insert(['cd' => 20, 'office_cd' => 1, 'shop_cd' => 2, 'login_id' => 'staff20', 'login_pass' => 'x', 'name' => '駅前 次郎', 'disp_flg' => '1']);
    DB::table('tb_koumoku')->insert([
        ['cd' => 1, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'name_kana', 'tb_koumoku_name' => 'カナ', 'koumoku_disp' => '1', 'koumoku_select' => '1', 'rank' => 1],
        ['cd' => 2, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'tel', 'tb_koumoku_name' => '電話番号', 'koumoku_disp' => '1', 'koumoku_select' => '1', 'rank' => 2],
        ['cd' => 3, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'mail', 'tb_koumoku_name' => 'メール', 'koumoku_disp' => '1', 'koumoku_select' => '0', 'rank' => 3],
        ['cd' => 4, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => 'free_koumoku_1', 'tb_koumoku_name' => 'ご要望', 'koumoku_disp' => '1', 'koumoku_select' => '1', 'rank' => 4],
    ]);
    DB::table('tb_yoyaku_koumoku_master')->insert([
        ['cd' => 100, 'name' => 'カット', 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '1', 'rank' => 2],
        ['cd' => 101, 'name' => 'シャンプー', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '2', 'rank' => 1],
    ]);
    $this->slot = ReservationSlot::create([
        'tb_staff_cd' => 10, 'moushikomi_date' => '2026/12/01', 'start_time' => 9 * 3600, 'end_time' => 26 * 3600,
        'people' => 3, 'hyouji_flg' => '1', 'disp_flg' => '1', 'status_flg' => '1',
    ]);
    $this->slot2 = ReservationSlot::create([
        'tb_staff_cd' => 20, 'moushikomi_date' => '2026/12/03', 'start_time' => 9 * 3600, 'end_time' => 18 * 3600,
        'people' => 3, 'hyouji_flg' => '1', 'disp_flg' => '1', 'status_flg' => '1',
    ]);
    DB::table('tb_customer')->insert(['cd' => 500, 'id' => 'M500', 'name' => '会員 太郎', 'name_kana' => 'カイイン タロウ', 'tel' => '0311112222', 'email1' => 'taro@example.jp', 'touroku_kubun' => '2', 'disp_flg' => '1']);
});

afterEach(fn () => Carbon::setTestNow());

function listReservation(int $cd, int $slotCd, int $start, int $end, array $overrides = []): void
{
    DB::table('tb_yoyaku')->insert(array_merge([
        'cd' => $cd, 'tb_yoyaku_uketsuke_day_cd' => $slotCd, 'start_time' => $start, 'end_time' => $end,
        'name' => '田中 花子', 'name_kana' => 'タナカ ハナコ', 'tel' => '0612345678', 'mail' => 'hana@example.jp', 'people' => 1,
        'base_tb_yoyaku_koumoku_master_cd' => '100', 'option_tb_yoyaku_koumoku_master_cd' => '101',
        'free_koumoku' => rawurlencode('窓側 希望'), 'entry_type' => '', 'hyouji_flg' => '1', 'disp_flg' => '1', 'touroku_date' => '1764550000',
    ], $overrides));
}

it('view_shop: 店舗毎予約数 por tienda y día, con enlaces al 予約表', function () {
    listReservation(1, $this->slot->cd, 10 * 3600, 11 * 3600);
    listReservation(2, $this->slot->cd, 11 * 3600, 12 * 3600);
    listReservation(3, $this->slot->cd, 12 * 3600, 13 * 3600, ['disp_flg' => '9']);
    listReservation(4, $this->slot2->cd, 10 * 3600, 11 * 3600);

    $html = $this->get(route('admin.reservas.by-shop', ['d' => '20261215']))->assertOk()->getContent();

    expect($html)->toContain('予約一覧 - ')
        ->toContain('<span class="hyoujiDate">2026年12月</span>')
        ->toContain('店舗毎予約数')
        ->toContain('1(火)')
        ->toContain('本店')->toContain('駅前店')
        ->toContain('d=20261130')->toContain('d=20270101');
    // 本店 el 1: 2 reservas activas (la cancelada no cuenta); 駅前店 el 3: 1
    expect($html)->toMatch('/d=20261201&amp;shop=1" style="font-size:16px;font-weight: bold; color: #EE0000;">2</')
        ->toMatch('/d=20261203&amp;shop=2" style="font-size:16px;font-weight: bold; color: #EE0000;">1</')
        ->toMatch('/d=20261202&amp;shop=1" style="">0</');

    // URL legacy
    $this->get('/system/category9/yoyaku/view_shop.php')->assertOk()->assertSee('2026年12月', false);
});

it('yoyaku_list_calendar: cuenta tb_reserve por día y marca 休日 de tb_shop_work', function () {
    DB::table('tb_shop')->where('cd', 2)->update(['disp_flg' => '9']); // una sola tienda visible
    $at = fn (int $d, int $h) => (string) Carbon::create(2026, 12, $d, $h, 0, 0, 'Asia/Tokyo')->getTimestamp();
    DB::table('tb_reserve')->insert([
        ['cd' => 1, 'reserve_time' => $at(5, 10), 'shop_cd' => '1', 'reserve_flg' => ''],
        ['cd' => 2, 'reserve_time' => $at(5, 23), 'shop_cd' => '1', 'reserve_flg' => ''],
        ['cd' => 3, 'reserve_time' => $at(6, 0), 'shop_cd' => '1', 'reserve_flg' => ''],
        ['cd' => 4, 'reserve_time' => $at(5, 12), 'shop_cd' => '2', 'reserve_flg' => ''],
    ]);
    DB::table('tb_shop_work')->insert(['cd' => 1, 'shop_cd' => 1, 'syukkinbi' => '2026/12', 'syukkin_time_7' => '0']);

    $html = $this->get(route('admin.reservas.list-calendar'))->assertOk()->getContent();

    expect($html)->toContain('■予約・来店一覧')
        ->toContain('2026年12月')
        ->toContain('2026年11月へ')->toContain('2027年1月へ')
        ->toContain('<span class="nowday">1</span>')
        ->toContain('<span class="holiday">6</span>') // domingo
        ->toContain('(2件)<input type="hidden" name="shop" value="1" />')
        ->toContain('value="2026/12/5"')
        ->toContain('<td class="holidaySet"><span class="normalday">7</span>')
        ->toContain('表　示');
    expect(substr_count($html, '(1件)'))->toBe(1); // el 6

    // POST de los botones de mes (year + mon) y vuelta con dd
    $this->post(route('admin.reservas.list-calendar'), ['year' => 2027, 'mon' => 1])->assertOk()->assertSee('2027年1月', false)->assertSee('2026年12月へ', false);
    $this->get('/system/category9/yoyaku/yoyaku_list_calendar.php?dd=2026/2/3')->assertOk()->assertSee('<span class="text3L bold">2026年2月</span>', false);
});

it('pop_calendar: 12 meses del año con hoy, el día elegido y enlaces a la ventana madre', function () {
    $html = $this->get(route('admin.reservas.pop-calendar', ['shop' => 1, 'd' => '20261224', 'isCti' => '1', 'tel' => '0311112222', 'customerCd' => '500']))
        ->assertOk()->getContent();

    expect($html)->toContain('年間カレンダー - ')
        ->toContain('■年間カレンダー')
        ->toContain('2026年1月')->toContain('2026年12月')
        ->toContain('<span class="weekday today">')
        ->toContain('<span class="weekday selectday">')
        ->toContain('メインウィンドウがありません')
        ->toContain('閉じる');
    expect(substr_count($html, 'class="smallCal"'))->toBe(12);
    // Enlaces con el CTI arrastrado (Js::from escapa & y /)
    expect($html)->toContain('isCti=1')->toContain('y=2025')->toContain('y=2027')->toContain('d=20261224');

    $this->get('/system/category9/yoyaku/pop_calendar.php?y=2030')->assertOk()->assertSee('2030年6月', false);
});

it('yoyaku_list_print: lista del día con columnas de tb_koumoku y datos del socio', function () {
    listReservation(1, $this->slot->cd, 25 * 3600, 25 * 3600 + 1800, ['tb_customer_cd' => 500]);
    listReservation(2, $this->slot->cd, 10 * 3600, 11 * 3600 + 1800, ['option_tb_yoyaku_koumoku_master_cd' => '']);
    listReservation(3, $this->slot2->cd, 10 * 3600, 11 * 3600); // otra tienda

    $html = $this->get(route('admin.reservas.print', ['shop' => 1, 'd' => '20261201']))->assertOk()->getContent();

    expect($html)->toContain('<title>予約一覧出力</title>')
        ->toContain('2026/12/01')
        ->toContain('<th>カナ</th>')->toContain('<th>電話番号</th>')->toContain('<th>ご要望</th>')->toContain('<th>指名</th>')->toContain('<th>作業内容</th>')
        ->not->toContain('<th>メールアドレス</th>')
        ->toContain('<td>10:00 ～ 11:30</td>')
        ->toContain('<td>25:00 ～ 25:30</td>')
        ->toContain('<td>会員 太郎</td>')->toContain('<td>カイイン タロウ</td>')
        ->toContain('<td>窓側 希望</td>')
        ->toContain('<td>山田</td>')
        ->toContain('<td>シャンプー / カット</td>') // ORDER BY rank
        ->toContain('CSV出力する')
        ->not->toContain('駅前 次郎');
    // orden por start_time
    expect(strpos($html, '10:00 ～'))->toBeLessThan(strpos($html, '25:00 ～'));

    $this->get(route('admin.reservas.print', ['shop' => 1, 'd' => '20261202']))->assertOk()->assertSee('該当日の予約リストはありません。');
    $this->get(route('admin.reservas.print'))->assertOk()->assertContent('');
});

it('csv_download: CSV SJIS-win del último 予約一覧出力', function () {
    listReservation(1, $this->slot->cd, 10 * 3600, 11 * 3600, ['tb_customer_cd' => 500]);

    $this->get(route('admin.reservas.print', ['shop' => 1, 'd' => '20261201']))->assertOk();
    $response = $this->post('/system/category9/yoyaku/print/csv_download.php', ['titleflg' => '1', 'flg' => 'upload'])->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/x-csv')
        ->and($response->headers->get('content-disposition'))->toContain('yoyaku.csv');
    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($csv)->toBe(
        "\t2026/12/01\r\n"
        . "\"\",\"名前\",\"カナ\",\"電話番号\",\"ご要望\",\"指名\",\"作業内容\"\r\n"
        . "\"10:00 ～ 11:00\",\"会員 太郎\",\"カイイン タロウ\",\"\t0311112222\",\"\t窓側 希望\",\"山田\",\"シャンプー / カット\"\r\n"
    );

    // Sin reservas: respuesta vacía, como el legacy
    $this->post(route('admin.reservas.csv'), ['shop' => 1, 'd' => '20261202'])->assertOk()->assertContent('');
});

it('予約プリント: el sondeo envía la siguiente reserva pendiente y la marca print_flg 2', function () {
    listReservation(1, $this->slot->cd, 10 * 3600, 11 * 3600, ['print_flg' => '2']);
    listReservation(2, $this->slot->cd, 11 * 3600, 12 * 3600, ['print_flg' => '1', 'name' => '<b>佐々木</b>']);
    listReservation(3, $this->slot->cd, 12 * 3600, 13 * 3600, ['print_flg' => '1', 'tb_customer_cd' => 500]);

    $this->get(route('admin.reservas.printer'))->assertOk()
        ->assertSee('<title>予約プリント</title>', false)
        ->assertSee('ofscURL', false)
        ->assertSee('setInterval(\'crawl()\',3000)', false);
    $this->get('/system/category9/yoyaku/yoyakuPrint.php')->assertOk();

    $first = $this->post('/system/category9/yoyaku/ajaxYoyakuPrint.php')->assertOk()->getContent();
    expect($first)->toContain('sendMessage(printContent,NORMAL_MODE);')
        ->toContain('customer_name')
        ->toContain('u0026lt;b') // escapado en el XML (&lt;) y luego en el JS (& → &)
        ->not->toContain('<b>')
        ->toContain('2026\/12\/01')
        ->toContain('11:00')
        ->toContain('RetailStoreID')->toContain('本店');
    expect(DB::table('tb_yoyaku')->where('cd', 2)->value('print_flg'))->toBe('2');

    $second = $this->post(route('admin.reservas.printer.poll'))->assertOk()->getContent();
    expect($second)->toContain('会員 太郎');
    expect(DB::table('tb_yoyaku')->where('cd', 3)->value('print_flg'))->toBe('2');

    $this->post(route('admin.reservas.printer.poll'))->assertOk()->assertContent('');
});

it('la navegación del 予約表 enlaza a 店舗毎予約数, 年間カレンダー, 予約一覧出力 y 予約プリント', function () {
    DB::table('tb_yoyaku_config')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'start_time' => '09:00', 'end_time' => '20:00']);
    DB::table('tb_base_system_grandchild')->insert([
        ['cd' => 1, 'tb_base_system_cd' => 9, 'tb_base_system_child_cd' => 105, 'system_name' => '予約一覧出力', 'system_flg' => '1'],
        ['cd' => 2, 'tb_base_system_cd' => 9, 'tb_base_system_child_cd' => 105, 'system_name' => 'リアルタイム印刷', 'system_flg' => '1'],
    ]);

    $html = $this->get(route('admin.reservas.timetable', ['d' => '20261201']))->assertOk()->getContent();

    expect($html)->toContain(route('admin.reservas.by-shop', ['d' => '20261201']))
        ->toContain('当日予約一覧出力')
        ->toContain(e(route('admin.reservas.print', ['shop' => 1, 'd' => '20261201'])))
        ->toContain('<iframe src="' . route('admin.reservas.printer') . '"')
        ->toContain('pop-calendar');
});
