<?php

use App\Providers\PromocionServiceProvider;
use App\Services\Promotions\PushSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/push_helpers.php';

/*
 * M5: promocion:send-follow-push (sendFollowPush.php, cada 5 minutos si プッシュ通知 está activo) y el calendario de Push.php.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    seedNucleo();
    pushSeed();
    Http::fake();
});

it('primera pasada: solo anota la hora; luego genera los push que tocan y reprograma', function () {
    $now = Carbon::now()->getTimestamp();
    pushFollow(['cd' => 1, 'type_flg' => 'scheduled', 'send_parameter' => 'send_date=2026/11/27,point_expiration_date=2026/12/31', 'send_time' => '09:58',
        'post_point' => 20, 'next_send_date' => $now - 120, 'pic_link' => serialize([0 => 'https://example.com'])]);
    pushFollow(['cd' => 2, 'type_flg' => 'follow', 'send_time' => '09:59', 'next_send_date' => $now - 60, 'post_point' => 5, 'point_expiration_days' => 3]);
    pushFollow(['cd' => 3, 'type_flg' => 'auto', 'send_parameter' => '2<>1', 'send_time' => '09:00', 'next_send_date' => $now - 7200]); // anterior a la última pasada
    pushFollow(['cd' => 4, 'type_flg' => 'follow', 'next_send_date' => $now - 60, 'disp_flg' => '7']); // parada
    pushFollow(['cd' => 5, 'type_flg' => 'follow', 'next_send_date' => $now + 600]); // aún no

    $this->artisan('promocion:send-follow-push')->assertSuccessful();
    expect(DB::table('push_master')->count())->toBe(0)
        ->and(DB::table('contents_master')->where('category', 'push_follow')->value('int_data'))->toBe($now);

    DB::table('contents_master')->where('category', 'push_follow')->update(['int_data' => $now - 300]);
    $this->artisan('promocion:send-follow-push')->assertSuccessful();

    $pushes = DB::table('push_master')->orderBy('cd')->get();
    expect($pushes)->toHaveCount(2);
    expect($pushes[0])->cd->toBe(700)->push_follow_master_cd->toBe(1)->type_flg->toBe('scheduled')->post_type->toBe('2')->post_point->toBe(20)
        ->point_expiration_date->toBe(Carbon::parse('2026-12-31 23:59:59', 'Asia/Tokyo')->getTimestamp())
        ->wherestr->toBe("WHERE disp_flg = '1'")->version->toBe(2);
    expect(unserialize($pushes[0]->pic_link))->toBe([0 => 'https://example.com']);
    expect($pushes[1])->push_follow_master_cd->toBe(2)->type_flg->toBe('follow')
        ->point_expiration_date->toBe(Carbon::parse('2026-11-30 23:59:59', 'Asia/Tokyo')->getTimestamp());

    // El programado se cierra; フォロー y 自動 pasan al siguiente envío
    expect(DB::table('push_follow_master')->where('cd', 1)->value('disp_flg'))->toBe('9')
        ->and(DB::table('push_follow_master')->where('cd', 2)->value('next_send_date'))->toBe(Carbon::parse('2026-11-28 09:59', 'Asia/Tokyo')->getTimestamp())
        ->and(DB::table('push_follow_master')->where('cd', 3)->value('next_send_date'))->toBe(Carbon::parse('2026-11-30 09:00', 'Asia/Tokyo')->getTimestamp());

    Http::assertSentCount(2);
    Http::assertSent(fn ($r) => (int) $r['cd'] === 700 && $r['type'] === 'push');
});

it('con プッシュ通知 apagado no hace nada; el Scheduler lo tiene cada 5 minutos', function () {
    DB::table('tb_base_system_child')->where('system_name', 'プッシュ通知')->update(['system_flg' => '0']);
    pushFollow(['cd' => 1, 'type_flg' => 'follow', 'next_send_date' => 1]);
    DB::table('contents_master')->insert(['cd' => 1, 'category' => 'push_follow', 'field' => 'last_push_date', 'int_data' => 0, 'disp_flg' => '1']);

    $this->artisan('promocion:send-follow-push')->assertSuccessful();
    expect(DB::table('push_master')->count())->toBe(0);

    $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'promocion:send-follow-push'));
    expect($event)->not->toBeNull()->and($event->expression)->toBe('*/5 * * * *')
        ->and(PromocionServiceProvider::TASKS)->toHaveKey(\App\Console\Commands\Promotions\SendFollowPushCommand::class);
});

it('Push.php: próximo envío de programado, フォロー y 自動 (días del mes con fin de mes y días de la semana)', function () {
    $s = new PushSchedule;
    $at = fn (string $d) => Carbon::parse($d, 'Asia/Tokyo')->getTimestamp();
    $now = Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'); // viernes

    expect($s->nextSendDate('scheduled', '09:00', 'send_date=2026/12/01', $now))->toBe($at('2026-12-01 09:00'))
        ->and($s->nextSendDate('scheduled', '09:00', 'send_date=2026/11/26', $now))->toBeNull()
        ->and($s->nextSendDate('follow', '11:00', '', $now))->toBe($at('2026-11-27 11:00'))
        ->and($s->nextSendDate('follow', '09:00', '', $now))->toBe($at('2026-11-28 09:00'))
        ->and($s->nextSendDate('auto', '09:00', '1<>11,02<>32', $now))->toBe($at('2026-11-30 09:00'))
        ->and($s->nextSendDate('auto', '09:00', '1<>02<>32', $now))->toBe($at('2027-02-28 09:00'))
        ->and($s->nextSendDate('auto', '09:00', '1<>01<>05', $now))->toBe($at('2027-01-05 09:00'))
        ->and($s->nextSendDate('auto', '11:00', '2<>5', $now))->toBe($at('2026-11-27 11:00'))
        ->and($s->nextSendDate('auto', '09:00', '2<>1,5', $now))->toBe($at('2026-11-30 09:00'))
        ->and($s->nextSendDate('auto', '25:00', '2<>1', $now))->toBeNull()
        ->and($s->describe('2<>0,6'))->toBe('配信曜日：日曜日 土曜日');
});
