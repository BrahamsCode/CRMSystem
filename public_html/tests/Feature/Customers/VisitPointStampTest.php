<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Customers\VisitRecordService;
use App\Services\Customers\VisitRecordValidationException;
use App\Services\Customers\VisitService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// C3: 来店処理 (customer_search.php, raiten_register.php, ajax_raiten_*), 来店履歴の追加・修正 (kanren_record_*),
// puntos y sellos manuales de la ficha (point_register.php, stamp_register.php) y ポイント利用一覧 (point_history.php)

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** admin_master.related_features = '1<>tipo<>50' y la categoría 50 (varios registros) con un campo obligatorio y uno de casillas */
function clientesKanrenConfig(string $packType): void
{
    DB::table('admin_master')->where('cd', 1)->update(['related_features' => "1<>{$packType}<>50", 'point_shikkou_kikan' => '30']);
    DB::table('tb_search_cate')->insert(['cd' => 50, 'name' => '施術記録', 'category' => 'category1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'record_type' => '1', 'disp_column' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert([
        ['cd' => 60, 'tb_search_cate_cd' => 50, 'name' => 'メニュー名', 'form_type' => 'テキスト', 'hissu' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1, 'flds_row' => 0, 'data' => null],
        ['cd' => 61, 'tb_search_cate_cd' => 50, 'name' => '部位', 'form_type' => 'チェックボックス', 'hissu' => null, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 2, 'flds_row' => 3, 'data' => '肩<>腰<>首'],
    ]);
}

// ---------------------------------------------------------------- 来店処理

it('carga 来店処理 con los textos del legacy (también en su URL)', function () {
    $this->get(route('admin.clientes.visits'))->assertOk()->assertSee('■来店処理')->assertSee('会員番号')->assertSee('value="登録"', false);
    $this->get('/system/category1/customer/customer_search.php')->assertOk()->assertSee('■来店処理');
});

it('来店処理: valida el número, registra la visita y respeta el intervalo de la tienda', function () {
    DB::table('tb_touch_mode')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'mode' => '1', 'interval_time' => 3600]);
    $customer = Customer::factory()->create(['id' => '1100555', 'kojin_birth' => '1990/06/01']);

    $this->post(route('admin.clientes.visits.store'), ['regist_id' => 'abc'])->assertSee('入力される内容は数字のみでお願いいたします。');
    $this->post(route('admin.clientes.visits.store'), ['regist_id' => '999'])->assertSee('顧客が特定できませんでした。');

    $this->post('/system/category1/customer/customer_search.php', ['regist_id' => '1100555'])->assertOk()->assertSee('来店処理しました。');
    $visit = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->first();
    expect($visit->shop_cd)->toBe(1)
        ->and($visit->raiten_hiduke)->toBe('2026/05/20')
        ->and($visit->customer_age)->toBe(35)
        ->and(DB::table('tb_customer')->where('cd', $customer->cd)->value('last_raiten_date'))->toBe('2026/05/20');

    // Dentro de la インターバル no se registra
    $this->post(route('admin.clientes.visits.store'), ['regist_id' => '1100555'])->assertSee('来店処理できませんでした。インターバルなどをご確認ください。');
    expect(DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->count())->toBe(1);
});

