<?php

require_once __DIR__.'/../Nucleo/helpers.php';

use App\Mail\Reservas\ReservationMail;
use App\Models\Customers\Customer;
use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// Reserva web pública del legacy r/ (y alias reserve/): index.php, check.php, ajax_getChoiceData.php,
// ajax_getScheduleData.php, ajax_reserveRegister.php, ajax_reserveDelete.php y getReservationWaitTime.php.
// Sin login: la sesión y el parámetro sess. JSON con las claves, el orden y los textos del legacy.

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 30, 10, 0, 0, 'Asia/Tokyo'));
    Mail::fake();

    seedNucleo();
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 1, 'yoyaku_time_interval' => '30']);
    DB::table('tb_shop')->where('cd', 1)->update(['tel' => '06-1111-2222', 'email' => 'shop@example.jp']);
    DB::table('tb_staff')->insert(['cd' => 10, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'staff10', 'login_pass' => 'x', 'name' => '山田', 'disp_flg' => '1', 'reserve_flg' => '1', 'rank' => 1, 'email1' => 'yamada@example.jp']);

    $koumoku = fn (int $cd, string $field, string $name, string $check = '') => ['cd' => $cd, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => $field, 'tb_koumoku_name' => $name, 'koumoku_disp' => '1', 'koumoku_check' => $check, 'rank' => $cd];
    DB::table('tb_koumoku')->insert([
        $koumoku(1, 'name', 'お名前', '1'), $koumoku(2, 'name_kana', 'フリガナ'), $koumoku(3, 'tel', '電話番号', '1'),
        $koumoku(4, 'mail', 'メールアドレス'), $koumoku(5, 'free_koumoku_1', 'ご要望'),
    ]);

    // カット (base, 60 min) y シャンプー (opción, 30 min) de 山田
    DB::table('tb_yoyaku_koumoku_master')->insert([
        ['cd' => 100, 'name' => 'カット', 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '1', 'rank' => 1],
        ['cd' => 101, 'name' => 'シャンプー', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '2', 'rank' => 2],
    ]);
    DB::table('tb_staff_works')->insert([
        ['cd' => 1, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 100, 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 2, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 101, 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1'],
    ]);

    ReservationConfig::create(['tb_shop_cd' => 1, 'start_time' => '09:00', 'end_time' => '20:00', 'setsubi_capacity' => 5, 'shimekiri' => '0', 'shimekiri_time' => '0']);

    // 山田 el 2026/12/01: 09:00-18:00, 1 persona, descanso 12:00-13:00
    $this->slot = ReservationSlot::create([
        'tb_staff_cd' => 10, 'moushikomi_date' => '2026/12/01', 'start_time' => 9 * 3600, 'end_time' => 18 * 3600,
        'people' => 1, 'start_break_time' => '12:00', 'end_break_time' => '13:00', 'hyouji_flg' => '1', 'disp_flg' => '1',
    ]);
    $this->sess = Str::random(40);
});

afterEach(fn () => Carbon::setTestNow());

/** POST con la cookie de sesión $this->sess (el `sess` del formulario es el id de sesión) */
function publicoPost(string $url, array $data = [], array $session = [], ?string $sessionId = null)
{
    $test = test();
    $sessionId ??= $test->sess;
    if ($session) {
        $test->withSession($session);
    }

    return $test->withCookie(config('session.cookie'), $sessionId)->post($url, $data + ['sess' => $sessionId]);
}

function publicoReserva(array $overrides = []): array
{
    return array_merge([
        'shop_cd' => '1', 'staff_cd' => '10', 'menu_cd' => ['100'], 'selectedDate' => '2026-12-01', 'selectedTime' => '10:00',
        'name' => '田中 花子', 'name_kana' => 'タナカハナコ', 'tel' => '0612345678', 'mail' => 'hanako@example.jp',
        'free_koumoku' => ['1' => '窓側'], 'customer_cd' => '', 'token' => '',
    ], $overrides);
}

