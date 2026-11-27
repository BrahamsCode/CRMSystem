<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/*
 * F1a: マイクーポン 一覧, 新規登録 / 修正 (errchk + register), 詳細 con クーポン解析 y 削除.
 */
beforeEach(function () {
    if (! View::exists('layouts.legacy')) {
        $dir = storage_path('framework/testing/promocion-views/layouts');
        File::ensureDirectoryExists($dir);
        $stub = "<html><head><title>@yield('title')</title>@yield('css')@yield('js')</head><body>@yield('content')</body></html>";
        File::put("$dir/legacy.blade.php", $stub);
        File::put("$dir/legacy-popup.blade.php", $stub);
        View::addLocation(dirname($dir));
    }
    if (! Illuminate\Support\Facades\Route::has('legacy.promocion.inc.ajax_search_coupon')) {
        Illuminate\Support\Facades\Route::middleware('web')->group(base_path('routes/legacy/promocion.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('hatuban')->insert(['koumoku' => 'tb_mycoupon_master', 'kazu' => 30]);
    DB::table('tb_base_system_child')->insert(['cd' => 33, 'system_name' => 'スタンプ管理', 'system_flg' => '1']);
    DB::table('tb_mycoupon_master')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'title' => '10%OFF', 'comment' => "全品\n対象", 'coupon_kubun' => '', 'limit_flg' => '5', 'time_limit' => Carbon::parse('2026-12-31 23:59:59', 'Asia/Tokyo')->getTimestamp(), 'attention' => '注意1<>注意2', 'disp_flg' => '1', 'hyouji_flg' => '1'],
        ['cd' => 2, 'tb_shop_cd' => -1, 'title' => '全店', 'comment' => '', 'coupon_kubun' => '', 'limit_flg' => '1', 'time_limit' => null, 'attention' => '', 'disp_flg' => '1', 'hyouji_flg' => ''],
        ['cd' => 3, 'tb_shop_cd' => 2, 'title' => '支店用', 'comment' => '', 'coupon_kubun' => '', 'limit_flg' => '1', 'time_limit' => null, 'attention' => '', 'disp_flg' => '1', 'hyouji_flg' => ''],
        ['cd' => 4, 'tb_shop_cd' => 1, 'title' => 'スタンプ', 'comment' => '', 'coupon_kubun' => '2', 'limit_flg' => '2', 'time_limit' => null, 'attention' => '', 'disp_flg' => '1', 'hyouji_flg' => ''],
    ]);
});

it('lista por tienda y 使用区分 y actualiza el 表示 en bloque', function () {
    $this->post(route('admin.promocion.coupons.index'), ['shop_cd' => '1', 'coupon_kubun' => ''])->assertOk()
        ->assertSee('10%OFF')->assertSee('全店')->assertDontSee('支店用')->assertDontSee('<td>スタンプ', false)
        ->assertSee('指定日付：2026/12/31まで')->assertSee('(発行中)')
        ->assertSee('>マイページスタンプ交換用</option>', false)->assertDontSee('ゲーム用');

    $this->post(route('admin.promocion.coupons.index'), ['shop_cd' => '1', 'coupon_kubun' => '', 'update_hyouji_flg' => 'update',
        'update_cd' => ['1', '2'], 'hyouji_flg_array' => ['2' => 'on']])->assertOk();
    expect(DB::table('tb_mycoupon_master')->orderBy('cd')->limit(2)->pluck('hyouji_flg')->all())->toBe(['', '1']);
});

it('valida como mycoupon_errchk', function () {
    $this->post(route('admin.promocion.coupons.create'), ['act' => 'up', 'tb_shop_cd' => '', 'title' => '', 'coupon_kubun' => '2', 'shouhi_point' => '', 'limit_flg' => '0'])
        ->assertOk()->assertSee('※店舗を選択して下さい。')->assertSee('※必須項目です。')->assertSee('※有効期限を選択して下さい。')->assertSee('<td class="Ng">', false);

    $this->post(route('admin.promocion.coupons.create'), ['act' => 'up', 'tb_shop_cd' => '1', 'title' => 'x', 'coupon_kubun' => '', 'limit_flg' => '5', 'time_limit' => '2026/02/30'])
        ->assertOk()->assertSee('※日付：2026/02/30は不適切な形式です。');
    expect(DB::table('tb_mycoupon_master')->count())->toBe(4);
});

