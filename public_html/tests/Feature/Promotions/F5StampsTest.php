<?php

use App\Mail\Promotions\PromotionMail;
use App\Services\Promotions\StampExpiryMailer;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/*
 * F5 (S2): スタンプ管理 — デザイン, 仕様 (+ AJAX y plantillas), ルール, クーポンお知らせ y comandos de caducidad.
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
    $this->withoutMiddleware(Authenticate::class);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('admin_master')->insert(['cd' => 1, 'domain' => 'shop.crm-s.net', 'stamp_shikkou_kikan' => '30']);
    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('tb_base_system_child')->insert(['cd' => 33, 'tb_base_system_cd' => 3, 'system_name' => 'スタンプ管理', 'system_flg' => '1']);
});

it('elige hasta 10 diseños y el de por defecto', function () {
    DB::table('tb_stamp_pattern')->insert([
        ['cd' => 1, 'narabi' => '2', 'disp_flg' => '1', 'pic_shop_flg' => '1', 'pic_default' => '1'],
        ['cd' => 2, 'narabi' => '1', 'disp_flg' => '1', 'pic_shop_flg' => '', 'pic_default' => ''],
        ['cd' => 3, 'narabi' => '3', 'disp_flg' => '9', 'pic_shop_flg' => '', 'pic_default' => ''],
    ]);
    $this->get(route('admin.promocion.stamps.pattern'))->assertOk()
        ->assertSeeInOrder(['/hp/uploads/c4/stamp/2.gif', '/hp/uploads/c4/stamp/1.gif'], false)
        ->assertDontSee('/hp/uploads/c4/stamp/3.gif', false)
        ->assertSee('id="default_1" value="1"  checked', false)->assertSee('id="default_2" value="2"  disabled', false);

    $this->post(route('admin.promocion.stamps.pattern.update'), ['send' => '更新', 'sentaku' => ['2' => '2', '3' => '3'], 'default' => '2'])
        ->assertOk()->assertSee('登録中です。');
    expect(DB::table('tb_stamp_pattern')->orderBy('cd')->get(['pic_shop_flg', 'pic_default'])->map(fn ($r) => [$r->pic_shop_flg, $r->pic_default])->all())
        ->toBe([['', ''], ['1', '1'], ['1', '']]);

    // Sin 設定 no cambia nada (el SQL del legacy fallaba entero)
    $this->post(route('admin.promocion.stamps.pattern.update'), ['send' => '更新', 'sentaku' => ['1' => '1']]);
    expect(DB::table('tb_stamp_pattern')->where('cd', 2)->value('pic_default'))->toBe('1');
});

it('muestra el 仕様, crea la fila de cada tienda y guarda cada formulario', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_stamp_config', 'kazu' => 5]);
    DB::table('tb_stamp_config')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'limit_flg' => '3600', 'stamp_max' => 20, 'touroku_bonus' => 2]);

    $this->get(route('admin.promocion.stamps.config', ['end' => 1]))->assertOk()
        ->assertSee('<option value="20"  selected >20個</option>', false)
        ->assertSee('<option value="2"  selected >2個</option>', false)
        ->assertSee('<option value="3600"  selected >1時間</option>', false)
        ->assertSee('jAlert("更新されました。", "通知");', false)
        ->assertSee('■スタンプ有効期限設定');
    expect(DB::table('tb_stamp_config')->where('tb_shop_cd', 2)->first())->limit_flg->toBe('1800')->cd->toBe(5);

    $this->post(route('admin.promocion.stamps.config.update'), ['act' => 'up', 'anchor_id' => 'form1', 'kankaku' => ['1' => '0', '2' => '7200'],
        'stamp_max' => '999999999', 'touroku_bonus' => '3', 'mypage_stamp_hyouji_flg' => '2'])
        ->assertRedirect(route('admin.promocion.stamps.config', ['end' => 1]).'#form1');
    expect(DB::table('tb_stamp_config')->orderBy('tb_shop_cd')->pluck('limit_flg')->all())->toBe(['0', '7200'])
        ->and((int) DB::table('tb_stamp_config')->where('tb_shop_cd', 1)->value('stamp_max'))->toBe(999999999)
        ->and(DB::table('admin_master')->value('mypage_stamp_hyouji_flg'))->toBe('2');

    $this->post(route('admin.promocion.stamps.config.update'), ['act' => 'change_stamp_shikkou_time', 'stamp_shikkou_time_h' => '03', 'stamp_shikkou_time_m' => '30']);
    $this->post(route('admin.promocion.stamps.config.update'), ['act' => 'change_stamp_shikkou_alert', 'stamp_shikkou_alert' => '7', 'stamp_shikkou_alert_time_h' => '09', 'stamp_shikkou_alert_time_m' => '00']);
    $this->post(route('admin.promocion.stamps.config.update'), ['act' => 'change_raiten_stamp_flg', 'raiten_stamp_flg' => '2']);
    $admin = DB::table('admin_master')->first();
    expect([$admin->stamp_shikkou_time, $admin->stamp_shikkou_alert, $admin->stamp_shikkou_alert_time, $admin->raiten_stamp_flg])->toBe(['03:30', '7', '09:00', '2']);

    // スタンプ会計処理: 還元 por tienda (vacío → 1000)
    DB::table('tb_base_system_grandchild')->insert(['cd' => 9, 'system_name' => 'スタンプ会計処理', 'system_flg' => '1']);
    $this->get(route('admin.promocion.stamps.config'))->assertSee('■スタンプアプリ設定');
    $this->post(route('admin.promocion.stamps.config.update'), ['act' => 'change_stamp_settings', 'stamp_settings' => [['1', '500', ''], ['2', '', '200']]]);
    expect(DB::table('tb_shop')->orderBy('cd')->get(['stamp_percentage_genkin', 'stamp_percentage_card'])->map(fn ($r) => [(float) $r->stamp_percentage_genkin, (float) $r->stamp_percentage_card])->all())
        ->toBe([[500.0, 1000.0], [1000.0, 200.0]]);
});

it('avisa de los afectados y al activar la caducidad la renueva', function () {
    DB::table('admin_master')->update(['stamp_shikkou_sousa' => '2']);
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'stamp_count' => 3, 'disp_flg' => '1', 'touroku_kubun' => '2'],
        ['cd' => 2, 'stamp_count' => 0, 'disp_flg' => '1', 'touroku_kubun' => '2'],
    ]);
    $this->post(route('admin.promocion.stamps.expiry-preview'))->assertOk()
        ->assertSee('有効アカウント1人(スタンプを1個以上保有するユーザー)に対して', false)->assertSee('有効期限2026/12/26を設定します。', false);

    $this->post(route('admin.promocion.stamps.config.update'), ['act' => 'change_stamp_shikkou_sousa', 'stamp_shikkou_sousa' => '1', 'anchor_id' => 'change_stamp_shikkou_sousa_form'])
        ->assertRedirect(route('admin.promocion.stamps.config', ['end' => 1]).'#change_stamp_shikkou_sousa_form');
    expect((int) DB::table('tb_customer')->where('cd', 1)->value('stamp_yukoukigen'))->toBe(Carbon::parse('2026-12-27 00:00:00', 'Asia/Tokyo')->getTimestamp() - 1)
        ->and(DB::table('tb_customer')->where('cd', 2)->value('stamp_yukoukigen'))->toBeNull();
    $this->post(route('admin.promocion.stamps.expiry-preview'))->assertSee('ng');
});

it('edita las plantillas de aviso en admin_master', function () {
    $this->get(route('admin.promocion.stamps.template', ['type' => 'shikkou']))->assertOk()
        ->assertSee('■スタンプ失効通知メール内容登録')->assertSee('{スタンプ数}', false)->assertSee('window.parent.popUpWinClose("")', false);

    $this->post(route('admin.promocion.stamps.template.update', ['type' => 'alert']), ['title' => '期限のお知らせ', 'naiyou' => "{名前}様\nあと少し"])
        ->assertRedirect(route('admin.promocion.stamps.template', ['type' => 'alert', 'comp' => 1]));
    $admin = DB::table('admin_master')->first();
    expect([$admin->stamp_alert_mail_title, $admin->stamp_alert_mail_naiyou])->toBe(['期限のお知らせ', "{名前}様\nあと少し"]);
    $this->get(route('admin.promocion.stamps.template', ['type' => 'alert', 'comp' => 1]))->assertSee('window.parent.popUpWinReloadClose("");', false);
});

it('lista, da de alta, modifica y borra スタンプ発行ルール', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_stamp_rules', 'kazu' => 10]);
    DB::table('tb_stamp_rules')->insert([
        ['cd' => 1, 'tb_shop_cd' => -1, 'type_flg' => '1', 'start_time' => 0, 'end_time' => 1200, 'stamp_count' => 2, 'hyouji_flg' => '1', 'update_date' => 100],
        ['cd' => 2, 'tb_shop_cd' => 1, 'type_flg' => '4', 'start_time' => 32, 'end_time' => null, 'stamp_count' => 3, 'hyouji_flg' => '9', 'update_date' => 100],
    ]);
    $this->get(route('admin.promocion.stamps.rules.index'))->assertOk()
        ->assertSeeInOrder(['本店のスタンプルール', '毎月&nbsp;末日', '3倍', '全店舗のスタンプルール', '毎日&nbsp;0:00時 から 12:00時 まで'], false);

    $this->post(route('admin.promocion.stamps.rules.create'), ['act' => 'up', 'tb_shop_cd' => '', 'type_flg' => '0'])->assertOk()
        ->assertSee('※店舗を選択して下さい。')->assertSee('※ルール区分を選択して下さい。');

    $this->get(route('admin.promocion.stamps.rules.create'))->assertOk()->assertDontSee('使用金額ランク')->assertSee('発行スタンプ');
    $this->post(route('admin.promocion.stamps.rules.create'), ['act' => 'up', 'tb_shop_cd' => '2', 'type_flg' => '5',
        'start_year' => '2026', 'start_month' => '12', 'start_day' => '24', 'stamp_count' => '4'])
        ->assertRedirect(route('admin.promocion.stamps.rules.registered', ['mode' => 'new']));
    $rule = DB::table('tb_stamp_rules')->where('cd', 10)->first();
    expect([(int) $rule->tb_shop_cd, $rule->type_flg, (int) $rule->stamp_count, $rule->hyouji_flg])->toBe([2, '5', 4, '1'])
        ->and((int) $rule->start_time)->toBe(Carbon::parse('2026-12-24 00:00:00', 'Asia/Tokyo')->getTimestamp());

    $this->post(route('admin.promocion.stamps.rules.edit'), ['act' => 'up', 'cd' => '10', 'tb_shop_cd' => '2', 'type_flg' => '2',
        'day_of_week' => '6', 'start_time' => '900', 'end_time' => '1800', 'stamp_count' => '2'])
        ->assertRedirect(route('admin.promocion.stamps.rules.registered', ['mode' => 'update']));
    expect(DB::table('tb_stamp_rules')->where('cd', 10)->first())->day_of_week->toBe(6)->stamp_count->toBe(2);

    $this->post(route('admin.promocion.stamps.rules.destroy'), ['cd' => '10', 'page' => '1', 'kensu' => '20'])->assertOk()->assertSee('削除中です。');
    expect(DB::table('tb_stamp_rules')->where('cd', 10)->exists())->toBeFalse();
});

it('configura la クーポンお知らせ por tienda y número de sellos', function () {
    DB::table('tb_mycoupon_master')->insert([
        ['cd' => 7, 'tb_shop_cd' => 1, 'title' => 'ドリンク無料', 'comment' => '1杯', 'coupon_kubun' => '2', 'disp_flg' => '1', 'hyouji_flg' => '1', 'limit_flg' => '1', 'time_limit' => '', 'shouhi_point' => 5],
        ['cd' => 8, 'tb_shop_cd' => -1, 'title' => '10%OFF', 'comment' => '', 'coupon_kubun' => '2', 'disp_flg' => '1', 'hyouji_flg' => '1', 'limit_flg' => '4', 'time_limit' => '30', 'shouhi_point' => 10],
        ['cd' => 9, 'tb_shop_cd' => 2, 'title' => '他店', 'comment' => '', 'coupon_kubun' => '2', 'disp_flg' => '1', 'hyouji_flg' => '1', 'limit_flg' => '1', 'time_limit' => '', 'shouhi_point' => 1],
    ]);
    $this->get(route('admin.promocion.stamps.oshirase'))->assertOk()->assertSee('■クーポンお知らせ設定登録')->assertSee('<option value="500">500</option>', false);
    $this->post(route('admin.promocion.stamps.oshirase.ajax'), ['tb_shop_cd' => '1', 'stamp_count' => ''])->assertSee('登録されていません');

    $this->post(route('admin.promocion.stamps.oshirase.ajax'), ['tb_shop_cd' => '1', 'stamp_count' => '5'])->assertOk()
        ->assertSeeInOrder(['発行から30日間', '10%OFF', 'ドリンク無料'])->assertDontSee('他店');

    $this->post(route('admin.promocion.stamps.oshirase.store'), ['tb_shop_cd' => '1', 'stamp_count' => '5', 'naiyou' => "おめでとう\n<b>", 'coupon_master' => '8,7,x', 'hyouji_flg' => '1'])
        ->assertOk()->assertSee('処理が完了しました。');
    $this->post(route('admin.promocion.stamps.oshirase.store'), ['tb_shop_cd' => '1', 'stamp_count' => '5', 'naiyou' => 'おめでとう!', 'coupon_master' => '8', 'hyouji_flg' => '']);
    $rows = DB::table('tb_stamp_oshirase_coupon')->get();
    expect($rows)->toHaveCount(1)
        ->and([$rows[0]->tb_mycoupon_master_cd, $rows[0]->naiyou, $rows[0]->hyouji_flg, $rows[0]->disp_flg])->toBe(['8', 'おめでとう!', '', '1']);

    $this->post(route('admin.promocion.stamps.oshirase.ajax'), ['tb_shop_cd' => '1'])->assertOk()
        ->assertSee('selectStampCount(5)', false)->assertSee('【ID：8】&nbsp;10%OFF', false)->assertSee('非適用');
    $this->post(route('admin.promocion.stamps.oshirase.ajax'), ['tb_shop_cd' => '1', 'stamp_count' => '5'])
        ->assertSee('id="coupon_master8" class="couponMasterCheck" value="8"  checked="checked"', false);
});

it('redirige las URL .php del legacy del menú a las pantallas nuevas', function () {
    $this->get('/system/category3/stamp/stamp_config.php')->assertRedirect(route('admin.promocion.stamps.config'));
    $this->get('/system/category3/stamp/stamp_rules_update.php?cd=3&page=2')->assertRedirect(route('admin.promocion.stamps.rules.edit', ['cd' => 3, 'page' => 2]));
    $this->get('/system/category3/stamp/stamp_shikkou_mail_template_entry.php')->assertRedirect(route('admin.promocion.stamps.template', ['type' => 'shikkou']));
});

it('caduca los sellos vencidos con aviso, log y puesta a cero', function () {
    Mail::fake();
    DB::table('admin_master')->update(['stamp_shikkou_sousa' => '1', 'stamp_shikkou_mail_title' => '{名前}様 スタンプ失効', 'stamp_shikkou_mail_naiyou' => '{スタンプ数}個が失効しました']);
    $past = Carbon::parse('2026-11-25 23:59:59', 'Asia/Tokyo')->getTimestamp();
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'name' => '太郎', 'email1' => 'taro@example.com', 'tb_shop_cd' => 2, 'stamp_count' => 4, 'stamp_yukoukigen' => $past, 'disp_flg' => '1', 'touroku_kubun' => '2', 'login_id' => 'taro', 'login_pass' => 'x'],
        ['cd' => 2, 'name' => '花子', 'email1' => 'hanako@example.com', 'tb_shop_cd' => 1, 'stamp_count' => 2, 'stamp_yukoukigen' => $past + 86400 * 3, 'disp_flg' => '1', 'touroku_kubun' => '2', 'login_id' => '', 'login_pass' => ''],
        ['cd' => 3, 'name' => '退会', 'email1' => 'old@example.com', 'tb_shop_cd' => 1, 'stamp_count' => 1, 'stamp_yukoukigen' => $past, 'disp_flg' => '9', 'touroku_kubun' => '2', 'login_id' => '', 'login_pass' => ''],
    ]);

    $this->artisan('promocion:stamp-shikkou')->assertSuccessful();

    Mail::assertSent(PromotionMail::class, 1);
    Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('taro@example.com') && $m->mailSubject === '太郎様 スタンプ失効'
        && str_starts_with($m->body, '4個が失効しました') && str_contains($m->body, 'user/login.php') && str_contains($m->body, '支店') && $m->fromAddress === 'mail_mag@shop.crm-s.net');
    $log = DB::table('tb_stamp_log')->first();
    expect([(int) $log->tb_customer_cd, (int) $log->shori_mae_stamp, (int) $log->shouhi_stamp, $log->action_flg])->toBe([1, 4, 4, '7'])
        ->and(DB::table('tb_customer')->orderBy('cd')->pluck('stamp_count')->all())->toBe([null, 2, null]);

    // Con la caducidad desactivada no hace nada
    DB::table('admin_master')->update(['stamp_shikkou_sousa' => '2']);
    expect(app(StampExpiryMailer::class)->expire())->toBe('有効期限無効です！！！');
});

it('avisa antes de caducar a quien vence el día configurado', function () {
    Mail::fake();
    DB::table('admin_master')->update(['stamp_shikkou_alert' => '3', 'stamp_alert_mail_title' => 'まもなく失効', 'stamp_alert_mail_naiyou' => '{名前}様']);
    $inTwoDays = Carbon::parse('2026-11-28 23:59:59', 'Asia/Tokyo')->getTimestamp();
    DB::table('tb_customer')->insert([
        ['cd' => 1, 'name' => '太郎', 'email1' => 'taro@example.com', 'tb_shop_cd' => 1, 'stamp_count' => 4, 'stamp_yukoukigen' => $inTwoDays, 'disp_flg' => '1', 'touroku_kubun' => '2'],
        ['cd' => 2, 'name' => '花子', 'email1' => 'hanako@example.com', 'tb_shop_cd' => 1, 'stamp_count' => 0, 'stamp_yukoukigen' => $inTwoDays, 'disp_flg' => '1', 'touroku_kubun' => '2'],
        ['cd' => 3, 'name' => '次郎', 'email1' => 'jiro@example.com', 'tb_shop_cd' => 1, 'stamp_count' => 4, 'stamp_yukoukigen' => $inTwoDays + 86400, 'disp_flg' => '1', 'touroku_kubun' => '2'],
    ]);

    $this->artisan('promocion:stamp-shikkou-alert')->assertSuccessful();

    Mail::assertSent(PromotionMail::class, 1);
    Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('taro@example.com') && str_starts_with($m->body, '太郎様'));
});

it('programa los comandos a la hora de admin_master solo con la caducidad en uso', function () {
    $mailer = app(StampExpiryMailer::class);
    $at = fn (string $time) => Carbon::parse("2026-11-26 $time", 'Asia/Tokyo');

    DB::table('admin_master')->update(['stamp_shikkou_sousa' => '1', 'stamp_shikkou_time' => '03:30', 'stamp_shikkou_alert_time' => '']);
    expect($mailer->isDue('shikkou', $at('03:30')))->toBeTrue()
        ->and($mailer->isDue('shikkou', $at('03:31')))->toBeFalse()
        ->and($mailer->isDue('alert', $at('12:00')))->toBeTrue();

    DB::table('admin_master')->update(['stamp_shikkou_sousa' => '2']);
    expect($mailer->isDue('shikkou', $at('03:30')))->toBeFalse();

    DB::table('admin_master')->update(['stamp_shikkou_sousa' => '1']);
    DB::table('tb_base_system_child')->update(['system_flg' => '0']);
    expect($mailer->isDue('shikkou', $at('03:30')))->toBeFalse();

    $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode(' ');
    expect($commands)->toContain('promocion:stamp-shikkou-alert')->toContain('promocion:stamp-shikkou');
});
