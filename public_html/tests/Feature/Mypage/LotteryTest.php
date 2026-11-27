<?php

use App\Services\Mypage\LotteryConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/helpers.php';

/* ガチャリン管理 各種設定 (kujibiki/kujibiki_config.php) en contents_master tb_c6_kujibiki */

beforeEach(function () {
    mypageLogin();
});

function lotteryRows(string $field): array
{
    return DB::table('contents_master')->where('category', 'tb_c6_kujibiki')->where('field', $field)
        ->orderBy('cd')->get(['text_data', 'int_data', 'float_data'])->map(fn ($row) => (array) $row)->all();
}

it('carga la pantalla con los valores por defecto (ハズレ 100%)', function () {
    $this->get(route('admin.mypage.lottery.edit'))
        ->assertOk()
        ->assertSee('<title>ガチャリン管理 各種設定 - shop</title>', false)
        ->assertSee('id="kujibiki_disp_flg1" value="9" checked="checked"', false)
        ->assertSee('<td align="center" valign="middle">100&nbsp;％</td>', false);
});

it('guarda 利用設定, 1日の利用可能回数 y el plazo de canje', function () {
    $url = route('admin.mypage.lottery.update');

    $this->post($url, ['act' => 'change_kujibiki_disp_flg', 'kujibiki_disp_flg' => '1'])->assertRedirect(route('admin.mypage.lottery.edit'));
    $this->post($url, ['act' => 'change_kujibiki_max', 'kujibiki_max' => '3']);
    $this->post($url, ['act' => 'change_kujibiki_limit_flg', 'kujibiki_limit_flg' => '5', 'time_limit' => '2026/12/31']);

    expect(lotteryRows('disp_flg'))->toBe([['text_data' => '1', 'int_data' => null, 'float_data' => null]])
        ->and(lotteryRows('max')[0]['int_data'])->toBe(3)
        ->and(lotteryRows('limit_flg')[0]['text_data'])->toBe('5')
        ->and(lotteryRows('limit_flg')[0]['int_data'])->toBe(Carbon::parse('2026-12-31 23:59:59', 'Asia/Tokyo')->getTimestamp());

    $this->post($url, ['act' => 'change_kujibiki_limit_flg', 'kujibiki_limit_flg' => '4', 'time_limit' => '10']);
    expect(lotteryRows('limit_flg'))->toBe([['text_data' => '4', 'int_data' => 10, 'float_data' => null]]);

    $this->get(route('admin.mypage.lottery.edit'))
        ->assertSee('id="kujibiki_disp_flg2" value="1" checked="checked"', false)
        ->assertSee('id="kujibiki_max" class="input2S" value="3"', false)
        ->assertSee('<option value="4" selected>当選から○○日間</option>', false);
});

it('guarda el 抽選設定 con filas rate y prize_count y la ハズレ como resto', function () {
    $url = route('admin.mypage.lottery.update');
    $rates = [
        'act' => 'change_kujibiki_rate',
        'kujibiki_dia_rate' => '1', 'kujibiki_dia_num' => '1',
        'kujibiki_gold_rate' => '4', 'kujibiki_gold_num' => '5',
        'kujibiki_silver_rate' => '10', 'kujibiki_silver_num' => '',
        'kujibiki_bronze_rate' => '25', 'kujibiki_bronze_num' => '100',
    ];

    $this->post($url, $rates)->assertRedirect();
    $this->post($url, ['kujibiki_dia_rate' => '2'] + $rates);

    expect(lotteryRows('rate'))->toBe([
        ['text_data' => 'ダイヤ', 'int_data' => 1, 'float_data' => 2.0],
        ['text_data' => 'ゴールド', 'int_data' => 5, 'float_data' => 4.0],
        ['text_data' => 'シルバー', 'int_data' => 0, 'float_data' => 10.0],
        ['text_data' => 'ブロンズ', 'int_data' => 100, 'float_data' => 25.0],
        ['text_data' => 'ハズレ', 'int_data' => null, 'float_data' => 59.0],
    ])->and(array_column(lotteryRows('prize_count'), 'int_data'))->toBe([0, 0, 0, 0]);

    // Más de 100% entre los cuatro: no se guarda (el legacy lo impedía solo en JS)
    $this->post($url, ['kujibiki_bronze_rate' => '90'] + $rates)->assertSessionHasErrors();
    expect(app(LotteryConfig::class)->read()['miss_rate'])->toBe(59.0);

    $this->get(route('admin.mypage.lottery.edit'))
        ->assertSee('id="kujibiki_dia_rate" class="input2S" value="2"', false)
        ->assertSee('<td align="center" valign="middle">59&nbsp;％</td>', false);
});
