<?php

require_once __DIR__ . '/helpers.php';

use App\Mail\Reservas\ReservationMail;
use App\Models\Customers\Customer;
use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Flujo de reserva de la administración (legacy category9/yoyaku: yoyaku.php, yoyaku_calendar.php,
// yoyaku_customer_select.php, yoyaku_register.php, ajax_yoyaku_register.php, ajax_move_objs.php,
// ajax_yoyaku_menu.php, ajax_staff.php, ajax_search_kaiin.php, related_customer.php, ajax_related_customer.php)

beforeEach(function () {
    reservasLogin();

    DB::table('tb_shop')->where('cd', 1)->update(['start_work_time' => '9<>0', 'email' => 'shop@example.jp']);
    DB::table('tb_staff')->where('cd', 10)->update(['email1' => 'yamada@example.jp']);
    DB::table('tb_staff')->insert(['cd' => 11, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'staff11', 'login_pass' => 'x', 'name' => '佐藤', 'disp_flg' => '1']);
    DB::table('admin_master')->where('cd', 1)->update(['yoyaku_time_interval' => '30']);

    // Formulario de reservas (tb_koumoku): カナ, 電話, メール y un campo libre
    $koumoku = fn (int $cd, string $field, string $name, string $disp = '1') => ['cd' => $cd, 'system_cate' => '9', 'tb_name' => 'tb_yoyaku', 'tb_koumoku' => $field, 'tb_koumoku_name' => $name, 'koumoku_disp' => $disp, 'koumoku_check' => '', 'rank' => $cd];
    DB::table('tb_koumoku')->insert([
        $koumoku(1, 'name', 'お名前'), $koumoku(2, 'name_kana', 'フリガナ'), $koumoku(3, 'tel', '電話番号'),
        $koumoku(4, 'mail', 'メールアドレス'), $koumoku(5, 'free_koumoku_1', 'ご要望'),
    ]);

    // Menús: カット (base, 60 min) y シャンプー (opción, 30 min) asignados a 山田
    DB::table('tb_yoyaku_koumoku_master')->insert([
        ['cd' => 100, 'name' => 'カット', 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '1', 'rank' => 1, 'naiyou' => '基本'],
        ['cd' => 101, 'name' => 'シャンプー', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1', 'tb_shop_cd' => 1, 'type_flg' => '2', 'rank' => 2, 'naiyou' => ''],
    ]);
    DB::table('tb_staff_works')->insert([
        ['cd' => 1, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 100, 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 2, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 101, 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1'],
    ]);

    ReservationConfig::create(['tb_shop_cd' => 1, 'start_time' => '09:00', 'end_time' => '20:00']);

    // Franja de 山田 el 2026/12/01: 09:00-18:00, 2 personas, descanso 12:00-13:00
    $this->slot = ReservationSlot::create([
        'tb_staff_cd' => 10, 'moushikomi_date' => '2026/12/01', 'start_time' => 9 * 3600, 'end_time' => 18 * 3600,
        'people' => 2, 'start_break_time' => '12:00', 'end_break_time' => '13:00', 'hyouji_flg' => '1', 'disp_flg' => '1',
    ]);
    $this->kijun = Carbon::create(2026, 12, 1, 0, 0, 0, 'Asia/Tokyo')->getTimestamp();
});

function reservaInput(array $overrides = []): array
{
    return array_merge([
        'act' => 'new', 'shop' => '1', 'staff' => '10', 'yoyaku_date' => '202612011000', 'kijun_time' => '1',
        'menu' => ['100' => '1'], 'option_menu' => ['101' => '1'],
        'name' => 'ﾀﾅｶ 花子 ', 'name_kana' => 'タナカハナコ', 'tel' => '0612345678', 'mail' => 'hanako@example.jp',
        'free_koumoku' => ['1' => '窓側 希望'], 'people' => '1', 'comment' => '初回', 'register_verify' => '1001',
    ], $overrides);
}

it('carga 予約 (yoyaku.php) con los campos del formulario y el selector de tiendas del master', function () {
    $this->get(route('admin.reservas.create', ['shop' => 1, 'staff' => 10, 'td' => '20261201']))
        ->assertOk()
        ->assertSee('会員検索')
        ->assertSee('フリガナ')
        ->assertSee('ご要望')
        ->assertSee('スタッフを選択')
        ->assertSee('<option value="1">本店</option>', false)
        ->assertSee('name="kijun_time" value="' . $this->kijun . '"', false)
        ->assertSee('ajax_yoyaku_menu.php', false);
});

it('responde ajax_staff.php y ajax_yoyaku_menu.php como el legacy', function () {
    $this->post('/system/category9/yoyaku/ajax_staff.php', ['shop_cd' => 1])
        ->assertOk()
        ->assertSee('▼スタッフ選択')
        ->assertSee('<option value="10">山田</option>', false);
    $this->post('/system/category9/yoyaku/ajax_staff.php', ['shop_cd' => 99])
        ->assertSee('<option value="">登録されていません</option>', false);

    $this->post('/system/category9/yoyaku/ajax_yoyaku_menu.php', ['shop_cd' => 1, 'staff_cd' => 10, 'menu_cds' => '100'])
        ->assertOk()
        ->assertSee('ベースメニュー')
        ->assertSee('オプションメニュー')
        ->assertSee('カテゴリ設定無し')
        ->assertSee('name="menu[100]" value="1" checked', false)
        ->assertSee('name="option_menu[101]" value="1" />', false)
        ->assertSee('確定');
    $this->post('/system/category9/yoyaku/ajax_yoyaku_menu.php', ['shop_cd' => 1, 'staff_cd' => 11])
        ->assertSee('メニューデータはありません');
});

it('pinta 時間選択 con 予約可, descanso, 予約不可 y fuera de horario', function () {
    // 10:00-11:00 ya con 2 personas (lleno)
    Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 10 * 3600, 'end_time' => 11 * 3600, 'people' => 2, 'disp_flg' => '1', 'name' => '既存']);

    $response = $this->get(route('admin.reservas.calendar-select', [
        'shop' => 1, 'staff' => 10, 'menu' => ['100' => 1], 'option_menu' => ['101' => 1], 'people' => 1, 'kijun_time' => $this->kijun,
    ]))->assertOk()
        ->assertSee('2026年12月01日')
        ->assertSee('(火)')
        ->assertSee('本店 / 山田')
        ->assertSee('【カット')
        ->assertSee('設定内容・予約状況閲覧')
        ->assertSee('設定最大人数')
        ->assertSee('[予約可]')
        ->assertSee('[予約不可]')
        ->assertSee('[休憩・外出]')
        ->assertSee('yoyaku_date=202612011330', false)
        ->assertSee('/admin/reservas/slots/edit?cd=' . $this->slot->cd, false);

    // 09:00 cabe (90 min hasta 10:30 choca con la reserva llena de las 10:00 → 予約不可); 13:00 libre → 予約可
    $html = $response->getContent();
    expect(substr_count($html, '[予約可]'))->toBeGreaterThan(3)
        ->and(str_contains($html, 'yoyaku_date=202612010900'))->toBeTrue();

    $this->get(route('admin.reservas.calendar-select', ['shop' => 1, 'staff' => 10]))->assertSee('キーコードがありません。');

    // Sin franja: 新規設定を行う
    $this->get(route('admin.reservas.calendar-select', ['shop' => 1, 'staff' => 11, 'menu' => ['100' => 1], 'kijun_time' => $this->kijun]))
        ->assertOk()
        ->assertSee('新規設定を行う')
        ->assertDontSee('[予約可]');
});

it('carga 顧客情報入力 con el resumen de la reserva y ゲスト por defecto', function () {
    $this->get(route('admin.reservas.customer-select', [
        'shop' => 1, 'staff' => 10, 'menu' => ['100' => 1], 'option_menu' => ['101' => 1], 'yoyaku_date' => '202612010100', 'plus' => '1', 'kijun_time' => $this->kijun,
    ]))->assertOk()
        ->assertSee('顧客情報入力')
        ->assertSee('予約受付の旨をお客様にお知らせする')
        ->assertSee('計：90分')
        ->assertSee('2026年12月01日(火) 25時00分～')
        ->assertSee('value="ゲスト"', false)
        ->assertSee('name="menu[100]" value="1"', false)
        ->assertSee('name="plus" value="1"', false);

    $this->get(route('admin.reservas.customer-select', ['shop' => 1, 'staff' => 10]))->assertSee('キーコードがありません。');
});

it('registra la reserva (hatuban, form2Db, campos libres, duración de los menús) sin doble envío', function () {
    Mail::fake();

    $this->post(route('admin.reservas.store'), reservaInput())
        ->assertOk()
        ->assertSee('popUpWinReloadClose', false);

    $reservation = Reservation::sole();
    expect($reservation->cd)->toBe(1)
        ->and($reservation->tb_yoyaku_uketsuke_day_cd)->toBe($this->slot->cd)
        ->and($reservation->start_time)->toBe(36000)
        ->and($reservation->end_time)->toBe(36000 + 90 * 60)
        ->and($reservation->name)->toBe('タナカ 花子')
        ->and($reservation->base_tb_yoyaku_koumoku_master_cd)->toBe('100')
        ->and($reservation->option_tb_yoyaku_koumoku_master_cd)->toBe('101')
        ->and($reservation->freeFields())->toBe(['窓側 希望'])
        ->and($reservation->disp_flg)->toBe('1')
        ->and($reservation->touroku_date)->not->toBeEmpty();
    expect(DB::table('log')->where('type2', 'tb_yoyaku')->where('type3', 1)->exists())->toBeTrue();
    Mail::assertNothingSent(); // sin お知らせ

    // Mismo register_verify (F5 / doble clic): no se registra otra vez
    $this->post(route('admin.reservas.store'), reservaInput())->assertOk();
    expect(Reservation::count())->toBe(1);
});

it('avisa por correo al 担当 y a la お客様 con el texto del legacy (yoyaku_register.php)', function () {
    Mail::fake();

    $this->post(route('admin.reservas.store'), reservaInput(['oshirase' => '1', 'register_verify' => '2002']))->assertOk();

    Mail::assertSent(ReservationMail::class, function (ReservationMail $mail) {
        return $mail->hasTo('yamada@example.jp')
            && $mail->mailSubject === 'タナカ 花子様より予約登録申請'
            && str_contains($mail->body, "○予約日：\n2026年12月01日 10：00")
            && str_contains($mail->body, 'カット / シャンプー / ')
            && ! str_contains($mail->body, 'hanako@example.jp');
    });
    Mail::assertSent(ReservationMail::class, function (ReservationMail $mail) {
        return $mail->hasTo('hanako@example.jp')
            && $mail->mailSubject === '予約登録確認メール'
            && str_contains($mail->body, '以下の内容で予約登録を承りました。')
            && str_contains($mail->body, '約90分')
            && str_contains($mail->body, 'm/yoyaku/yoyaku_m.php?flow=10&yoyaku_cd=1&t=')
            && str_contains($mail->body, '本メールは配信専用です。');
    });
    Mail::assertSentCount(2);
});

it('al socio le manda un enlace firmado a Mi página solo si existe la ruta de S4 (nunca md5 de login)', function () {
    Mail::fake();
    $customer = Customer::factory()->create(['tb_shop_cd' => 1, 'name' => '会員 太郎', 'email1' => 'taro@example.jp', 'login_id' => 'taro', 'login_pass' => 'pass', 'touroku_kubun' => '2']);

    $this->post(route('admin.reservas.store'), reservaInput(['kaiin' => (string) $customer->cd, 'oshirase' => '1', 'register_verify' => '3003']))->assertOk();

    expect(Reservation::sole()->tb_customer_cd)->toBe($customer->cd)
        ->and(Reservation::sole()->name)->toBeNull();
    Mail::assertSent(ReservationMail::class, fn (ReservationMail $mail) => $mail->hasTo('taro@example.jp')
        && str_contains($mail->body, '→確認はログイン後、予約一覧からどうぞ')
        && ! str_contains($mail->body, 'login_pass') && ! str_contains($mail->body, 'user/login.php'));

    // Con la ruta de Mi página publicada, el enlace firmado
    Route::get('__test/mypage/{customer}', fn () => '')->name('publico.mypage.login');
    app('router')->getRoutes()->refreshNameLookups();
    Mail::fake();
    $this->post(route('admin.reservas.store'), reservaInput(['kaiin' => (string) $customer->cd, 'oshirase' => '1', 'register_verify' => '3004', 'yoyaku_date' => '202612011400']))->assertOk();
    Mail::assertSent(ReservationMail::class, fn (ReservationMail $mail) => $mail->hasTo('taro@example.jp')
        && str_contains($mail->body, '__test/mypage/' . $customer->cd . '?expires=')
        && str_contains($mail->body, 'signature='));
});

it('no deja pasar del aforo de la franja al registrar', function () {
    Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 10 * 3600, 'end_time' => 11 * 3600, 'people' => 2, 'disp_flg' => '1', 'name' => '既存']);

    $this->post(route('admin.reservas.store'), reservaInput(['yoyaku_date' => '202612011030', 'register_verify' => '4004']))
        ->assertRedirect()
        ->assertSessionHas('reservationError');
    expect(Reservation::count())->toBe(1);

    // A las 11:00 ya cabe
    $this->post(route('admin.reservas.store'), reservaInput(['yoyaku_date' => '202612011100', 'register_verify' => '4005']))->assertOk();
    expect(Reservation::count())->toBe(2);
});