it('VisitService::register con la implementación real de S3 da los sellos y puntos de raitenFunc', function () {
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 1, 'point_shikkou_kikan' => '30', 'stamp_shikkou_kikan' => '10']);
    DB::table('tb_shop')->where('cd', 1)->update(['login_default_point_settings' => serialize(['useFlg' => '1', 'earnPoints' => 5])]);
    DB::table('tb_stamp_config')->insert(['cd' => 1, 'stamp_max' => 10, 'limit_flg' => '0']);
    // Reglas de la tienda: 3 sellos por visita; puntos ×2 en esta franja horaria
    DB::table('tb_stamp_rules')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'stamp_count' => 3, 'hyouji_flg' => '1']);
    DB::table('tb_point_rules')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 2, 'hyouji_flg' => '1']);
    $customer = Customer::factory()->create(['stamp_count' => 8, 'last_stamp_time' => 0]);

    $visit = app(VisitService::class)->register($customer, 1, VisitService::ORIGIN_TERMINAL, 5, 10);

    $row = DB::table('tb_customer')->where('cd', $customer->cd)->first();
    $stampLog = DB::table('tb_stamp_log')->where('tb_customer_cd', $customer->cd)->first();
    $pointLog = DB::table('tb_point_log')->where('tb_customer_cd', $customer->cd)->first();

    // Sellos: 3 de la regla, sin pasar del máximo (8 → 10); log action 1 con 来店処理
    expect($visit->stamp)->toBe(3)
        ->and($row->stamp_count)->toBe(10)
        ->and((int) $row->last_stamp_time)->toBe(now()->getTimestamp())
        ->and($stampLog->sounyu_stamp)->toBe(2)
        ->and($stampLog->shori_mae_stamp)->toBe(8)
        ->and($stampLog->action_flg)->toBe('1')
        ->and($stampLog->raiten_shori)->toBe('1')
        ->and($stampLog->tb_terminal_cd)->toBe(5)
        ->and($stampLog->tb_staff_cd)->toBe(10);

    // Puntos de check-in: regla (×2) × earnPoints (5), sin personal
    expect($pointLog->sounyu_point)->toBe(10)
        ->and($pointLog->action_flg)->toBe('mycolle:checkIn')
        ->and($pointLog->tb_staff_cd)->toBeNull()
        ->and((int) DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->value('service_point'))->toBe(10);
});

it('raiten_register.php: alta manual de varias visitas y borrado con recálculo de 来店周期', function () {
    $customer = Customer::factory()->create();
    DB::table('tb_raiten_rireki')->insert(['cd' => 900, 'user_cd' => $customer->cd, 'shop_cd' => 1, 'raiten_time' => (string) Carbon::parse('2026-05-01 10:00', 'Asia/Tokyo')->timestamp, 'raiten_hiduke' => '2026/05/01']);

    $this->get('/system/category1/inc/raiten_register.php?' . http_build_query([
        'yy' => '2026', 'mm' => '05', 'dd' => '11', 'hh' => '03', 'ii' => '30', 'raiten_shop_cd' => '1',
        'kaiin_cd' => $customer->cd, 'raiten_action' => '来店履歴処理実行', 'raiten_count' => '2',
    ]))->assertOk();

    $new = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->where('manual_flg', '1')->get();
    // Antes de las 05:00 cuenta como el día anterior
    expect($new)->toHaveCount(2)
        ->and($new[0]->raiten_hiduke)->toBe('2026/05/10')
        ->and($new[0]->stamp)->toBe(0);
    $row = DB::table('tb_customer')->where('cd', $customer->cd)->first();
    expect($row->last_raiten_date)->toBe('2026/05/10')->and($row->shuki_heikin)->toBe(9);

    foreach ($new as $visit) {
        $this->get('/system/category1/inc/raiten_register.php?' . http_build_query(['raiten_cd' => $visit->cd, 'kaiin_cd' => $customer->cd, 'raiten_action' => '来店履歴削除']))->assertOk();
    }
    $row = DB::table('tb_customer')->where('cd', $customer->cd)->first();
    expect(DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->where('disp_flg', '9')->count())->toBe(2)
        ->and($row->last_raiten_date)->toBe('2026/05/01')
        ->and($row->shuki_heikin)->toBeNull();
});

it('ajax_raiten_list.php y ajax_raiten_time_select.php devuelven el HTML que esperan los JS', function () {
    $customer = Customer::factory()->create();
    DB::table('tb_raiten_rireki')->insert(['cd' => 901, 'user_cd' => $customer->cd, 'shop_cd' => 1, 'raiten_time' => (string) Carbon::parse('2026-05-03 18:05', 'Asia/Tokyo')->timestamp, 'raiten_hiduke' => '2026/05/03']);

    $this->post('/system/category1/inc/ajax_raiten_list.php', ['kaiin_cd' => $customer->cd, 'page' => 1])->assertOk()
        ->assertSee('来店日時')->assertSee('2026/05/03 18:05&nbsp;(日)', false)->assertSee('本店')
        ->assertSee("setRaitenTimeSelect('2026', '05', '03', '18', '05');setRaiten(901, 1);", false);

    $this->post('/system/category1/inc/ajax_raiten_time_select.php', ['y' => '2026', 'm' => '5', 'd' => '3', 'h' => '8', 'i' => '7'])->assertOk()
        ->assertSee('<option value="2026" selected>2026</option>', false)
        ->assertSee('<option value="05" selected>05</option>', false)
        ->assertSee('<option value="07" selected>07</option>', false);
});