it('da de alta con hatuban, get_form y premio de ガチャリン, y modifica', function () {
    $this->post(route('admin.promocion.coupons.create'), ['act' => 'up', 'tb_shop_cd' => '1', 'title' => ' "半額" ', 'comment' => "It's", 'coupon_kubun' => '9',
        'kujibiki_name' => 'ゴールド', 'limit_flg' => '4', 'time_limit' => '30', 'attention' => ['a', '', 'b']])
        ->assertRedirect(route('admin.promocion.coupons.registered', ['cd' => 30, 'register_type' => 'new']));

    $row = DB::table('tb_mycoupon_master')->where('cd', 30)->first();
    expect($row->title)->toBe('半額')->and($row->comment)->toBe('It’s')->and((string) $row->time_limit)->toBe('30')
        ->and($row->attention)->toBe('a<><>b')->and($row->disp_flg)->toBe('1')->and($row->coupon_kubun)->toBe('9');
    expect(DB::table('contents_master')->where('category', 'tb_c6_kujibiki')->where('field', 'coupon')->where('int_data', 30)->value('text_data'))->toBe('ゴールド');

    $this->get(route('admin.promocion.coupons.edit', ['cd' => 1]))->assertOk()->assertSee('■マイクーポン 修正')
        ->assertSee('name="time_limit_val2" maxlength="10" size="12" value="2026/12/31"', false)->assertSee('value="注意2"', false);

    $this->post(route('admin.promocion.coupons.edit'), ['act' => 'up', 'cd' => '1', 'tb_shop_cd' => '1', 'title' => '15%OFF', 'coupon_kubun' => '2', 'shouhi_point' => '5', 'limit_flg' => '5', 'time_limit' => '2027/01/15'])
        ->assertRedirect(route('admin.promocion.coupons.registered', ['cd' => 1, 'register_type' => 'update']));
    $row = DB::table('tb_mycoupon_master')->where('cd', 1)->first();
    expect($row->title)->toBe('15%OFF')->and($row->shouhi_point)->toBe(5)
        ->and((int) $row->time_limit)->toBe(Carbon::parse('2027-01-15 23:59:59', 'Asia/Tokyo')->getTimestamp());

    $this->get(route('admin.promocion.coupons.registered', ['cd' => 1, 'register_type' => 'update']))->assertSee('登録中です。')
        ->assertSee('URL=' . e(route('admin.promocion.coupons.show', ['cd' => 1, 'register_type' => 'update'])), false);
});

it('muestra el detalle con la クーポン解析 de los usos', function () {
    DB::table('tb_customer')->insert(['cd' => 7, 'id' => 'C7', 'name' => '客', 'kojin_sex' => '女性', 'job' => '会社員', 'disp_flg' => '1',
        'kojin_birth_date' => Carbon::parse('1990-01-01', 'Asia/Tokyo')->getTimestamp()]);
    DB::table('tb_mycoupon_used')->insert(['cd' => 1, 'tb_mycoupon_master_cd' => 1, 'tb_customer_cd' => 7, 'tb_shop_cd' => 1, 'used_date' => (string) Carbon::parse('2026-11-20 12:00', 'Asia/Tokyo')->getTimestamp()]);
    DB::table('mailmag_master')->insert(['cd' => 50, 'type_flg' => 'coupon', 'type_data_cd' => 1, 'タイトル' => 'クーポン便', '配信総数' => 12, '配信日時' => '2026/11/01 10:00:00', 'search_display' => '店舗：本店<>性別：女性']);

    $this->get(route('admin.promocion.coupons.show', ['cd' => 1]))->assertOk()
        ->assertSee('■クーポン 詳細')->assertSee('指定日付：2026/12/31まで')->assertSee('注意1<br/>', false)
        ->assertSee('過去クーポン使用総数')->assertSee('<span style="font-size: 45px; font-weight: bold; color: #EE0000">1</span>', false)
        ->assertSee('2026年11月')->assertSee('女性：1人,1', false)->assertSee('30～39歳：1人,1', false)->assertSee('会社員：1人,1', false)
        ->assertSee('クーポン便')->assertSee('店舗：本店<br />性別：女性', false)
        ->assertSee('修正する')->assertSee('削除する');
});