it('carga r/index.php con las tiendas, el formulario y el id de sesión en sess', function () {
    $response = $this->withCookie(config('session.cookie'), $this->sess)->get('/r/index.php?shop_cd=1&staff_cd=10&menu_cd[]=100');

    $response->assertOk()
        ->assertSee('<title>Reserve</title>', false)
        ->assertSee('予約フォーム')
        ->assertSee('店舗選択')
        ->assertSee('<input type="radio" name="shop_cd" id="shop_cd_1" value="1"><label for="shop_cd_1">本店</label>', false)
        ->assertSee('name="sess" id="sess" value="'.$this->sess.'"', false)
        ->assertSee('name="start_date" id="start_date" value="2026-11-30"', false)
        ->assertSee('name="end_date" id="end_date" value="2026-12-06"', false)
        ->assertSee('var defaults = {"shop_cd":"1","staff_cd":"10","menu_cd":["100"]};', false)
        ->assertSee('ご要望')
        ->assertSee('name="free_koumoku[1]"', false)
        ->assertSee('/r/js/main.js', false);

    $this->get('/reserve/index.php')->assertOk()->assertSee('予約フォーム')->assertSee('href="css/main.css', false);
});

it('r/index.php responde como el legacy sin マイコレ o sin tienda con 店舗グループ', function () {
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 0]);
    expect($this->get('/r/index.php')->assertOk()->getContent())->toBe('不正アクセス。');

    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 1, 'shop_category_flg' => 1]);
    expect($this->get('/r/index.php')->getContent())->toBe('パラメータが正しくありません。');
});

it('ajax_getChoiceData.php devuelve empleados, menús y settings con las claves del legacy', function () {
    $response = publicoPost('/r/ajax_getChoiceData.php', ['shop_cd' => '1', 'start_date' => '2026-11-30', 'end_date' => '2026-12-06']);
    $body = $response->assertOk()->getContent();

    expect($body)->toStartWith('{ "status" : "ok", "data" : {"staff":[{"cd":"10","name":'.json_encode('山田').',"pic_name":null,"hasImage":false,"imgLastModify":0}]');
    $json = json_decode($body, true);
    expect(array_keys($json['data']))->toBe(['staff', 'menu', 'settings'])
        ->and($json['data']['menu'][0])->toBe(['tb_staff_cd' => '10', 'menu_cd' => '100', 'time' => '60', 'title' => 'カット', 'type_flg' => '1', 'category_name' => null, 'hasImage' => false, 'imgLastModify' => 0])
        ->and($json['data']['menu'][1]['menu_cd'])->toBe('101')
        ->and(array_keys($json['data']['settings']))->toBe([
            'showMenuPrice', 'showEstimatedTime', 'allowFreeReserve', 'allowNominationWhenFreeReserveIsAllowed', 'freeReserveName',
            'selectStaffAnnotation', 'selectMenuAnnotation', 'selectShopAnnotation', 'selectTimeAnnotation', 'customerFormAnnotation',
            'businessHoursReservationError',
        ]);

    // kind=staff: solo quien hace todos los menús marcados (y sin settings)
    $json = json_decode(publicoPost('/r/ajax_getChoiceData.php', ['shop_cd' => '1', 'start_date' => '2026-11-30', 'end_date' => '2026-12-06', 'kind' => 'staff', 'menu_cd' => ['100', '999']])->getContent(), true);
    expect($json['data'])->toBe(['staff' => []]);

    expect(publicoPost('/r/ajax_getChoiceData.php', ['shop_cd' => 'x'])->getContent())->toBe('{ "status" : "error", "errorMsg" : "不正アクセス" }');
});

