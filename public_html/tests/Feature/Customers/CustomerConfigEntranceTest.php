<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// C7: 初期設定管理 (config/felica_mode.php, card_new.php, mail_comment*.php, schoolyear_*.php, schoolclass_*.php)
// y 入退室一覧 (entrance/working_calendar.php, work_list.php)

beforeEach(function () {
    reservasLogin();
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
});

afterEach(fn () => Carbon::setTestNow());

/** Epoch de una hora de Japón */
function clientesC7Epoch(string $at): string
{
    return (string) Carbon::parse($at, 'Asia/Tokyo')->getTimestamp();
}

// ---------------------------------------------------------------- カードリーダーモード設定

it('カードリーダーモード設定: carga en su URL legacy y, sin la fila cd = 2, la crea al guardar el modo y el intervalo', function () {
    expect(DB::table('tb_felica_mode')->where('cd', 2)->exists())->toBeFalse();

    $this->get('/system/category1/config/felica_mode.php')->assertOk()
        ->assertSee('■カードリーダーモード設定')->assertSee('運用モード')->assertSee('入室強制モード')->assertSee('退室強制モード')
        ->assertSee('カード重複読込時間間隔の設定')->assertSee('テスト')
        ->assertSee('value="0" checked', false);

    $this->post(route('admin.clientes.reader-mode.update'), ['felica_mode' => '1', 'felica_mode_up' => 'up'])
        ->assertRedirect(route('admin.clientes.reader-mode'));
    $row = DB::table('tb_felica_mode')->where('cd', 2)->first();
    expect($row->mode)->toBe('1')->and((int) $row->update)->toBe(now()->getTimestamp());

    $this->post(route('admin.clientes.reader-mode.update'), ['inter_time' => '1800', 'interval_time_action_act' => 'up']);
    $this->post(route('admin.clientes.reader-mode.update'), ['inter_time' => '99; drop', 'interval_time_action_act' => 'up']);
    expect(DB::table('tb_felica_mode')->where('cd', 2)->value('interval_time'))->toBe('1800');

    // Un modo desconocido guarda 運用 (0), como el legacy; la fila cd = 1 (S4) no se toca
    DB::table('tb_felica_mode')->insert(['cd' => 1, 'mode' => '0']);
    $this->post(route('admin.clientes.reader-mode.update'), ['felica_mode' => 'x', 'felica_mode_up' => 'up']);
    expect(DB::table('tb_felica_mode')->where('cd', 2)->value('mode'))->toBe('0')
        ->and(DB::table('tb_felica_mode')->count())->toBe(2);

    $this->get(route('admin.clientes.reader-mode'))->assertOk()
        ->assertSee('<li class="modeSelected" onclick="modeSelect(0)">運用モード</li>', false)
        ->assertSee('<option value="1800" selected="selected">30分</option>', false);
});

// ---------------------------------------------------------------- カード管理

