<?php

require_once __DIR__ . '/helpers.php';

use App\Mail\Reservas\ReservationMail;
use App\Models\Customers\Customer;
use App\Models\Reservations\SimpleReservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// 予約管理 simple (legacy system/category3/reservation/: reserve_*, category_*, config*, ajax_*, model_reservation.php)

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 12, 1, 10, 30, 0, 'Asia/Tokyo'));
    reservasLogin();
    Mail::fake();

    DB::table('tb_shop')->where('cd', 1)->update(['email' => 'honten@example.jp']);
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '駅前店', 'disp_flg' => '1']);
    DB::table('tb_staff')->where('cd', 10)->update(['reserve_flg' => '1', 'email1' => 'yamada@example.jp']);
    DB::table('tb_koumoku')->insert([
        ['cd' => 301, 'system_cate' => '3', 'tb_name' => 'tb_reserve', 'tb_koumoku' => 'reserve_people', 'tb_koumoku_name' => '予約人数', 'koumoku_disp' => '1', 'koumoku_check' => '', 'rank' => 1],
        ['cd' => 302, 'system_cate' => '3', 'tb_name' => 'tb_reserve', 'tb_koumoku' => 'reserve_nomination', 'tb_koumoku_name' => '担当者指名', 'koumoku_disp' => '1', 'koumoku_check' => '', 'rank' => 2],
    ]);
    DB::table('tb_reserve_name')->insert([
        ['cd' => 1, 'reserve_cate_name' => 'カット', 'tb_shop_cd' => 1, 'disp_flg' => '1'],
        ['cd' => 2, 'reserve_cate_name' => '全店 相談', 'tb_shop_cd' => null, 'disp_flg' => '1'],
        ['cd' => 3, 'reserve_cate_name' => '駅前 限定', 'tb_shop_cd' => 2, 'disp_flg' => '1'],
        ['cd' => 4, 'reserve_cate_name' => '削除済', 'tb_shop_cd' => 1, 'disp_flg' => '9'],
    ]);
    DB::table('hatuban')->insert([['koumoku' => 'tb_reserve', 'kazu' => 50], ['koumoku' => 'tb_reserve_name', 'kazu' => 10]]);
});

afterEach(fn () => Carbon::setTestNow());

function simpleReserve(int $cd, array $overrides = []): void
{
    DB::table('tb_reserve')->insert(array_merge([
        'cd' => $cd, 'reserve_time' => (string) Carbon::create(2026, 12, 5, 14, 0, 0, 'Asia/Tokyo')->getTimestamp(),
        'reserve_cate' => '1', 'reserve_people' => '2', 'reserve_nomination' => '10', 'user_cd' => '', 'user_name' => '田中 &amp; 花子',
        'user_mail' => 'hana@example.jp', 'user_tel' => '0612345678', 'shop_cd' => '1', 'update' => '1', 'reserve_flg' => '1', 'raiten_flg' => '',
    ], $overrides));
}

function simpleValidInput(array $overrides = []): array
{
    return array_merge([
        'act' => 'new', 'mitouroku' => 'mitouroku', 'tb_shop_cd' => '1', 'name' => '田中 <花子>', 'tel' => '090-1111-2222',
        'email' => 'hana@example.jp', 'hiduke' => '2026/12/05', 'hour1' => '14', 'min1' => '30', 'ninsuu' => '2',
        'category_cd' => '2', 'staff_cd' => '10', 'reserve_cd' => '',
    ], $overrides);
}

it('reserve_customer_search: buscador y lista de socios con el botón 予約', function () {
    Customer::factory()->create(['cd' => 700, 'id' => 'M700', 'kanri_id' => 'K-7', 'name' => '会員 太郎', 'touroku_date' => '1764550000']);

    $this->get('/system/category3/reservation/reserve_customer_search.php')->assertOk()
        ->assertSee('顧客一覧・検索 - ', false)
        ->assertSee('■顧客検索')
        ->assertSee('非会員の予約')
        ->assertDontSee('■顧客一覧');

    $html = $this->post(route('admin.reservas.simple.customer-search'), ['act' => 'up', 'se_flg' => '1'])->assertOk()->getContent();
    expect($html)->toContain('■顧客一覧')->toContain('M700')->toContain('K-7')->toContain('会員 太郎')
        ->toContain('value="予約"')->toContain('name="kaiin_cd" id="kaiin_cd" value="700"')
        ->toContain('1件～1件目/1件中');
});

