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
    // Socios demo de S2 (demo%@example.com): reciben también los casos límite
    Customer::factory()->count(20)->sequence(fn ($s) => ['email1' => 'demo'.($s->index + 1).'@example.com'])->create();

    $this->seed(PromocionDemoSeeder::class);
    $counts = promocionDemoCounts();
    $demo = fn () => DB::table('mailmag_master')->where('備考', PromocionDemoSeeder::DEMO_MARK);

    expect($counts['tb_mycoupon_master'])->toBe(8 + 25)
        ->and($demo()->where('配信状況', '2')->count())->toBe(15)
        ->and($demo()->where('配信状況', '2')->where('type_flg', 'deco')->count())->toBe(5)
        ->and($demo()->where('配信状況', '1')->whereIn('type_flg', ['follow', 'auto'])->where('disp_flg', '7')->count())->toBe(3)
        ->and($demo()->where('登録者', '1')->count())->toBe(21)
        ->and($counts['tb_mycoupon'])->toBeGreaterThan(0)
        ->and($counts['tb_mycoupon_used'])->toBeGreaterThan(0)
        ->and($counts['mailmag_sended_log'])->toBeGreaterThan(100)
        ->and($counts['tb_mailmag_analyze'])->toBeGreaterThan(30)
        ->and($counts['push_master'])->toBe(4 + 23)
        ->and($counts['push_notifications'])->toBeGreaterThan(30)
        ->and($counts['tb_c3_anketo_sub'])->toBe(36)
        ->and($counts['tb_c3_anketo2_sub'])->toBe(36)
        ->and($counts['tb_digital_signage'])->toBe(5 + 2)
        // Nada sale solo: sin send_schedule y las reglas de push en 停止
        ->and($counts['send_schedule'])->toBe(0)
        ->and(DB::table('push_follow_master')->where('disp_flg', '<>', '7')->count())->toBe(0);

    // 配信総数 = destinatarios
    $mail = $demo()->where('配信状況', '2')->orderBy('cd')->first();
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
    expect(DB::table('tb_mycoupon_master')->count())->toBe(8 + 25)
        ->and(DB::table('mailmag_sended_log')->count())->toBe(0)
        ->and(DB::table('tb_mycoupon')->count())->toBe(0);

    Customer::factory()->count(5)->create();
    $this->seed(PromocionDemoSeeder::class);

    expect(DB::table('tb_mycoupon_master')->count())->toBe(8 + 25)
        ->and(DB::table('mailmag_master')->count())->toBe(21 + 41)
        ->and(DB::table('mailmag_sended_log')->count())->toBeGreaterThan(0)
        ->and(DB::table('tb_c3_anketo_sub')->count())->toBe(15);
});

it('no se sale de hatuban.kazu cuando la tabla ya tiene cd en formato legacy (epoch + tienda + aleatorio)', function () {
    seedNucleo();
    // Filas que crea la pantalla ポイント仕様管理 (createHatsuban) y una tabla de hatuban con un cd legacy
    DB::table('point_shikkou_alert')->insert(['cd' => 1795779603001977718, 'tb_shop_cd' => 1, 'flg' => '1', 'title' => '', 'sort' => 0]);
    DB::table('tb_stamp_rules')->insert(['cd' => 1795779603001977719, 'tb_shop_cd' => 1, 'type_flg' => '1', 'hyouji_flg' => '1']);
    DB::table('hatuban')->where('koumoku', 'tb_stamp_rules')->delete();

    $this->seed(PromocionDemoSeeder::class);

    expect(DB::table('point_shikkou_alert')->count())->toBe(3)
        ->and(DB::table('tb_stamp_rules')->count())->toBe(3)
        ->and(DB::table('point_shikkou_alert')->distinct()->count('cd'))->toBe(3)
        ->and(DB::table('tb_stamp_rules')->distinct()->count('cd'))->toBe(3)
        ->and(DB::table('hatuban')->whereIn('koumoku', ['point_shikkou_alert', 'tb_stamp_rules'])->exists())->toBeFalse();
});