// ---------------------------------------------------------------- 来店履歴の追加・修正

it('carga el formulario 来店履歴の追加・修正 con los textos del legacy', function () {
    clientesKanrenConfig('1');
    $customer = Customer::factory()->create(['name' => '佐藤 一郎']);

    $this->post(route('admin.clientes.visit-records.create'), ['kaiin_cd' => $customer->cd, 'tab' => '9', 'act' => 'new'])->assertOk()
        ->assertSee('■顧客登録情報')->assertSee('佐藤 一郎')->assertSee('来店日時')->assertSee('現時刻で新規来店とする')
        ->assertSee('既に取得済みの来店履歴から流用する')->assertSee('ポイント管理')->assertSee('施術記録')->assertSee('メニュー名')
        ->assertSee('この内容で登録する')->assertSee('/system/category1/inc/ajax_shop_point_config.php', false);
});

it('valida la fecha y los campos obligatorios con los mensajes del legacy', function () {
    clientesKanrenConfig('1');
    $customer = Customer::factory()->create();

    $this->post(route('admin.clientes.visit-records.store'), ['kaiin_cd' => $customer->cd, 'tab' => '9', 'add_record_verify' => '1', 'raiten_y' => '2026', 'raiten_m' => '02', 'raiten_d' => '30'])
        ->assertOk()->assertSee('※日付の入力をご確認ください。')->assertSee('※必須項目です。');
    expect(DB::table('tb_raiten_rireki')->count())->toBe(0);
});