it('modifica desde el popup del 予約表 (ajax_yoyaku_register.php): datos, día y empleado; comprueba sess', function () {
    $reservation = Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 36000, 'end_time' => 39600, 'people' => 1, 'disp_flg' => '1', 'name' => '旧', 'touroku_date' => '1', 'update_date' => '1']);
    $sessionId = Str::random(40);

    $this->get('/system/category9/yoyaku/ajax_yoyaku_register.php?free_koumoku[1]=' . rawurlencode('メモ') . '&' . http_build_query(['cd' => $reservation->cd, 'sess' => 'otra']))
        ->assertOk()->assertContent('不正アクセス！！');

    $this->withCookie(config('session.cookie'), $sessionId)
        ->get('/system/category9/yoyaku/ajax_yoyaku_register.php?free_koumoku[1]=' . rawurlencode('メモ') . '&' . http_build_query([
            'cd' => $reservation->cd, 'sess' => $sessionId, 'name' => '新 名前', 'name_kana' => 'シン', 'tel' => '090', 'mail' => '',
            'startTime' => '25:30', 'endTime' => '26:00', 'people' => '3', 'mark' => '★', 'comment' => 'メモ', 'base_menu' => '100', 'option_menu' => '',
            'tb_staff_cd' => '11', 'moushikomi_date' => '2026-12-02', 'checked_recalculation' => '0',
        ]))
        ->assertOk()
        ->assertContent('');

    $fresh = Reservation::find($reservation->cd);
    $slot = ReservationSlot::find($fresh->tb_yoyaku_uketsuke_day_cd);
    expect($fresh->name)->toBe('新 名前')
        ->and($fresh->start_time)->toBe(25 * 3600 + 1800)
        ->and($fresh->end_time)->toBe(26 * 3600)
        ->and($fresh->people)->toBe(3)
        ->and($fresh->freeFields())->toBe(['メモ'])
        ->and($fresh->update_date)->toBe('1')
        ->and($slot->tb_staff_cd)->toBe(11)
        ->and($slot->moushikomi_date)->toBe('2026/12/02')
        ->and($slot->start_time)->toBe(9 * 3600)   // franja nueva con el horario de 予約設定
        ->and($slot->end_time)->toBe(20 * 3600)
        ->and($slot->people)->toBe(1);

    // Baja (act=del)
    $this->withCookie(config('session.cookie'), $sessionId)
        ->get('/system/category9/yoyaku/ajax_yoyaku_register.php?' . http_build_query(['cd' => $reservation->cd, 'sess' => $sessionId, 'act' => 'del']))
        ->assertOk()->assertContent('');
    expect(Reservation::withDeleted()->find($reservation->cd)->disp_flg)->toBe('9');
});