it('reserve_new → confirm → register → complete: alta de 非会員 con correos', function () {
    $form = $this->post('/system/category3/reservation/reserve_new.php', ['mitouroku' => 'mitouroku', 'act' => 'new'])->assertOk()->getContent();
    expect($form)->toContain('■予約新規登録')
        ->toContain('<option value="">▼店舗選択</option>')
        ->toContain('携帯電話番号')
        ->toContain('予約人数')
        ->toContain('指名')
        ->toContain('system/category3/reservation/ajax_category.php');

    // Errores con los textos del legacy
    $errors = $this->post(route('admin.reservas.simple.confirm'), simpleValidInput(['tb_shop_cd' => '', 'name' => '', 'email' => 'mal', 'hiduke' => '2026/02/30', 'ninsuu' => 'x']))
        ->assertOk()->getContent();
    expect($errors)->toContain('※必ず選択して下さい。')->toContain('※必須入力項目です。')->toContain('※不適切な形式です。')
        ->toContain('※2026/02/30は不適切な形式です。')->toContain('※半角数字で入力して下さい。')->toContain(' class="Ng"')
        ->toContain('<div class="contents form">');

    // Confirmación: 予約内容 de 全店舗 y 指名
    $confirm = $this->post(route('admin.reservas.simple.confirm'), simpleValidInput())->assertOk()->getContent();
    expect($confirm)->toContain('value="書き直す"')->toContain('この内容で予約する')
        ->toContain('全店 相談')->toContain('山田')->toContain('14時30分')->toContain('2人')
        ->toContain('田中 &lt;花子&gt;')
        ->toContain('name="tel" value="09011112222"');

    // Registro
    $register = $this->post('/system/category3/reservation/reserve_new_register.php', simpleValidInput(['tel' => '09011112222']))->assertOk()->getContent();
    expect($register)->toContain('登録中です。')->toContain('■予約新規登録 確認');

    $row = DB::table('tb_reserve')->where('cd', 50)->first();
    expect($row)->not->toBeNull()
        ->and($row->user_name)->toBe('田中 &lt;花子&gt;')
        ->and($row->user_tel)->toBe('09011112222')
        ->and($row->reserve_flg)->toBe('2')
        ->and($row->shop_cd)->toBe('1')
        ->and($row->reserve_cate)->toBe('2')
        ->and($row->reserve_nomination)->toBe('10')
        ->and($row->user_cd)->toBe('')
        ->and((int) $row->reserve_time)->toBe(Carbon::create(2026, 12, 5, 14, 30, 0, 'Asia/Tokyo')->getTimestamp())
        ->and($row->update)->not->toBe('');
    expect($register)->toContain(route('admin.reservas.simple.complete', 50));

    // Correos: tienda, 指名 y cliente
    Mail::assertSent(ReservationMail::class, 3);
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->hasTo('honten@example.jp') && $m->mailSubject === '田中 <花子>様より予約登録申請'
        && str_contains($m->body, '○予約日：' . "\n" . '2026年12月05日 14時30分'));
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->hasTo('hana@example.jp') && $m->mailSubject === '予約登録確認メール'
        && str_contains($m->body, '会員登録がお済みでない方は'));

    // 完了 (también por la URL legacy ?reserve_cd=)
    $this->get('/system/category3/reservation/reserve_new_complete.php?reserve_cd=50')->assertOk()
        ->assertSee('■予約新規登録 完了')
        ->assertSee('田中 &lt;花子&gt;', false)
        ->assertSee('2026/12/05')->assertSee('14:30')
        ->assertSee('全店 相談')
        ->assertSee('続けて新規登録する', false);
});

it('reserve_new: socio (nombre, móvil y correo del socio) y modificación act=up', function () {
    Customer::factory()->create(['cd' => 700, 'id' => 'M700', 'name' => '会員 太郎', 'kojin_mobile' => '08012345678', 'email1' => 'taro@example.jp']);

    $this->post(route('admin.reservas.simple.create'), ['kaiin_cd' => '700'])->assertOk()
        ->assertSee('会員番号')->assertSee('M700')
        ->assertSee('<input type="hidden" name="name" id="name" value="会員 太郎" />', false)
        ->assertSee('value="08012345678"', false)
        ->assertSee('taro@example.jp');

    $this->post(route('admin.reservas.simple.store'), simpleValidInput(['kaiin_cd' => '700', 'mitouroku' => '', 'name' => '会員 太郎', 'email' => 'taro@example.jp', 'tel' => '08012345678']))->assertOk();
    expect(DB::table('tb_reserve')->where('cd', 50)->value('user_cd'))->toBe('700');
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->hasTo('taro@example.jp') && str_contains($m->body, '→修正はログイン後、予約一覧からどうぞ'));

    // 修正する: carga la reserva guardada
    $this->post('/system/category3/reservation/reserve_new.php', ['act' => 'up', 'reserve_cd' => '50'])->assertOk()
        ->assertSee('■予約登録情報 修正')
        ->assertSee('value="2026/12/05"', false)
        ->assertSee('<option value="14" selected>14</option>', false)
        ->assertSee('<input type="hidden" name="reserve_cd" id="reserve_cd" value="50">', false);

    $this->post(route('admin.reservas.simple.store'), simpleValidInput(['act' => 'up', 'reserve_cd' => '50', 'kaiin_cd' => '700', 'hour1' => '16', 'min1' => '00', 'name' => '会員 太郎', 'email' => 'taro@example.jp']))->assertOk();
    expect((int) DB::table('tb_reserve')->where('cd', 50)->value('reserve_time'))->toBe(Carbon::create(2026, 12, 5, 16, 0, 0, 'Asia/Tokyo')->getTimestamp())
        ->and(DB::table('tb_reserve')->count())->toBe(1);
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->mailSubject === '予約変更確認メール');
});