it('alta con puntos: movimiento, saldo, visita y campos 関連情報 en su grupo; corrección y borrado', function () {
    clientesKanrenConfig('1');
    DB::table('tb_shop')->where('cd', 1)->update(['shiyou_point_jyougen' => 50, 'point_percentage_genkin' => 5, 'point_percentage_card' => 2, 'keisan_flg' => '1']);
    $customer = Customer::factory()->create();
    DB::table('tb_customer_point')->insert(['cd' => $customer->cd, 'tb_customer_cd' => $customer->cd, 'service_point' => 80, 'point_yukoukigen' => 0]);

    $post = fn (array $extra) => $this->post(route('admin.clientes.visit-records.store'), $extra + [
        'kaiin_cd' => $customer->cd, 'tab' => '9', 'act' => 'new', 'selected_shop_cd' => '1',
        'raiten_y' => '2026', 'raiten_m' => '05', 'raiten_d' => '19', 'raiten_h' => '14', 'raiten_i' => '30',
        'form60' => ' ｶｯﾄ ', 'form61_1' => '腰',
    ]);

    // 会計 1000, 使用 100 (tope de la tienda 50), efectivo 5%: 差引 950 → 47 pt; 手動 10 減算
    $post(['add_record_verify' => '111', 'kaikei' => '1000', 'shouhi_point' => '100', 'shiharai_houhou' => '1', 'sounyu_point' => '10', 'sounyu_point_add_remove_flg' => '2'])
        ->assertOk()->assertSee('登録中です。');

    $visit = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->first();
    $log = DB::table('tb_point_log')->where('cd', $visit->tb_point_log_cd)->first();
    expect($visit->manual_flg)->toBe('1')
        ->and($visit->tb_search_cate_cd)->toBe(50)
        ->and($visit->tb_customer_sub_inner_group)->toBe('1')
        ->and($visit->raiten_hiduke)->toBe('2026/05/19')
        ->and($log->shouhi_point)->toBe(50)
        ->and($log->sashihiki_kaikei)->toBe(950)
        ->and($log->jidou_point)->toBe(47)
        ->and($log->sounyu_point)->toBe(-10)
        ->and($log->shori_mae_point)->toBe(80)
        ->and($log->tb_staff_cd)->toBe(1)
        ->and($log->tb_terminal_cd)->toBe(-1)
        ->and($log->raiten_shori)->toBe('1');
    $balance = DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->first();
    expect((int) $balance->service_point)->toBe(80 - 50 + 47 - 10)
        ->and((int) $balance->point_yukoukigen)->toBe(Carbon::parse('2026-06-18 23:59:59', 'Asia/Tokyo')->timestamp);
    $subs = DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->orderBy('tb_search_form_cd')->get();
    expect($subs)->toHaveCount(2)
        ->and($subs[0]->text_data)->toBe('カット')
        ->and($subs[0]->category_inner_group)->toBe('1')
        ->and($subs[0]->form_title)->toBe('施術記録<>メニュー名')
        ->and($subs[1]->text_data)->toBe('腰<>');

    // El mismo add_record_verify (doble clic) no registra otra vez
    $post(['add_record_verify' => '111', 'kaikei' => '1000'])->assertOk();
    expect(DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->count())->toBe(1);

    // Corrección: fecha, tienda y campos; no toca el movimiento ni el saldo
    $post(['add_record_verify' => '222', 'act' => 'up', 'related_features_cd' => (string) $visit->cd, 'raiten_d' => '18', 'form60' => 'カラー'])->assertOk();
    $updated = DB::table('tb_raiten_rireki')->where('cd', $visit->cd)->first();
    expect($updated->raiten_hiduke)->toBe('2026/05/18')
        ->and(DB::table('tb_point_log')->count())->toBe(1)
        ->and(DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)->where('tb_search_form_cd', 60)->value('text_data'))->toBe('カラー')
        ->and(DB::table('tb_customer')->where('cd', $customer->cd)->value('last_raiten_date'))->toBe('2026/05/18');

    // El formulario de corrección muestra el movimiento y los valores guardados
    $this->post(route('admin.clientes.visit-records.create'), ['kaiin_cd' => $customer->cd, 'tab' => '9', 'act' => 'up', 'related_features_cd' => $visit->cd])
        ->assertOk()->assertSee('自動計算で')->assertSee('value="カラー"', false)->assertDontSee('既に取得済みの来店履歴から流用する');

    // Segundo alta → siguiente category_inner_group
    $post(['add_record_verify' => '333'])->assertOk();
    expect(DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->orderByDesc('cd')->value('tb_customer_sub_inner_group'))->toBe('2');

    // Borrado: visita, movimiento y campos ocultos; el saldo no cambia
    $this->post(route('admin.clientes.visit-records.destroy'), ['kaiin_cd' => $customer->cd, 'tab' => '9', 'related_features_cd' => $visit->cd])
        ->assertOk()->assertSee('削除中です。');
    expect(DB::table('tb_raiten_rireki')->where('cd', $visit->cd)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_point_log')->where('cd', $visit->tb_point_log_cd)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_customer_sub')->where('category_inner_group', '1')->where('disp_flg', '9')->count())->toBe(2)
        ->and((int) DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->value('service_point'))->toBe(67);
});