it('mueve reservas a otro empleado (una, las del día, las de ese día en adelante) con ajax_move_objs.php', function () {
    $later = ReservationSlot::create(['tb_staff_cd' => 10, 'moushikomi_date' => '2026/12/05', 'start_time' => 32400, 'end_time' => 64800, 'people' => 2, 'disp_flg' => '1']);
    $a = Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 36000, 'end_time' => 39600, 'people' => 1, 'disp_flg' => '1']);
    $b = Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 50400, 'end_time' => 54000, 'people' => 1, 'disp_flg' => '1']);
    $c = Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $later->cd, 'start_time' => 36000, 'end_time' => 39600, 'people' => 1, 'disp_flg' => '1']);
    $staffOf = fn (Reservation $r) => ReservationSlot::find(Reservation::find($r->cd)->tb_yoyaku_uketsuke_day_cd)->tb_staff_cd;

    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $a->cd, 'newStaff' => 11, 'objs' => '1']))->assertContent('');
    expect($staffOf($a))->toBe(11)->and($staffOf($b))->toBe(10);

    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $b->cd, 'newStaff' => 11, 'objs' => 'today']))->assertContent('');
    expect($staffOf($b))->toBe(11)->and($staffOf($c))->toBe(10);

    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $b->cd, 'newStaff' => 10, 'objs' => 'all']))->assertContent('');
    expect($staffOf($a))->toBe(10)->and($staffOf($b))->toBe(10);

    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $c->cd, 'newStaff' => 'x']))->assertContent('ERROR:staff_cd');
});