it('reserve_calendar: mes con el número de reservas y formulario a reserve_list', function () {
    simpleReserve(1);
    simpleReserve(2, ['shop_cd' => '2']);

    $html = $this->get('/system/category3/reservation/reserve_calendar.php')->assertOk()->getContent();
    expect($html)->toContain('■予約・来店一覧')->toContain('2026年12月')
        ->toContain('<option value="">全店舗</option>')
        ->toContain('<option value="2">駅前店</option>')
        ->toContain('全<font style="font-weight: bold; color: #EE0000">2</font>件')
        ->toContain('value="2026/12/5"')
        ->toContain(route('admin.reservas.simple.index'));

    $this->post(route('admin.reservas.simple.calendar'), ['year' => '2027', 'mon' => '1'])->assertOk()->assertSee('2027年1月');
});

it('reserve_list: reservas del día con estado, 会員ID y enlace al detalle', function () {
    Customer::factory()->create(['cd' => 700, 'id' => 'M700']);
    simpleReserve(1, ['user_cd' => '700', 'reserve_flg' => '2', 'raiten_flg' => '1']);
    simpleReserve(2, ['shop_cd' => '2']);
    simpleReserve(3, ['reserve_time' => (string) Carbon::create(2026, 12, 6, 9, 0, 0, 'Asia/Tokyo')->getTimestamp()]);

    $html = $this->post('/system/category3/reservation/reserve_list.php', ['dd' => '2026/12/5', 'shop_cd' => '1'])->assertOk()->getContent();
    expect($html)->toContain('■予約一覧 (2026/12/05)')
        ->toContain('<span class="attention">1件</span>の該当結果がありました。')
        ->toContain('M700')->toContain('田中 &amp; 花子')->toContain('2026/12/05 14:00')
        ->toContain('alt="来店済"')->toContain('alt="予約確定"')
        ->toContain('value="顧客情報"')
        ->toContain(e(route('admin.reservas.simple.show', ['reservation' => 1, 'page' => 1, 'dd' => '2026/12/5', 'shop_cd' => '1', 'kensu' => 10])));

    // 全店舗
    $this->get(route('admin.reservas.simple.index', ['dd' => '2026/12/5']))->assertOk()->assertSee('2件</span>', false)->assertSee('非会員');
    $this->get(route('admin.reservas.simple.index', ['dd' => '2026/12/7']))->assertOk()->assertSee('現在予約はありません');
});