it('borra con disp_flg = 9 y vuelve a la lista', function () {
    $this->post(route('admin.promocion.coupons.destroy'), ['delete_flg' => 'del', 'cd' => '3', 'shop_cd' => '2', 'coupon_kubun' => '', 'page' => '1', 'kensu' => '20'])
        ->assertOk()->assertSee('削除中です。')->assertSee('mycoupon_list?shop_cd=2', false);
    expect(DB::table('tb_mycoupon_master')->where('cd', 3)->value('disp_flg'))->toBe('9');
});

it('envía la クーポン por correo con el flujo común y la lista en sus 過去配信', function () {
    DB::table('tb_office')->insert(['cd' => 1, 'kaishamei' => '本社']);
    DB::table('tb_base_system_child')->insert(['cd' => 32, 'system_name' => 'マイクーポン管理', 'system_flg' => '1']);
    DB::table('hatuban')->insert(['koumoku' => 'mailmag', 'kazu' => 80]);
    DB::table('tb_customer')->insert(['cd' => 9, 'id' => 'C9', 'name' => '客', 'email1' => 'c@example.com', 'mailmaga_email_type' => 'パソコン', 'touroku_kubun' => '2', 'disp_flg' => '1']);
    Illuminate\Support\Facades\Bus::fake();

    $this->get(route('admin.promocion.coupons.show', ['cd' => 1]))->assertSee('このクーポンを送る')->assertSee(route('admin.promocion.coupons.mail.select'), false);

    $carry = ['cd' => '1', 'hakkou_cd' => '1', 'get_coupon_cd' => '1', 'get_coupon_text' => '【10%OFF】(1)', 'coupon_kubun' => ''];
    $select = $this->post(route('admin.promocion.coupons.mail.select'), $carry)->assertOk()->assertSee('クーポン 発行条件選択')->assertSee('name="get_coupon_cd" value="1"', false);
    $response = $this->post(route('admin.promocion.coupons.mail.select'), $carry + ['act' => 'up'])->assertOk()->assertSee('件見つかりました');
    preg_match('/name="search_uniqid" value="(\w{16})"/', $response->getContent(), $m);

    $this->post(route('admin.promocion.coupons.mail.create'), $carry + ['search_uniqid' => $m[1]])->assertOk()
        ->assertSee('【10%OFF】(1)')->assertSee('id="get_coupon_cd" name="get_coupon_cd" value="1"', false)->assertSee(route('admin.promocion.coupons.mail.confirm'), false);

    $this->post(route('admin.promocion.coupons.mail.store'), ['search_uniqid' => $m[1], 'hakkou_cd' => '1', 'get_coupon_cd' => '1', 'get_coupon_text' => '【10%OFF】(1)',
        'title' => 'クーポン', 'naiyou' => '本文', 'tourokusya' => '1', 'send_flg' => '1', 'jikan' => '10', 'fun' => '00', 'mail_send_verify' => '5'])->assertOk()->assertSee('以下の内容で配信いたしました。');

    $mail = DB::table('mailmag_master')->where('cd', 80)->first();
    expect($mail->type_flg)->toBe('coupon')->and($mail->type_data_cd)->toBe(1);

    $this->get(route('admin.promocion.coupons.mail.past'))->assertOk()->assertSee('■過去マイクーポンメール配信')->assertSee('<td>10%OFF</td>', false);
});