it('alta con sellos: 還元 de la tienda × regla del día, máximo y log action_flg 8 (servicio directo, como setRaitenDetail)', function () {
    clientesKanrenConfig('2');
    DB::table('tb_stamp_config')->insert(['cd' => 1, 'stamp_max' => 10]);
    DB::table('tb_shop')->where('cd', 1)->update(['stamp_percentage_genkin' => 500, 'stamp_percentage_card' => 1000]);
    DB::table('tb_stamp_rules')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'type_flg' => '5', 'start_time' => Carbon::parse('2026-05-19', 'Asia/Tokyo')->timestamp, 'end_time' => Carbon::parse('2026-05-20', 'Asia/Tokyo')->timestamp, 'stamp_count' => 2, 'hyouji_flg' => '1']);
    $customer = Customer::factory()->create(['stamp_count' => 3, 'last_stamp_time' => 100]);

    $cd = app(VisitRecordService::class)->save($customer, [
        'raiten_y' => '2026', 'raiten_m' => '05', 'raiten_d' => '19', 'raiten_h' => '10', 'raiten_i' => '00',
        'kaikei' => '1500', 'shiharai_houhou' => '1', 'sounyu_point' => '9', 'form60' => 'ネイル',
    ], null);

    $visit = DB::table('tb_raiten_rireki')->where('cd', $cd)->first();
    $log = DB::table('tb_stamp_log')->where('cd', $visit->tb_stamp_log_cd)->first();
    // floor(1500 / 500) × 2 = 6 → 3 + 6 = 9; 手動 9 limitado a 1 por el máximo 10
    expect($log->jidou_stamp)->toBe(6)
        ->and($log->sounyu_stamp)->toBe(1)
        ->and($log->action_flg)->toBe('8')
        ->and($log->tb_staff_cd)->toBeNull()
        ->and($visit->shop_cd)->toBe(1)
        ->and(DB::table('tb_customer')->where('cd', $customer->cd)->value('stamp_count'))->toBe(10)
        ->and((int) DB::table('tb_customer')->where('cd', $customer->cd)->value('last_stamp_time'))->toBe(100);

    expect(fn () => app(VisitRecordService::class)->save($customer, ['raiten_y' => '', 'form60' => '']))
        ->toThrow(VisitRecordValidationException::class);
    try {
        app(VisitRecordService::class)->save($customer, ['raiten_y' => '']);
    } catch (VisitRecordValidationException $e) {
        expect($e->errors())->toBe(['raiten' => '※日付の入力をご確認ください。', 'form60' => '※必須項目です。']);
    }
});

it('ajax_shop_point_config.php y ajax_shop_stamp_config.php con los valores por defecto del legacy', function () {
    $this->get('/system/category1/inc/ajax_shop_point_config.php?shop_cd=1')->assertOk()
        ->assertExactJson(['shop_name' => '本店', 'keisan_flg' => '1', 'shiyou_point_jyougen' => 1000, 'point_percentage_genkin' => 1, 'point_percentage_card' => 1]);
    $this->get('/system/category1/inc/ajax_shop_stamp_config.php?shop_cd=1&datetime=2026,05,19,10,05')->assertOk()
        ->assertExactJson(['shop_name' => '本店', 'stamp_percentage_genkin' => 1000, 'stamp_percentage_card' => 1000, 'stamp_count' => 1, 'date_disp' => '2026/05/19 10:05']);
});

it('la pestaña 9 de la ficha envía a las rutas de 来店履歴の追加・修正', function () {
    clientesKanrenConfig('1');
    $customer = Customer::factory()->create();
    DB::table('tb_raiten_rireki')->insert(['cd' => 902, 'user_cd' => $customer->cd, 'shop_cd' => 1, 'raiten_time' => (string) now()->timestamp, 'raiten_hiduke' => '2026/05/20']);

    $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '9']))->assertOk()
        ->assertSee('action="' . route('admin.clientes.visit-records.create') . '"', false)
        ->assertSee('action="' . route('admin.clientes.visit-records.destroy') . '"', false);
});

// ---------------------------------------------------------------- puntos y sellos manuales