it('siembra los casos límite: varias tiendas, fin de mes y de año, 29/02, japonés largo, estados y paginación', function () {
    seedNucleo();
    DB::table('tb_shop')->insert([
        ['cd' => 2, 'master_cd' => 1, 'kaishamei' => '梅田店', 'disp_flg' => '1'],
        ['cd' => 3, 'master_cd' => 1, 'kaishamei' => '難波店', 'disp_flg' => '1'],
    ]);
    Customer::factory()->count(30)->sequence(fn ($s) => ['email1' => 'demo'.($s->index + 1).'@example.com', 'mailmaga_flg' => '1'])->create();
    DB::table('tb_kingaku_rank_master')->insert(['cd' => 1, 'name' => 'ブロンズ', 'jyouken1' => 0, 'jyouken2' => 29999, 'disp_flg' => '1', 'hyouji_flg' => '1', 'rank' => 1]);

    $this->seed(PromocionDemoSeeder::class);
    $edge = fn () => DB::table('mailmag_master')->where('備考', PromocionDemoSeeder::EDGE_MARK);

    // Envíos: repartidos por tienda (登録者) y 本部, fechas límite, largo, sin destinatarios, con errores y de baja
    expect($edge()->where('配信状況', '2')->count())->toBe(38)
        ->and($edge()->where('配信状況', '1')->count())->toBe(3)
        ->and($edge()->distinct()->pluck('登録者')->sort()->values()->all())->toBe(['-1', '1', '2', '3'])
        ->and($edge()->where('配信日', '2024/02/29')->exists())->toBeTrue()
        ->and($edge()->where('配信日', '2028/02/29')->exists())->toBeTrue()
        ->and($edge()->where('配信日', 'like', '%/12/31')->exists())->toBeTrue()
        ->and($edge()->where('disp_flg', '9')->count())->toBe(2)
        ->and(mb_strlen($edge()->where('タイトル', 'like', '【重要・全店舗%')->value('タイトル')))->toBe(100);
    $empty = $edge()->where('タイトル', 'like', '【対象者なし】%')->first();
    expect((int) $empty->{'配信総数'})->toBe(0)
        ->and(DB::table('mailmag_sended_log')->where('mailmag_master_cd', $empty->cd)->exists())->toBeFalse();
    $errors = $edge()->where('タイトル', 'like', '【不達あり】%')->value('cd');
    expect(DB::table('tb_customer')->join('mailmag_sended_log', 'tb_customer.cd', '=', 'mailmag_sended_log.tb_customer_cd')
        ->where('mailmag_master_cd', $errors)->where('error_send_cnt', '>', 0)->count())->toBe(6)
        ->and(DB::table('tb_customer')->where('mailmaga_flg', '9')->count())->toBe(4);

    // Cupones: caducado, 29/02, de baja, oculto, 0 y 999999 puntos; entregados caducados, vigentes y usados
    expect(DB::table('tb_mycoupon_master')->where('coupon_kubun', '')->where('disp_flg', '1')->count())->toBeGreaterThan(20)
        ->and(DB::table('tb_mycoupon_master')->where('disp_flg', '9')->count())->toBe(2)
        ->and(DB::table('tb_mycoupon_master')->whereIn('shouhi_point', [0, 999999])->count())->toBe(2)
        ->and(DB::table('tb_mycoupon')->where('time_limit', '<', (string) now()->getTimestamp())->exists())->toBeTrue()
        ->and(DB::table('tb_mycoupon_used')->exists())->toBeTrue();

    // Push, encuestas sin respuestas y rangos
    expect(DB::table('push_master')->where('disp_flg', '1')->count())->toBeGreaterThan(20)
        ->and(DB::table('push_master')->distinct()->count('tb_shop_cd'))->toBe(4)
        ->and(DB::table('tb_c3_anketo_cate')->whereNull('hyouji_flg')->count())->toBe(3)
        ->and(DB::table('tb_c3_anketo_sub')->whereIn('tb_c3_anketo_cate_cd', DB::table('tb_c3_anketo_cate')->whereNull('hyouji_flg')->pluck('cd'))->exists())->toBeFalse()
        ->and(DB::table('tb_kingaku_rank_master')->count())->toBe(2)
        ->and(DB::table('tb_customer')->whereNotNull('kingaku_rank_now')->count())->toBe(3)
        ->and(DB::table('tb_customer')->whereNotNull('raiten_rank_pre')->count())->toBe(4);

    // Una segunda ejecución no duplica
    $errorCount = fn () => DB::table('tb_customer')->where('error_send_cnt', '>', 0)->count();
    $before = promocionDemoCounts() + ['customers_error' => $errorCount()];
    $this->seed(PromocionDemoSeeder::class);
    expect(promocionDemoCounts() + ['customers_error' => $errorCount()])->toBe($before);

    // Las pantallas paginan y muestran los casos
    DB::table('tb_base_system_child')->insert([
        ['cd' => 30, 'tb_base_system_cd' => 3, 'system_name' => 'メルマガ管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 34, 'tb_base_system_cd' => 3, 'system_name' => 'プッシュ通知', 'system_flg' => '1', 'rank' => 2],
    ]);
    loginAs('master', 'secreto1');
    $this->get(route('admin.promocion.mail.past', ['page' => 2]))->assertOk()->assertSee('月末感謝デー');
    $this->get(route('admin.promocion.mail.past', ['mailmagcd' => $empty->cd]))->assertOk();
    $this->get(route('admin.promocion.mail.future'))->assertOk()->assertSee('【予約】閏日のお知らせ');
    $this->get(route('admin.promocion.deco.past', ['page' => 2]))->assertOk();
    $this->get(route('admin.promocion.coupons.index', ['shop_cd' => -1, 'page' => 2]))->assertOk();
    $this->get(route('admin.promocion.push.past', ['page' => 2]))->assertOk();
    $this->get(route('admin.promocion.anketo.index'))->assertOk()->assertSee('【設問なし】作成中のアンケート');
    $this->get(route('admin.promocion.anketo2.index'))->assertOk()->assertSee('【回答なし】店内アンケート（新メニュー）')->assertDontSee('【削除済み】旧アンケート');
    $this->get(route('admin.promocion.news.index'))->assertOk()->assertDontSee('【削除済み】旧キャンペーンのお知らせ');
});