it('カード管理: lista tb_user_card con filtro, elige socio sin tarjeta y la asigna (tb_customer, tb_user_card, tb_play_time)', function () {
    $owner = Customer::factory()->create(['id' => '1100100', 'name' => '持主太郎', 'felica_no' => 'AAA111']);
    $free = Customer::factory()->create(['id' => '1100200', 'name' => '生徒花子', 'felica_no' => '', 'tel' => '090-0000-0000', 'email1' => 'hanako@example.com']);
    DB::table('tb_user_card')->insert([
        ['cd' => 1, 'card_no' => 'C-01', 'felica_no' => 'AAA111', 'staff_cd' => (string) $owner->cd, 'update' => clientesC7Epoch('2026-04-01 10:00'), 'card_flg' => '1'],
        ['cd' => 2, 'card_no' => 'C-02', 'felica_no' => 'BBB222', 'staff_cd' => '', 'update' => clientesC7Epoch('2026-05-02 10:00'), 'card_flg' => '0'],
    ]);
    DB::table('tb_play_time')->insert(['cd' => 1, 'staff_cd' => $free->cd, 'syukkinbi' => clientesC7Epoch('2026-05-01 00:00'), 'felica_no' => '']);

    $this->get('/system/category1/config/card_new.php')->assertOk()
        ->assertSee('■カード管理')->assertSee('<span class="text3L attention">2</span>件見つかりました。', false)
        ->assertSee('持主太郎')->assertSee('2026/05/02')->assertSee('value="生徒登録"', false)->assertSee('登録モード');

    $this->get(route('admin.clientes.cards', ['card_flg' => '0']))->assertOk()
        ->assertSee('BBB222')->assertDontSee('AAA111');

    $this->post(route('admin.clientes.cards.select'), ['felica_no' => 'BBB222', 'card_no' => 'C-02', 'felica_add' => 'up'])->assertOk()
        ->assertSee('■生徒選択一覧')->assertSee('カードナンバー:<span class="attention">C-02</span>', false)
        ->assertSee('生徒花子')->assertSee('hanako@...')->assertDontSee('持主太郎');

    $this->post(route('admin.clientes.cards.store'), ['staff_cd' => $free->cd, 'felica_no' => 'BBB222', 'felica_set_flg' => 'up'])
        ->assertRedirect(route('admin.clientes.cards'));
    expect(DB::table('tb_customer')->where('cd', $free->cd)->value('felica_no'))->toBe('BBB222')
        ->and((array) DB::table('tb_user_card')->where('cd', 2)->first(['card_flg', 'staff_cd']))->toBe(['card_flg' => '1', 'staff_cd' => (string) $free->cd])
        ->and(DB::table('tb_play_time')->where('cd', 1)->value('felica_no'))->toBe('BBB222');

    // Otra vez la misma tarjeta a un socio que ya tiene: mensaje del legacy y nada cambia
    $this->post(route('admin.clientes.cards.store'), ['staff_cd' => $owner->cd, 'felica_no' => 'BBB222', 'felica_set_flg' => 'up'])->assertOk()
        ->assertSee('カード <font color=red>AAA111</font> は既に登録されているカードです。ご確認ください。', false);
    expect(DB::table('tb_customer')->where('cd', $owner->cd)->value('felica_no'))->toBe('AAA111');

    // 登録モード desde カード管理
    $this->post(route('admin.clientes.cards.mode'), ['felica_mode' => '9', 'felica_mode_up' => 'up'])->assertRedirect(route('admin.clientes.cards'));
    expect(DB::table('tb_felica_mode')->where('cd', 2)->value('mode'))->toBe('9');
    $this->get(route('admin.clientes.cards'))->assertSee('<li class="modeSelected" onclick="modeSelect(3)">登録モード</li>', false);
});

// ---------------------------------------------------------------- メールコメント

it('メールコメント: guarda los dos comentarios con get_form2() en la fila cd = 2 y exige ambos', function () {
    $this->get('/system/category1/config/mail_comment.php')->assertOk()
        ->assertSee('■メールコメント登録')->assertSee('入室コメント')->assertSee('※{生徒名}と入力された箇所には生徒の名前が挿入されます。');

    $this->post(route('admin.clientes.mail-comment.update'), ['nyushitsu_komento' => '入室', 'taishitsu_komento' => ''])
        ->assertRedirect(route('admin.clientes.mail-comment'));
    expect(DB::table('tb_felica_mode')->where('cd', 2)->exists())->toBeFalse();

    $this->post(route('admin.clientes.mail-comment.update'), ['nyushitsu_komento' => ' {生徒名}さんが{時間}に入室 "A&B" ', 'taishitsu_komento' => '{生徒名}さんが退室'])
        ->assertOk()->assertSee('登録中です。');
    $row = DB::table('tb_felica_mode')->where('cd', 2)->first();
    expect($row->nyushitsu_komento)->toBe('{生徒名}さんが{時間}に入室 &quot;A&amp;B&quot;')
        ->and($row->taishitsu_komento)->toBe('{生徒名}さんが退室');

    $this->get(route('admin.clientes.mail-comment'))
        ->assertSee('{生徒名}さんが{時間}に入室 &quot;A&amp;B&quot;</textarea>', false)->assertDontSee('&amp;amp;', false);
});

// ---------------------------------------------------------------- 学年設定 / クラス設定