it('reserve_detail y reserve_change_status: 取り消し, 予約確定 y 来店処理', function () {
    simpleReserve(1);

    $html = $this->get('/system/category3/reservation/reserve_detail.php?reserve_cd=1&dd=2026/12/5&shop_cd=1')->assertOk()->getContent();
    expect($html)->toContain('■予約詳細')->toContain('本店')->toContain('田中 &amp; 花子')->toContain('hana@example.jp')
        ->toContain('2026/12/05')->toContain('14:00')->toContain('カット')->toContain('山田')
        ->toContain('value="修正する"')->toContain('value="取り消し"')->toContain('value="予約確定"')
        ->not->toContain('value="来店処理"');

    // 予約確定 (correo) → 来店処理 (sin correo)
    $this->post(route('admin.reservas.simple.status', 1), ['act' => 'kakutei', 'dd' => '2026/12/5', 'shop_cd' => '1'])->assertOk()
        ->assertSee('■予約情報 予約確定')->assertSee('予約確定中です。')
        ->assertSee('reserve_cd=1', false);
    expect(DB::table('tb_reserve')->where('cd', 1)->value('reserve_flg'))->toBe('2');
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->mailSubject === '予約確定確認メール' && $m->hasTo('hana@example.jp'));
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->hasTo('yamada@example.jp') && ! str_contains($m->body, '○電話番号'));

    $this->get(route('admin.reservas.simple.show', 1))->assertOk()->assertSee('value="来店処理"', false);
    $this->post('/system/category3/reservation/reserve_change_status.php', ['act' => 'visit', 'reserve_cd' => '1'])->assertOk()->assertSee('来店処理中です。');
    expect(DB::table('tb_reserve')->where('cd', 1)->value('raiten_flg'))->toBe('1');
    $this->get(route('admin.reservas.simple.show', 1))->assertOk()->assertDontSee('value="来店処理"', false)->assertDontSee('value="取り消し"', false);

    simpleReserve(2);
    $this->post(route('admin.reservas.simple.status', 2), ['act' => 'del'])->assertOk()->assertSee('予約取り消し中です。');
    expect(DB::table('tb_reserve')->where('cd', 2)->value('reserve_flg'))->toBe('9');
    Mail::assertSent(ReservationMail::class, fn ($m) => $m->mailSubject === '予約取り消し確認メール');

    // reserve_delete.php: permanent → '2', sin correo
    simpleReserve(3);
    $sent = count(Mail::sent(ReservationMail::class));
    $this->post('/system/category3/reservation/reserve_delete.php', ['act' => 'permanent', 'reserve_cd' => '3'])->assertOk()->assertSee('本予約処理');
    expect(DB::table('tb_reserve')->where('cd', 3)->value('reserve_flg'))->toBe('2')
        ->and(count(Mail::sent(ReservationMail::class)))->toBe($sent);
});

it('no master: lista y alta solo con la tienda de la sesión; sin permiso 1026-4 no cancela', function () {
    DB::table('tb_kengen_contents')->insert(['cd' => 1026, 'name' => '予約管理', 'hyouji_flg' => '1']);
    DB::table('tb_staff_group')->insert(['cd' => 5, 'name' => '一般', 'disp_flg' => '1']);
    DB::table('tb_staff')->where('cd', 10)->update(['tb_staff_group_cd' => 5]);
    DB::table('tb_kengen')->insert(['cd' => 1, 'tb_staff_group_cd' => 5, 'tb_kengen_contents_cd' => 1026, 'etsuran_flg' => '1', 'shinki_flg' => '1', 'shusei_flg' => '1', 'sakujo_flg' => '0']);
    simpleReserve(1);
    simpleReserve(2, ['shop_cd' => '2']);
    $this->get('/system/logout.php');
    loginAs('staff10', 'x');

    $this->post(route('admin.reservas.simple.index'), ['dd' => '2026/12/5', 'shop_cd' => '2'])->assertOk()->assertSee('1件</span>', false);
    $this->post(route('admin.reservas.simple.create'), ['mitouroku' => 'mitouroku', 'act' => 'new'])->assertOk()
        ->assertSee('<input type="hidden" name="tb_shop_cd" id="tb_shop_cd" value="1">', false)
        ->assertDontSee('駅前店');
    $this->post(route('admin.reservas.simple.store'), simpleValidInput(['tb_shop_cd' => '2']))->assertOk();
    expect(DB::table('tb_reserve')->where('cd', 50)->value('shop_cd'))->toBe('1');

    $this->get(route('admin.reservas.simple.show', 1))->assertOk()->assertDontSee('value="取り消し"', false);
    $this->post(route('admin.reservas.simple.status', 1), ['act' => 'del'])->assertForbidden();
    expect(DB::table('tb_reserve')->where('cd', 1)->value('reserve_flg'))->toBe('1');
});

it('ajax_category / ajax_staff: <option> de la tienda', function () {
    $html = $this->post('/system/category3/reservation/ajax_category.php', ['shop_cd' => '1'])->assertOk()->getContent();
    expect($html)->toContain('<option value="">選択して下さい</option>')
        ->toContain('<option value="1">カット</option>')->toContain('<option value="2">全店 相談</option>')
        ->not->toContain('駅前 限定')->not->toContain('削除済');

    $this->post('/system/category3/reservation/ajax_staff.php', ['shop_cd' => '1'])->assertOk()->assertSee('<option value="10">山田</option>', false);
    $this->post('/system/category3/reservation/ajax_staff.php', ['shop_cd' => '2'])->assertOk()->assertSee('登録されていません');
});