it('point_register.php y stamp_register.php: suman y restan con las respuestas del legacy', function () {
    $customer = Customer::factory()->create(['stamp_count' => 2]);
    DB::table('tb_stamp_config')->insert(['cd' => 1, 'stamp_max' => 10]);

    $this->get('/system/category1/inc/point_register.php?point_qty=30&point_action_type=1&kaiin_cd=' . $customer->cd)->assertOk()->assertContent('OK<>30ポイントを追加しました。');
    $this->get('/system/category1/inc/point_register.php?point_qty=5&point_action_type=2&kaiin_cd=' . $customer->cd)->assertContent('OK<>5ポイントを削減しました。');
    $this->get('/system/category1/inc/point_register.php?point_qty=0&point_action_type=1&kaiin_cd=' . $customer->cd)->assertContent('');
    $log = DB::table('tb_point_log')->where('tb_customer_cd', $customer->cd)->orderBy('cd')->first();
    expect((int) DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->value('service_point'))->toBe(25)
        ->and($log->tb_shop_cd)->toBe(1)->and($log->tb_staff_cd)->toBe(1)->and($log->tb_terminal_cd)->toBe(-1);

    $this->get('/system/category1/inc/stamp_register.php?stamp_qty=3&stamp_action_type=1&oshirase_mail_flg=0&kaiin_cd=' . $customer->cd)->assertContent('OK<>3ポイントを追加しました。');
    $this->get('/system/category1/inc/stamp_register.php?stamp_qty=9&stamp_action_type=2&oshirase_mail_flg=0&kaiin_cd=' . $customer->cd)->assertContent('ERROR<>9ポイントを削減することは出来ません！！');
    $this->get('/system/category1/inc/stamp_register.php?stamp_qty=1&stamp_action_type=2&oshirase_mail_flg=0&kaiin_cd=' . $customer->cd)->assertContent('OK<>1ポイントを削減しました。');

    $logs = DB::table('tb_stamp_log')->where('tb_customer_cd', $customer->cd)->orderBy('cd')->get();
    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('stamp_count'))->toBe(4)
        ->and($logs)->toHaveCount(2)
        ->and($logs[0]->sounyu_stamp)->toBe(3)->and($logs[0]->action_flg)->toBe('4')->and($logs[0]->shori_mae_stamp)->toBe(2)
        ->and($logs[1]->shouhi_stamp)->toBe(1);
});

// ---------------------------------------------------------------- ポイント利用一覧

it('ポイント利用一覧: carga, filtra y descarga el CSV en SJIS-win', function () {
    $a = Customer::factory()->create(['id' => '1100601', 'login_id' => '1100601', 'name' => '田中 太郎']);
    $b = Customer::factory()->create(['id' => '1100602', 'login_id' => '1100602', 'name' => '鈴木 花子']);
    $t = Carbon::parse('2026-05-10 10:00', 'Asia/Tokyo')->timestamp;
    DB::table('tb_point_log')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'tb_customer_cd' => $a->cd, 'touroku_date' => $t, 'shori_mae_point' => 100, 'sounyu_point' => 20, 'shouhi_point' => null, 'action_flg' => null],
        ['cd' => 2, 'tb_shop_cd' => 1, 'tb_customer_cd' => $b->cd, 'touroku_date' => $t + 60, 'shori_mae_point' => 50, 'sounyu_point' => null, 'shouhi_point' => 30, 'action_flg' => null],
        ['cd' => 3, 'tb_shop_cd' => 1, 'tb_customer_cd' => $b->cd, 'touroku_date' => Carbon::parse('2025-01-01', 'Asia/Tokyo')->timestamp, 'shori_mae_point' => 0, 'sounyu_point' => 5, 'shouhi_point' => null, 'action_flg' => null],
    ]);

    $this->get(route('admin.clientes.point-history'))->assertOk()
        ->assertSee('■ポイント利用検索')->assertSee('利用前ポイント数')->assertSee('田中 太郎')->assertSee('鈴木 花子')->assertSee('1件～3件目/3件中');
    $this->get('/system/category1/customer/point_history.php')->assertOk()->assertSee('■ポイント利用検索');

    $this->post(route('admin.clientes.point-history'), ['search_type' => '1', 'texto' => '消費', 'from_point_year' => '2026', 'from_point_month' => '05'])
        ->assertOk()->assertSee('鈴木 花子')->assertDontSee('田中 太郎')->assertSee('1件～1件目/1件中');
    $this->post(route('admin.clientes.point-history'), ['search_type' => '2', 'to_point_year' => '2025'])
        ->assertOk()->assertSee('ポイント付与日')->assertSee('20250101')->assertSee('1件～1件目/1件中');

    $response = $this->post(route('admin.clientes.point-history.csv', ['search_type' => '1', 'from_point_year' => '2026']), ['csv_print' => '1']);
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');
    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
    expect(explode("\n", $csv))->toBe([
        'ID,名前,電話番号,登録店舗,ポイント利用日,利用前ポイント数,利用後ポイント数,ポイント利用種別,利用ポイント数',
        '1100602,鈴木 花子,' . $b->tel . ',本店,2026年05月10日,50,20,ポイント消費,30',
        '1100601,田中 太郎,' . $a->tel . ',本店,2026年05月10日,100,120,ポイント追加,20',
    ]);
});