it('学年設定: alta con hatuban (cate_order = cd), edición y borrado; クラス設定 usa su propia tabla', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_schoolyear_config', 'kazu' => 5]);

    $this->get('/system/category1/config/schoolyear_config.php')->assertOk()
        ->assertSee('■学年登録')->assertSee('■学年一覧')->assertSee('学年は登録されていません。');

    $this->post(route('admin.clientes.school-years.store'), ['cate_name' => '   ', 'act' => 'new'])->assertRedirect();
    $this->post(route('admin.clientes.school-years.store'), ['cate_name' => '小学1年 <A>', 'act' => 'new'])->assertOk()->assertSee('登録中です。');
    $row = DB::table('tb_schoolyear_config')->first();
    expect($row->cd)->toBe(5)->and($row->cate_order)->toBe(5)->and($row->cate_name)->toBe('小学1年 &lt;A&gt;')->and($row->disp_flg)->toBe('1')
        ->and(DB::table('tb_schoolyear_config')->count())->toBe(1);

    $this->get(route('admin.clientes.school-years.index', ['cd' => 5]))->assertOk()
        ->assertSee('value="小学1年 &lt;A&gt;"', false)->assertSee('value="この内容で修正する"', false);
    $this->put(route('admin.clientes.school-years.update', 5), ['cate_name' => '小学2年', 'act' => 'up'])->assertOk();
    expect(DB::table('tb_schoolyear_config')->where('cd', 5)->value('cate_name'))->toBe('小学2年');

    $this->delete(route('admin.clientes.school-years.destroy', 5))->assertOk()->assertSee('削除中です。');
    expect(DB::table('tb_schoolyear_config')->where('cd', 5)->value('disp_flg'))->toBe('9');

    $this->post(route('admin.clientes.school-classes.store'), ['cate_name' => 'Aクラス', 'act' => 'new'])->assertOk();
    expect(DB::table('tb_schoolclass_config')->value('cate_name'))->toBe('Aクラス');
    $this->get('/system/category1/config/schoolclass_config.php')->assertOk()->assertSee('■クラス一覧')->assertSee('Aクラス');
});

// ---------------------------------------------------------------- 入退室一覧

it('入退室一覧: calendario con 休日 de la tienda y botón 表示 de cada día', function () {
    DB::table('tb_shop_work')->insert(['cd' => 1, 'shop_cd' => 1, 'syukkinbi' => '2026/5', 'syukkin_time_10' => '0']);

    $this->get('/system/category1/entrance/working_calendar.php?year=2026&mon=5')->assertOk()
        ->assertSee('■入退室一覧')->assertSee('2026年5月')->assertSee('2026年4月へ', false)->assertSee('2026年6月へ', false)
        ->assertSee('<td class="holidaySet"><span class="holiday">10</span>', false)
        ->assertSee('<span class="nowday">20</span>', false)
        ->assertSee('value="2026/5/31"', false)->assertSee('営業日数：30／31');

    // Vuelta desde la lista: dd fija el mes
    $this->get(route('admin.clientes.entries.calendar', ['dd' => '2025/12/3']))->assertOk()->assertSee('2025年12月');
});

it('入退室一覧: lista del día con entrada/salida de tb_play_time y la corrección (antes de las 5 = día siguiente)', function () {
    $customer = Customer::factory()->create(['id' => '1100300', 'name' => '入室一郎']);
    Customer::factory()->create(['id' => '1100400', 'name' => '未入室']);
    DB::table('tb_play_time')->insert([
        'cd' => 1, 'staff_cd' => $customer->cd, 'syukkinbi' => clientesC7Epoch('2026-05-01 00:00'),
        'syukkin_time_7' => clientesC7Epoch('2026-05-07 16:05'), 'taikin_time_7' => clientesC7Epoch('2026-05-07 18:30'),
        'syukkin_flg_7' => '入室', 'syukkin_kubun_7' => '退室',
    ]);

    $this->get('/system/category1/entrance/work_list.php?dd=2026/5/7')->assertOk()
        ->assertSee('■入退室一覧')->assertSee('2026年5月7日')->assertSee('1件～2件目/2件中', false)
        ->assertSee('入室一郎')->assertSee('16:05')->assertSee('18:30')->assertSee('未入室')
        ->assertSee('value="日付選択へ戻る"', false);

    $this->post(route('admin.clientes.entries.update'), [
        'dd' => '2026/5/7', 'scd' => $customer->cd, 'sh' => '09', 'sm' => '15', 'th' => '01', 'tm' => '30', 'sf' => '遅刻', 'sk' => '早退', 'kensu' => '20',
    ])->assertRedirect(route('admin.clientes.entries.index', ['dd' => '2026/5/7', 'kensu' => '20']));

    $row = DB::table('tb_play_time')->where('cd', 1)->first();
    expect($row->syukkin_time_7)->toBe(clientesC7Epoch('2026-05-07 09:15'))
        ->and($row->taikin_time_7)->toBe(clientesC7Epoch('2026-05-08 01:30'))
        ->and($row->syukkin_flg_7)->toBe('遅刻')
        ->and($row->syukkin_kubun_7)->toBe('早退');

    // Sin hora: queda vacío
    $this->post(route('admin.clientes.entries.update'), ['dd' => '2026/5/7', 'scd' => $customer->cd, 'sh' => '', 'sm' => '15', 'th' => '', 'tm' => '', 'sf' => '', 'sk' => '']);
    expect(DB::table('tb_play_time')->where('cd', 1)->value('syukkin_time_7'))->toBe('');
});