it('ajax_getScheduleData.php devuelve los huecos con descansos, aforo y tiempo requerido', function () {
    $body = publicoPost('/r/ajax_getScheduleData.php', ['shop_cd' => '1', 'staff_cd' => '10', 'start_date' => '2026-12-01', 'end_date' => '2026-12-01', 'menu_cd' => ['100']])
        ->assertOk()->getContent();

    expect($body)->toStartWith('{ "status" : "ok", "data" : {"schedule":{"2026-12-01":{"youbi":'.json_encode('火').',"dow":"tue","weekNum":1,"flg":"1","startTime":"09:00",');
    $data = json_decode($body, true)['data'];
    $day = $data['schedule']['2026-12-01'];
    expect(array_keys($data))->toBe(['schedule', 'requiredTime', 'untilReserveDate'])
        ->and(array_keys($day))->toBe(['youbi', 'dow', 'weekNum', 'flg', 'startTime', 'startTimeUT', 'endTime', 'endTimeUT', 'timetable'])
        ->and($day['startTimeUT'])->toBe(Carbon::create(2026, 12, 1, 9, 0, 0, 'Asia/Tokyo')->getTimestamp())
        ->and($day['timetable']['10:00'])->toBe(['available' => true])
        ->and($day['timetable']['11:30']['available'])->toBeFalse() // 60 min chocan con el descanso
        ->and($day['timetable']['12:00']['available'])->toBeFalse()
        ->and($day['timetable']['13:00']['available'])->toBeTrue()
        ->and($day['timetable']['17:30']['available'])->toBeFalse() // pasa del fin de la franja
        ->and($data['requiredTime'])->toBe(['10' => 60])
        ->and($data['untilReserveDate'])->toBe('2027-11-30');

    expect(publicoPost('/r/ajax_getScheduleData.php', ['shop_cd' => '1'])->getContent())->toBe('{ "status" : "error", "errorMsg" : "不正アクセス。" }');
});

it('ajax_reserveRegister.php registra la reserva (entry_type 100), avisa por correo y llena el aforo', function () {
    $response = publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['menu_cd' => ['100', '101']]));

    expect($response->assertOk()->getContent())->toBe('{ "status" : "ok", "refreshSchedule" : true }');
    $reservation = Reservation::query()->sole();
    expect($reservation->tb_yoyaku_uketsuke_day_cd)->toBe($this->slot->cd)
        ->and($reservation->entry_type)->toBe('100')
        ->and($reservation->start_time)->toBe(10 * 3600)
        ->and($reservation->end_time)->toBe(11 * 3600 + 1800)
        ->and($reservation->base_tb_yoyaku_koumoku_master_cd)->toBe('100')
        ->and($reservation->option_tb_yoyaku_koumoku_master_cd)->toBe('101')
        ->and($reservation->name)->toBe('田中 花子')
        ->and($reservation->free_koumoku)->toBe(rawurlencode('窓側'))
        ->and($reservation->people)->toBe(1)
        ->and($reservation->tb_customer_cd)->toBeNull();
    Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->hasTo('hanako@example.jp'));
    expect(DB::table('log')->where('type1', 'V2予約')->where('type3', $reservation->cd)->exists())->toBeTrue();

    // Aforo de 山田 (1 persona) lleno a esa hora
    expect(publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['selectedTime' => '10:30']))->getContent())
        ->toBe('{ "status" : "error", "errorMsg" : "選択した日時の予約できる人数を超えた為登録できませんでした。お手数ですが別の日時を選択してください。", "refreshSchedule" : true }');

    // フリー予約 (sin staff_cd): entry_type 101
    expect(publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['staff_cd' => '', 'selectedTime' => '14:00']))->getContent())->toContain('"ok"');
    expect(Reservation::query()->where('start_time', 14 * 3600)->value('entry_type'))->toBe('101');
});

it('ajax_reserveRegister.php responde los errores del legacy', function () {
    expect(publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['sess' => 'otra']))->getContent())
        ->toBe('{ "status" : "error", "errorMsg" : "不正アクセス。" }')
        ->and(publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['menu_cd' => ['101']]))->getContent())
        ->toBe('{ "status" : "error", "errorMsg" : "オプションメニューのみで予約できません。" }')
        ->and(publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['name' => '', 'tel' => '', 'mail' => 'mal@']))->getContent())
        ->toBe('{ "status" : "error", "errorMsg" : "※お名前が入力されていません。<br />※メールアドレスが不適切な形式です。<br />※電話番号が入力されていません。<br />" }');
    expect(Reservation::query()->count())->toBe(0);
});