it('cambia día y hora con ajax_move_objs.php y avisa con sendYoyakuMail(update)', function () {
    Mail::fake();
    $reservation = Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 36000, 'end_time' => 41400, 'people' => 1, 'disp_flg' => '1',
        'name' => '山本', 'mail' => 'yamamoto@example.jp', 'tel' => '03', 'base_tb_yoyaku_koumoku_master_cd' => '100', 'option_tb_yoyaku_koumoku_master_cd' => '101', 'free_koumoku' => rawurlencode('窓側')]);

    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $reservation->cd, 'reserveDate' => '2026/13/01', 'startTime' => '10:00']))
        ->assertContent('正しい開始日時を選択してください。');

    // Mismo día y hora: no cambia nada
    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $reservation->cd, 'reserveDate' => '2026/12/01', 'startTime' => '10:00', 'sendMailFlg' => '1']))
        ->assertContent('');
    Mail::assertNothingSent();

    $this->get('/system/category9/yoyaku/ajax_move_objs.php?' . http_build_query(['cd' => $reservation->cd, 'reserveDate' => '2026/12/01', 'startTime' => '14:30', 'sendMailFlg' => '1']))
        ->assertContent('');
    expect(Reservation::find($reservation->cd)->start_time)->toBe(14 * 3600 + 1800)
        ->and(Reservation::find($reservation->cd)->end_time)->toBe(14 * 3600 + 1800 + 5400);

    Mail::assertSent(ReservationMail::class, fn (ReservationMail $m) => $m->hasTo('shop@example.jp') && $m->mailSubject === '山本様より予約変更申請'
        && str_contains($m->body, "○予約日：\n2026年12月01日 14時30分") && str_contains($m->body, "　■ベースメニュー\n　カテゴリ無し\n　　カット(60分)"));
    Mail::assertSent(ReservationMail::class, fn (ReservationMail $m) => $m->hasTo('yamada@example.jp'));
    Mail::assertSent(ReservationMail::class, fn (ReservationMail $m) => $m->hasTo('yamamoto@example.jp') && $m->mailSubject === '予約変更確認メール'
        && str_contains($m->body, '以下の内容で予約を変更いたしました。') && str_contains($m->body, "○ご要望：\n窓側")
        && str_contains($m->body, '&shop_cd=1'));
});

