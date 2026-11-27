<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/* Cupones que imprime el reproductor (ajax_*_print_coupon_crawl*.php y fla/floormap/ajax_print_crawl.php) */

beforeEach(function () {
    DB::table('tb_shop')->insert([
        'cd' => 1, 'kaishamei' => '本店', 'zip' => '530-0001', 'pref' => '大阪府', 'add' => '大阪市北区',
        'add_number' => '1-1', 'add_build' => 'ビル3F', 'tel' => '06-0000-0000', 'email' => 'info@example.jp',
        'shop_url' => 'https://example.jp', 'disp_flg' => '1',
    ]);
    DB::table('tb_mycoupon_master')->insert([
        'cd' => 71, 'tb_shop_cd' => 1, 'title' => '来店ありがとう', 'comment' => "ドリンク1杯\n無料", 'attention' => '他券併用不可<>1回限り',
        'coupon_kubun' => '8', 'limit_flg' => '1', 'disp_flg' => '1', 'hyouji_flg' => '1', 'update_date' => '1',
    ]);
});

function snExpectedTicket(string $limit): string
{
    return "<div class=\"kobetsuPrintArea\">\n"
        ."NO.71<br />\n"
        ."<span class=\"couponTitle\">来店ありがとう</span><br />\n"
        ."<span class=\"couponComment\">ドリンク1杯<br />\n無料</span><br />\n"
        ."他券併用不可<br />\n1回限り<br />\n"
        ."<span class=\"couponLimit\">有効期限：{$limit}</span><br />\n"
        .'<hr />'
        ."本店<br />\n530-0001<br />\n大阪府大阪市北区1-1<br />\nビル3F<br />\n06-0000-0000<br />\ninfo@example.jp<br />\nhttps://example.jp<br />\n"
        .'</div>';
}

it('imprime el cupón de フェリカ cuando hay una visita nueva en la tienda', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 27, 12, 0, 0, 'Asia/Tokyo'));
    DB::table('tb_raiten_rireki')->insert(['cd' => 1, 'user_cd' => 5, 'shop_cd' => 1, 'raiten_time' => '1764200000']);

    $response = $this->post('/digitalsignage/ajax_kaiin_raiten_print_coupon_crawl.php?tb_mycoupon_master_cd=71', [
        'KaiinRaitenPrintCoupon_kanshi_time' => '1764100000', 'shop_cd' => 1,
    ]);

    $response->assertOk()->assertContent(
        snExpectedTicket('2026/12/04')
        .'<script type="text/javascript">imprime();</script>'
        ."\r\n"
        .'<input type="hidden" name="KaiinRaitenPrintCoupon_kanshi_time" id="KaiinRaitenPrintCoupon_kanshi_time" value="'.now()->getTimestamp().'" />'
    );

    // Sin visitas nuevas desde el último 監視時間: solo el hidden
    $this->post('/digitalsignage/ajax_kaiin_raiten_print_coupon_crawl.php?tb_mycoupon_master_cd=71', [
        'KaiinRaitenPrintCoupon_kanshi_time' => '1764200000', 'shop_cd' => 1,
    ])->assertContent('<input type="hidden" name="KaiinRaitenPrintCoupon_kanshi_time" id="KaiinRaitenPrintCoupon_kanshi_time" value="'.now()->getTimestamp().'" />');

    Carbon::setTestNow();
});

it('imprime con 無条件 por alta o visita y con 新規 solo por alta de cliente', function () {
    DB::table('tb_customer')->insert(['cd' => 9, 'tb_shop_cd' => 1, 'touroku_date' => '1764200000']);

    $this->post('/digitalsignage/ajax_shinkyaku_print_coupon_crawl_2.php?tb_mycoupon_master_cd=71', [
        'ShinkyakuPrintCoupon_kanshi_time' => '1764100000', 'shop_cd' => 1,
    ])->assertSee('<span class="couponTitle">来店ありがとう</span>', false)->assertSee('imprime();', false);

    $this->post('/digitalsignage/ajax_Mujyouken_print_coupon_crawl_2.php?tb_mycoupon_master_cd=71', [
        'mujyoukenPrintCoupon_kanshi_time' => '1764100000', 'shop_cd' => 1,
    ])->assertSee('NO.71', false)->assertSee('id="mujyoukenPrintCoupon_kanshi_time"', false);

    $this->post('/digitalsignage/ajax_shinkyaku_print_coupon_crawl_2.php?tb_mycoupon_master_cd=71', [
        'ShinkyakuPrintCoupon_kanshi_time' => '1764300000', 'shop_cd' => 1,
    ])->assertDontSee('NO.71', false);
});

it('imprime el cupón del toque (_2) y nada si el cupón no está visible', function () {
    $this->post('/digitalsignage/ajax_touch_print_coupon_crawl_2.php?tb_mycoupon_master_cd=71', ['shop_cd' => 1])
        ->assertSee('NO.71', false)
        ->assertSee('<script type="text/javascript">imprime();</script>', false);

    DB::table('tb_mycoupon_master')->where('cd', 71)->update(['hyouji_flg' => '0']);
    $this->post('/digitalsignage/ajax_touch_print_coupon_crawl_2.php?tb_mycoupon_master_cd=71', ['shop_cd' => 1])
        ->assertContent('');
});

it('imprime los cupones pedidos desde マイページ y los marca como usados', function () {
    DB::table('tb_mycoupon_print')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'tb_customer_cd' => 5, 'touroku_date' => '1764200000']);
    DB::table('tb_mycoupon')->insert([
        'cd' => 500, 'tb_mycoupon_master_cd' => 71, 'tb_shop_cd' => 1, 'tb_customer_cd' => 5,
        'time_limit' => (string) Carbon::create(2026, 12, 31, 12, 0, 0, 'Asia/Tokyo')->getTimestamp(), 'print_flg' => '1',
    ]);

    $this->post('/digitalsignage/ajax_kaiin_mycoupon_print_crawl.php', ['KaiinMycouponPrint_kanshi_time' => '1764100000', 'shop_cd' => 1])
        ->assertSee('<span class="couponLimit">有効期限：2026年12月31日</span>', false)
        ->assertSee('id="KaiinMycouponPrint_kanshi_time"', false);

    expect(DB::table('tb_mycoupon')->where('cd', 500)->exists())->toBeFalse()
        ->and(DB::table('tb_mycoupon_used')->where('cd', 500)->value('tb_shop_cd'))->toBe(1);
});

it('imprime el cupón de una tienda desde インフォメーション y avisa si no hay', function () {
    $this->post('/digitalsignage/fla/floormap/ajax_print_crawl.php', ['cd' => 1, 'print_type' => 'クーポン'])
        ->assertSee("☆☆☆☆☆☆☆☆☆☆☆<br />\n<span style=\"font-weight:bold\">来店ありがとう<br />\n", false)
        ->assertSee('<img src="fla/floormap/sampleqr.jpg"><br />', false)
        ->assertSee('<script>imprime();</script>', false);

    $this->post('/digitalsignage/fla/floormap/ajax_print_crawl.php', ['cd' => 999, 'print_type' => '求人情報'])
        ->assertContent('<script>alert("現在求人情報はありません。");</script>');
});
