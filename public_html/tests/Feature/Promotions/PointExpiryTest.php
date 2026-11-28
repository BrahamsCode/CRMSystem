<?php

use App\Mail\Promotions\PromotionMail;
use App\Services\Promotions\PointExpiryMailer;
use App\Services\Promotions\PushTrigger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/*
 * Caducidad de puntos (cron del legacy: sendPointShikkouMail, sendPointShikkouAlertMail y ...AlertMailMultiple).
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-26 10:00:00', 'Asia/Tokyo'));

    DB::table('admin_master')->insert(['cd' => 1, 'domain' => 'shop.crm-s.net', 'point_yukoukigen_flg' => '1']);
    DB::table('tb_office')->insert(['cd' => 1, 'start_work_time' => '9<>15']);
    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('tb_base_system_grandchild')->insert(['cd' => 1, 'system_name' => 'ポイント管理', 'system_flg' => '1']);
    DB::table('hatuban')->insert([['koumoku' => 'tb_point_log', 'kazu' => 100], ['koumoku' => 'push_master', 'kazu' => 50]]);

    $this->pushCalls = [];
    $calls = &$this->pushCalls;
    app()->instance(PushTrigger::class, new class($calls) implements PushTrigger {
        public function __construct(private array &$calls) {}

        public function trigger(string $type, int $masterCd): void
        {
            $this->calls[] = [$type, $masterCd];
        }
    });
});

function pointExpiryCustomer(int $cd, ?int $points, ?int $expires, array $extra = []): void
{
    DB::table('tb_customer')->insert($extra + ['cd' => $cd, 'id' => "id$cd", 'name' => "客$cd", 'email1' => "c$cd@example.com",
        'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1]);
    DB::table('tb_customer_point')->insert(['cd' => $cd, 'tb_customer_cd' => $cd, 'service_point' => $points, 'point_yukoukigen' => $expires]);
}

it('caduca los puntos vencidos: movimiento action_flg 7 y saldo a NULL, sin correo (comentado en el legacy)', function () {
    Mail::fake();
    DB::table('admin_master')->update(['point_shikkou_mail_naiyou' => '失効しました']);
    $past = Carbon::parse('2026-11-25 23:59:59', 'Asia/Tokyo')->getTimestamp();
    pointExpiryCustomer(1, 120, $past);
    pointExpiryCustomer(2, 50, $past + 86400 * 3);
    pointExpiryCustomer(3, 30, $past, ['disp_flg' => '9']);

    $this->artisan('promocion:point-shikkou')->expectsOutput('失効処理: 1件')->assertSuccessful();

    Mail::assertNothingSent();
    $log = DB::table('tb_point_log')->get();
    expect($log)->toHaveCount(1)
        ->and([(int) $log[0]->tb_customer_cd, (int) $log[0]->shori_mae_point, (int) $log[0]->shouhi_point, $log[0]->action_flg])->toBe([1, 120, 120, '7'])
        ->and(DB::table('tb_customer_point')->orderBy('cd')->pluck('service_point')->map(fn ($v) => $v === null ? null : (int) $v)->all())->toBe([null, 50, null])
        ->and(DB::table('tb_customer_point')->where('cd', 1)->value('point_yukoukigen'))->toBeNull();
});

it('no caduca nada sin texto de 失効 o con la caducidad desactivada, como el die() del legacy', function () {
    pointExpiryCustomer(1, 120, Carbon::parse('2026-11-20', 'Asia/Tokyo')->getTimestamp());
    $mailer = app(PointExpiryMailer::class);

    expect($mailer->expire())->toBe('メール内容は空です！！！');
    DB::table('admin_master')->update(['point_shikkou_mail_naiyou' => 'x', 'point_yukoukigen_flg' => '2']);
    expect($mailer->expire())->toBe('有効期限無効です！！！')
        ->and((int) DB::table('tb_customer_point')->value('service_point'))->toBe(120);
});

it('avisa con el texto de admin_master a quien vence el día configurado y deja un push', function () {
    Mail::fake();
    DB::table('admin_master')->update(['point_shikkou_alert' => '3', 'point_alert_mail_title' => 'まもなく失効', 'point_alert_mail_naiyou' => '{名前}様 {ポイント数}pt']);
    $inTwoDays = Carbon::parse('2026-11-28 23:59:59', 'Asia/Tokyo')->getTimestamp();
    pointExpiryCustomer(1, 80, $inTwoDays, ['tb_shop_cd' => 2]);
    pointExpiryCustomer(2, 0, $inTwoDays);
    pointExpiryCustomer(3, 80, $inTwoDays + 86400);

    $this->artisan('promocion:point-shikkou-alert')->expectsOutput('事前通知: 1件')->assertSuccessful();

    Mail::assertSent(PromotionMail::class, 1);
    Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('c1@example.com') && $m->mailSubject === 'まもなく失効'
        && str_starts_with($m->body, '客1様 80pt') && str_contains($m->body, '支店'));
    $push = DB::table('push_master')->first();
    expect([(int) $push->tb_shop_cd, $push->title, $push->extra_data])->toBe([-1, 'まもなく失効', PointExpiryMailer::PUSH_EXTRA_DATA])
        ->and($push->wherestr)->toBe(PointExpiryMailer::legacyAlertSql(3))
        ->and($this->pushCalls)->toBe([['push', (int) $push->cd]]);
});

it('sin afectados no deja push', function () {
    Mail::fake();
    DB::table('admin_master')->update(['point_shikkou_alert' => '3', 'point_alert_mail_naiyou' => 'x']);

    $this->artisan('promocion:point-shikkou-alert')->expectsOutput('事前通知: 0件')->assertSuccessful();

    expect(DB::table('push_master')->count())->toBe(0)->and($this->pushCalls)->toBe([]);
});

it('avisos múltiples de point_shikkou_alert: --cd y --due a su alert_time', function () {
    Mail::fake();
    DB::table('point_shikkou_alert')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'flg' => '1', 'title' => '1週間前', 'message' => '{名前}様 あと7日', 'alert_day' => '7', 'alert_time' => '10:00'],
        ['cd' => 2, 'tb_shop_cd' => 1, 'flg' => '1', 'title' => '前日', 'message' => '明日失効', 'alert_day' => '1', 'alert_time' => '18:30'],
        ['cd' => 3, 'tb_shop_cd' => 1, 'flg' => '0', 'title' => '無効', 'message' => 'x', 'alert_day' => '7', 'alert_time' => '10:00'],
        ['cd' => 4, 'tb_shop_cd' => 1, 'flg' => '1', 'title' => '0日', 'message' => 'x', 'alert_day' => '0', 'alert_time' => '10:00'],
    ]);
    pointExpiryCustomer(1, 10, Carbon::parse('2026-12-02 12:00', 'Asia/Tokyo')->getTimestamp());
    pointExpiryCustomer(2, 10, Carbon::parse('2026-11-26 20:00', 'Asia/Tokyo')->getTimestamp());
    $mailer = app(PointExpiryMailer::class);

    expect($mailer->dueAlertCds(now()))->toBe([1])
        ->and($mailer->dueAlertCds(Carbon::parse('2026-11-26 18:30', 'Asia/Tokyo')))->toBe([2]);

    $this->artisan('promocion:point-shikkou-alert', ['--due' => true])->expectsOutput('事前通知 1: 1件')->assertSuccessful();
    Mail::assertSent(PromotionMail::class, fn (PromotionMail $m) => $m->hasTo('c1@example.com') && $m->mailSubject === '1週間前');

    $this->artisan('promocion:point-shikkou-alert', ['--cd' => 2])->expectsOutput('事前通知 2: 1件')->assertSuccessful();
    Mail::assertSent(PromotionMail::class, 2);
    expect(DB::table('push_master')->orderBy('cd')->pluck('title')->all())->toBe(['1週間前', '前日']);

    // Sin ポイント管理 o con la caducidad desactivada no hay avisos
    DB::table('admin_master')->update(['point_yukoukigen_flg' => '2']);
    expect($mailer->dueAlertCds(now()))->toBe([]);
});

it('programa a la hora de admin_master, con la de apertura de tb_office si falta', function () {
    $mailer = app(PointExpiryMailer::class);
    $at = fn (string $time) => Carbon::parse("2026-11-26 $time", 'Asia/Tokyo');

    DB::table('admin_master')->update(['point_shikkou_time' => '03:30', 'point_shikkou_alert_time' => '']);
    expect($mailer->isDue('shikkou', $at('03:30')))->toBeTrue()
        ->and($mailer->isDue('shikkou', $at('03:31')))->toBeFalse()
        ->and($mailer->isDue('alert', $at('09:15')))->toBeTrue()
        ->and($mailer->isDue('alert', $at('00:00')))->toBeFalse();

    DB::table('admin_master')->update(['point_yukoukigen_flg' => '2']);
    expect($mailer->isDue('shikkou', $at('03:30')))->toBeFalse();

    DB::table('admin_master')->update(['point_yukoukigen_flg' => '1']);
    DB::table('tb_base_system_grandchild')->update(['system_flg' => '0']);
    expect($mailer->isDue('shikkou', $at('03:30')))->toBeFalse();
});