it('socio por token: lo ve en check.php y anula con ajax_reserveDelete.php', function () {
    $customer = Customer::factory()->create(['name' => '佐藤 一郎', 'email1' => 'ichiro@example.jp', 'tel' => '0900000000', 'touroku_kubun' => '2', 'disp_flg' => '1', 'touroku_date' => '1700000000']);
    $token = md5($customer->cd.':1700000000');

    $this->withCookie(config('session.cookie'), $this->sess)->get('/r/index.php?token='.$token)
        ->assertOk()->assertSee('佐藤 一郎　様')->assertSee('name="customer_cd" id="customer_cd" value="'.$customer->cd.'"', false);

    expect(publicoPost('/r/ajax_reserveRegister.php', publicoReserva(['customer_cd' => (string) $customer->cd, 'name' => '佐藤 一郎']))->getContent())->toContain('"ok"');
    $reservation = Reservation::query()->sole();
    expect($reservation->tb_customer_cd)->toBe((int) $customer->cd)->and($reservation->name)->toBeNull();

    $this->withCookie(config('session.cookie'), $this->sess)->get('/r/check.php?shop_cd=1')
        ->assertOk()
        ->assertSee('登録済み予約一覧')
        ->assertSee('2026-12-01(火) 10:00～')
        ->assertSee('本店')
        ->assertSee('山田')
        ->assertSee('カット')
        ->assertSee('cd="'.$reservation->cd.'"', false)
        ->assertSee('href="./index.php?shop_cd=1"', false);

    // Otra sesión sin socio no puede anularla
    expect(publicoPost('/r/ajax_reserveDelete.php', ['shop_cd' => '1', 'cd' => (string) $reservation->cd], ['mobile' => []], Str::random(40))->getContent())
        ->toBe('{ "status" : "error", "errorMsg" : "予約データがありません。" }');

    expect(publicoPost('/r/ajax_reserveDelete.php', ['shop_cd' => '1', 'cd' => (string) $reservation->cd], ['mobile' => ['user_cd' => (string) $customer->cd]])->getContent())
        ->toBe('{ "status" : "ok" }');
    expect(DB::table('tb_yoyaku')->where('cd', $reservation->cd)->value('disp_flg'))->toBe('9');
    Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->hasTo('ichiro@example.jp') && str_contains($mail->mailSubject, '取り消し'));

    $this->withSession(['mobile' => ['user_cd' => (string) $customer->cd]])->get('/reserve/check.php')->assertOk()->assertSee('予約がありません');
});

it('getReservationWaitTime.php devuelve la espera hasta el primer hueco o 予約できません。', function () {
    expect($this->get('/r/getReservationWaitTime.php?shop_cd=1')->getContent())->toBe('{ "status" : "error", "errorMsg" : "予約できません。" }');

    // クイック予約: menú 0 de 10 min; hoy 2026/12/01 a las 10:05
    Carbon::setTestNow(Carbon::create(2026, 12, 1, 10, 5, 0, 'Asia/Tokyo'));
    DB::table('tb_yoyaku_koumoku_master')->insert(['cd' => 0, 'name' => 'クイック', 'shoyou_time' => 10, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '1']);
    DB::table('tb_staff_works')->insert(['cd' => 3, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 0, 'shoyou_time' => 10, 'hyouji_flg' => '1', 'disp_flg' => '1']);
    $ut = Carbon::create(2026, 12, 1, 10, 30, 0, 'Asia/Tokyo')->getTimestamp();

    expect($this->get('/r/getReservationWaitTime.php?shop_cd=1')->getContent())
        ->toBe('{ "status" : "ok", "waitTime" : 25, "minReservationDatetime" : "2026-12-01 10:30", "minReservationUT" : '.$ut.' }');
});
