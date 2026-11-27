<?php

use Illuminate\Support\Facades\DB;

/* Reproductor digitalsignage/ (index.php → index2.php), ルーレット y スロット, sin login */

function snProgram(array $attributes): void
{
    DB::table('tb_c7_movie_program')->insert($attributes + [
        'tb_shop_cd' => 1, 'name' => 'P', 'disp_flg' => '1', 'update_date' => '1700000000', 'vertical_setting' => 'landscape',
    ]);
}

function snDisplay(int $cd, string $programCd): void
{
    DB::table('tb_display')->insert(['cd' => $cd, 'tb_shop_cd' => 1, 'name' => 'D', 'tb_c7_movie_program_cds' => $programCd, 'disp_flg' => '1']);
}

it('carga el reproductor de archivos con sus sondeos, su XML y la テロップ', function () {
    snProgram([
        'cd' => 30, 'disp_conf' => '1', 'telop_comment' => '本日のおすすめ', 'feli_act_flg' => '2',
        'feli_tb_mycoupon_master_cds' => '71', 'act_flg' => '1', 'tb_mycoupon_master_cds' => '<>72',
    ]);
    snDisplay(40, '30');

    $this->get('/digitalsignage/index2.php?shop_cd=1&display_cd=40')
        ->assertOk()
        ->assertSee('<title>digital signage</title>', false)
        ->assertSee('<base href="'.url('digitalsignage').'/" />', false)
        ->assertSee('var xmlMod = "1700000000";', false)
        ->assertSee('$("#xmlCheckResult").load("ajax_xml_check_2.php",{', false)
        ->assertSee('data-xml="../hp/uploads/c7/display/30.xml"', false)
        ->assertSee('<span>本日のおすすめ</span>', false)
        // 新規登録 (feli_act_flg 2) con el cupón de フェリカ, y 画面タッチ con el primer cupón marcado
        ->assertSee('ajax_shinkyaku_print_coupon_crawl_2.php?time=', false)
        ->assertSee('&tb_mycoupon_master_cd=71', false)
        ->assertSee('id="ShinkyakuPrintCoupon_kanshi_time"', false)
        ->assertSee('ajax_touch_print_coupon_crawl_2.php?time=', false)
        ->assertSee('&tb_mycoupon_master_cd=72', false)
        ->assertDontSee('function ajaxMujyoukenPrintCouponCrawl(', false)
        ->assertDontSee('<object', false);

    // index.php y la carpeta dan lo mismo (index.php hacía require de index2.php)
    $this->get('/digitalsignage/?display_cd=40')->assertOk()->assertSee('shop_cd:1,', false);
    $this->get('/digitalsignage/index.php?display_cd=40')->assertOk()->assertSee('digital signage');
});

it('muestra 「ただ今システムがビジーです」 sin display y los errores del legacy', function () {
    $this->get('/digitalsignage/index2.php?shop_cd=1')
        ->assertOk()
        ->assertContent('<p style="text-align: center;">ただ今システムがビジーです。<br />再起動するかシステム管理者にお問い合わせ下さい。</p>');

    $this->get('/digitalsignage/index2.php?display_cd=999')->assertContent('error2');

    snDisplay(41, '');
    $this->get('/digitalsignage/index2.php?display_cd=41')->assertContent('error1');
});

it('en modo システム内蔵 no reproduce archivos y abre la ルーレット o la スロット al tocar', function () {
    snProgram(['cd' => 31, 'disp_conf' => '2', 'tb_dispaly_systems_cds' => '5', 'act_flg' => '1']);
    snDisplay(42, '31');

    $this->get('/digitalsignage/index2.php?shop_cd=1&display_cd=42')
        ->assertOk()
        ->assertSee('location.href = "./roulette.php?shop_cd=1&display_cd=42";', false)
        ->assertSee('ajaxTouchRoulettePrintCrawl();', false)
        ->assertDontSee('id="signagePlayer"', false)
        ->assertDontSee('function ajaxTouchPrintCouponCrawl(', false);

    snProgram(['cd' => 32, 'disp_conf' => '2', 'tb_dispaly_systems_cds' => '2']);
    snDisplay(43, '32');
    $this->get('/digitalsignage/index2.php?shop_cd=1&display_cd=43')
        ->assertSee('location.href = "./slot.php?shop_cd=1&display_cd=43";', false);

    snProgram(['cd' => 33, 'disp_conf' => '2', 'tb_dispaly_systems_cds' => '1']);
    snDisplay(44, '33');
    $this->get('/digitalsignage/index2.php?shop_cd=1&display_cd=44')
        ->assertSee('どのお店をお探しですか')
        ->assertSee('$("#ajaxFloormapPrintCouponCrawlResult").load("fla/floormap/ajax_print_crawl.php",{', false);
});

it('sirve la ルーレット y la スロット en HTML5 con su vuelta al reproductor', function () {
    $this->get('/digitalsignage/roulette.php?shop_cd=1&display_cd=42')
        ->assertOk()
        ->assertSee('location.href = "./?shop_cd=1&display_cd=42&coupon_no=" + couponNo + "";', false)
        ->assertSee('load_data.xml', false)
        ->assertSee('<canvas id="roulette"', false);

    $this->get('/digitalsignage/slot.php?shop_cd=1&display_cd=43')
        ->assertOk()
        ->assertSee('<meta http-equiv="Refresh" content="14; URL=./?shop_cd=1&display_cd=43">', false)
        ->assertSee('<canvas id="slot"', false);
});