it('liga la reserva a un socio (related_customer.php + ajax_related_customer.php) y busca socios', function () {
    $reservation = Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $this->slot->cd, 'start_time' => 36000, 'end_time' => 39600, 'people' => 1, 'disp_flg' => '1', 'name' => '鈴木', 'name_kana' => 'スズキ']);
    $customer = Customer::factory()->create(['tb_shop_cd' => 1, 'id' => 'A001', 'name' => '鈴木 一郎', 'touroku_kubun' => '2']);

    $this->get(route('admin.reservas.related-customer', ['yoyaku_cd' => $reservation->cd]))
        ->assertOk()
        ->assertSee('顧客関連づけ')
        ->assertSee('2026/12/01')
        ->assertSee('鈴木')
        ->assertSee('(スズキ)')
        ->assertSee('既存会員との紐づけ処理を実行しようとしています。')
        ->assertSee('ajax_related_customer.php', false);

    $this->post('/system/category9/yoyaku/ajax_search_kaiin.php', ['w' => '鈴木'])
        ->assertOk()
        ->assertSee('1件中/1件表示')
        ->assertSee('A001');

    $this->post('/system/category9/yoyaku/ajax_related_customer.php', ['yoyaku_cd' => $reservation->cd, 'customer_cd' => $customer->cd])
        ->assertOk()->assertContent('1');
    expect(Reservation::find($reservation->cd)->tb_customer_cd)->toBe($customer->cd);
});

it('publica ajax_menu.php y ajax_yoyaku_koumoku_category.php en sus URL del legacy', function () {
    $this->post('/system/category9/yoyaku/ajax_menu.php', ['shop_cd' => 1, 'staff_cd' => 10])->assertOk()->assertSee('カット');

    DB::table('tb_yoyaku_koumoku_category')->insert(['cd' => 1, 'name' => 'ヘア', 'rank' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1']);
    $this->post('/system/category9/yoyaku/ajax_yoyaku_koumoku_category.php')->assertOk()->assertSee('ヘア');
    $this->post('/system/category9/yoyaku/ajax_yoyaku_koumoku_category.php', ['send_type' => 'del', 'send_targetCd' => 1, 'send_vl' => 1])->assertOk()->assertDontSee('ヘア');
    expect(DB::table('tb_yoyaku_koumoku_category')->where('cd', 1)->value('disp_flg'))->toBe('9');
});