it('category_entry / register / delete: 予約カテゴリー por tienda y 全店舗', function () {
    $html = $this->get('/system/category3/reservation/category_entry.php')->assertOk()->getContent();
    expect($html)->toContain('■予約カテゴリー登録')->toContain('■予約カテゴリー一覧')
        ->toContain('<option value="-1" >全店舗</option>')
        ->toContain('最新から3件')
        ->not->toContain('削除済');

    $this->post('/system/category3/reservation/category_register.php', ['act' => 'new', 'shop_cd' => '-1', 'cate_name' => ' 新 "メニュー" '])->assertOk()
        ->assertSee('登録中です。')->assertSee(route('admin.reservas.simple-categories.index'), false);
    $new = DB::table('tb_reserve_name')->where('cd', 10)->first();
    expect($new->reserve_cate_name)->toBe('新 &quot;メニュー&quot;')->and($new->tb_shop_cd)->toBeNull()->and($new->disp_flg)->toBe('1');

    $this->get(route('admin.reservas.simple-categories.index', ['cd' => 1]))->assertOk()
        ->assertSee('value="カット"', false)->assertSee('この内容で修正する')->assertSee('<option value="1" selected>本店</option>', false);
    $this->post(route('admin.reservas.simple-categories.store'), ['act' => 'up', 'cd' => '1', 'shop_cd' => '2', 'cate_name' => 'カラー'])->assertOk();
    expect(DB::table('tb_reserve_name')->where('cd', 1)->first())->reserve_cate_name->toBe('カラー')->tb_shop_cd->toBe(2);

    $this->post(route('admin.reservas.simple-categories.destroy', 1), ['delete_flg' => 'del', 'cd' => '1'])->assertOk()->assertSee('削除中です。');
    expect(DB::table('tb_reserve_name')->where('cd', 1)->value('disp_flg'))->toBe('9');
});

it('config / config_register: 予約項目設定 en tb_koumoku', function () {
    $this->get('/system/category3/reservation/config.php')->assertOk()
        ->assertSee('■予約項目設定')->assertSee('予約人数')->assertSee('担当者指名')
        ->assertSee('name="item_cd1_1" id="item_cd1_1" value="1" checked', false);

    $this->post('/system/category3/reservation/config_register.php', [
        'act' => 'up', 'item_max_no' => '2', 'item_cd1' => '301', 'item_cd2' => '302', 'item_cd1_2' => '1', 'item_cd2_2' => '1',
    ])->assertOk()->assertSee('登録中です。');

    expect(DB::table('tb_koumoku')->where('cd', 301)->value('koumoku_disp'))->toBe('')
        ->and(DB::table('tb_koumoku')->where('cd', 302)->value('koumoku_disp'))->toBe('1')
        ->and(DB::table('tb_koumoku')->where('cd', 302)->value('koumoku_check'))->toBe('1');

    // Sin 予約人数, el formulario ya no lo pide
    $this->post(route('admin.reservas.simple.create'), ['mitouroku' => 'mitouroku', 'act' => 'new'])->assertOk()->assertDontSee('予約人数');
});

it('model_reservation: 予約機種一覧 y su CSV', function () {
    Customer::factory()->create(['cd' => 700, 'login_id' => 'L700', 'name' => '機種 太郎', 'tel' => '0300000000', 'tb_shop_cd' => 1]);
    DB::table('tb_customer_point')->insert(['cd' => 1, 'tb_customer_cd' => 700, 'service_point' => 1200]);
    $applied = Carbon::create(2026, 11, 1, 12, 0, 0, 'Asia/Tokyo')->getTimestamp();
    DB::table('tb_change_model')->insert(['cd' => 1, 'next_model' => 'iPhone 18', 'resevation_type' => '３カ月後に予約したい', 'tb_customer_cd' => '700', 'disp_flg' => '1', 'touroku_date' => (string) $applied]);

    $this->get('/system/category3/reservation/model_reservation.php')->assertOk()
        ->assertSee('■予約機種一覧')->assertSee('L700')->assertSee('機種 太郎')->assertSee('本店')
        ->assertSee('2026年11月01日')->assertSee('2027年01月30日')->assertSee('iPhone 18')->assertSee('1,200 pt')
        ->assertSee('1件～1件目/1件中');

    $csv = $this->post(route('admin.reservas.model-changes'), ['csv_print' => '1'])->assertOk()->streamedContent();
    expect(mb_convert_encoding($csv, 'UTF-8', 'SJIS-win'))
        ->toStartWith('ID,名前,電話番号,登録店舗,予約申込日,ご希望予約時期,該当する予約時期,希望機種,保有ポイント数')
        ->toContain('L700,機種 太郎,0300000000,本店,2026年11月01日,３カ月後に予約したい,2027年01月30日,iPhone 18,1200');
});
