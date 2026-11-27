<?php

use App\Models\Customers\Customer;
use Database\Seeders\Demo\PromocionDemoSeeder;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Nucleo/helpers.php';

/*
 * Datos de demostración de 販売促進管理 (CONVENCIONES §4): siembra, no duplica y no envía nada.
 */
function promocionDemoCounts(): array
{
    $tables = ['tb_mycoupon_master', 'tb_mycoupon', 'tb_mycoupon_used', 'mailmag_master', 'mailmag_sended_log', 'tb_mailmag_analyze',
        'send_schedule', 'push_master', 'push_follow_master', 'push_notifications', 'tb_point_rules', 'point_shikkou_alert',
        'tb_stamp_config', 'tb_stamp_rules', 'tb_stamp_oshirase_coupon', 'tb_c3_anketo_cate', 'tb_c3_anketo_sub',
        'tb_c3_anketo2_cate', 'tb_c3_anketo2_sub', 'tb_digital_signage'];

    return collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
}

it('siembra configuración y datos por cliente, sin duplicar en una segunda ejecución', function () {
    seedNucleo();
    mt_srand(20261127);
    Customer::factory()->count(20)->create();

    $this->seed(PromocionDemoSeeder::class);
    $counts = promocionDemoCounts();

    expect($counts['tb_mycoupon_master'])->toBe(8)
        ->and(DB::table('mailmag_master')->where('配信状況', '2')->count())->toBe(15)
        ->and(DB::table('mailmag_master')->where('配信状況', '2')->where('type_flg', 'deco')->count())->toBe(5)
        ->and(DB::table('mailmag_master')->where('配信状況', '1')->whereIn('type_flg', ['follow', 'auto'])->where('disp_flg', '7')->count())->toBe(3)
        ->and($counts['tb_mycoupon'])->toBeGreaterThan(0)
        ->and($counts['tb_mycoupon_used'])->toBeGreaterThan(0)
        ->and($counts['mailmag_sended_log'])->toBeGreaterThan(100)
        ->and($counts['tb_mailmag_analyze'])->toBeGreaterThan(30)
        ->and($counts['push_master'])->toBe(4)
        ->and($counts['push_notifications'])->toBeGreaterThan(30)
        ->and($counts['tb_c3_anketo_sub'])->toBe(36)
        ->and($counts['tb_c3_anketo2_sub'])->toBe(36)
        ->and($counts['tb_digital_signage'])->toBe(5)
        // Nada sale solo: sin send_schedule y las reglas de push en 停止
        ->and($counts['send_schedule'])->toBe(0)
        ->and(DB::table('push_follow_master')->where('disp_flg', '<>', '7')->count())->toBe(0);

    // 配信総数 = destinatarios
    $mail = DB::table('mailmag_master')->where('配信状況', '2')->orderBy('cd')->first();
    expect((int) $mail->{'配信総数'})->toBe(DB::table('mailmag_sended_log')->where('mailmag_master_cd', $mail->cd)->count());

    $this->seed(PromocionDemoSeeder::class);
    expect(promocionDemoCounts())->toBe($counts);

    // Las pantallas los muestran
    DB::table('tb_base_system_child')->insert([
        ['cd' => 30, 'tb_base_system_cd' => 3, 'system_name' => 'メルマガ管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 34, 'tb_base_system_cd' => 3, 'system_name' => 'プッシュ通知', 'system_flg' => '1', 'rank' => 2],
    ]);
    loginAs('master', 'secreto1');
    $this->get(route('admin.promocion.mail.past'))->assertOk()->assertSee('年末年始の営業日のお知らせ');
    $this->get(route('admin.promocion.deco.past'))->assertOk()->assertSee('忘年会・新年会のご予約受付中');
    $this->get(route('admin.promocion.mail.future'))->assertOk()->assertSee('クリスマス限定コースのご案内');
    $this->get(route('admin.promocion.coupons.index'))->assertOk()->assertSee('お誕生日おめでとうクーポン');
    $this->get(route('admin.promocion.clicks.mail'))->assertOk();
    $this->get(route('admin.promocion.news.index'))->assertOk()->assertSee('冬の感謝祭を開催します');
    $this->get(route('admin.promocion.follow.future'))->assertOk()->assertSee('もうすぐお誕生日ですね');
    $this->get(route('admin.promocion.auto.future'))->assertOk()->assertSee('毎月1日のお知らせ');
    $this->get(route('admin.promocion.coupons.ranking'))->assertOk();
    $this->get(route('admin.promocion.coupons.stats'))->assertOk();
    $this->get(route('admin.promocion.points.rules.index'))->assertOk();
    foreach (['clicks.deco', 'clicks.coupon', 'anketo.index', 'anketo2.index'] as $name) {
        $this->get(route("admin.promocion.$name"))->assertOk();
    }
    $this->get(route('admin.promocion.push.past'))->assertOk()->assertSee('秋の味覚フェア開催中');
    $this->get(route('admin.promocion.push.history'))->assertOk();
    $mailCd = DB::table('mailmag_master')->where('タイトル', '夏季休業のお知らせ')->value('cd');
    $this->get(route('admin.promocion.mail.past', ['mailmagcd' => $mailCd]))->assertOk();
});

it('sin socios siembra solo la configuración y completa la parte por cliente cuando los hay', function () {
    seedNucleo();
    DB::table('tb_customer')->delete();

    $this->seed(PromocionDemoSeeder::class);
    expect(DB::table('tb_mycoupon_master')->count())->toBe(8)
        ->and(DB::table('mailmag_sended_log')->count())->toBe(0)
        ->and(DB::table('tb_mycoupon')->count())->toBe(0);

    Customer::factory()->count(5)->create();
    $this->seed(PromocionDemoSeeder::class);

    expect(DB::table('tb_mycoupon_master')->count())->toBe(8)
        ->and(DB::table('mailmag_master')->count())->toBe(21)
        ->and(DB::table('mailmag_sended_log')->count())->toBeGreaterThan(0)
        ->and(DB::table('tb_c3_anketo_sub')->count())->toBe(15);
});
