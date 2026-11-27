<?php

use App\Models\Terminales\PaperCard;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* ペーパーサイネージ 集計 (aggregate.php) y CSV (csv_output.php) sobre url_transfer_data */

beforeEach(function () {
    s4Login();

    PaperCard::create(['cd' => 1, 'id' => 'CRM000000001', 'title' => 'A', 'url' => 'http://a', 'hyouji_flg' => '1', 'disp_flg' => '1']);
    PaperCard::create(['cd' => 2, 'id' => 'CRM000000002', 'title' => 'B', 'url' => 'http://b', 'hyouji_flg' => '1', 'disp_flg' => '1']);

    DB::table('tb_customer')->insert([
        ['cd' => 101, 'id' => 'K101', 'name' => '山田太郎', 'kojin_sex' => '男性', 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1],
        ['cd' => 102, 'id' => 'K102', 'name' => '佐藤花子', 'kojin_sex' => '女性', 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1],
        ['cd' => 103, 'id' => 'K103', 'name' => '仮登録', 'kojin_sex' => '', 'disp_flg' => '1', 'touroku_kubun' => '1', 'tb_shop_cd' => 1],
    ]);

    DB::table('url_transfer_data')->insert([
        ['access' => '2026-11-27 09:10:00', 'tb_customer_cd' => 101, 'data_id' => 'CRM000000001'],
        ['access' => '2026-11-27 09:40:00', 'tb_customer_cd' => 101, 'data_id' => 'CRM000000001'],
        ['access' => '2026-11-27 13:00:00', 'tb_customer_cd' => 102, 'data_id' => 'CRM000000001'],
        ['access' => '2026-11-27 13:05:00', 'tb_customer_cd' => 103, 'data_id' => 'CRM000000001'],
        ['access' => '2026-11-27 20:00:00', 'tb_customer_cd' => null, 'data_id' => 'CRM000000001'],
        ['access' => '2026-11-27 21:00:00', 'tb_customer_cd' => null, 'data_id' => 'CRM000000002'],
    ]);
});

it('muestra el resumen, el gráfico por hora y los clientes con más accesos', function () {
    $this->get(route('admin.terminales.paper-cards.aggregate', ['cd' => 1]))
        ->assertOk()
        ->assertSee('jquery-1.11.2.min.js', false)
        // 合計 5 · 男性 2 · 女性 1 · 性別未登録 1 (仮登録 también cuenta en el resumen, como el legacy) · 非会員 1
        ->assertSeeInOrder(['<td class="right">5</td>', '<td class="right">2</td>', '<td class="right">1</td>', '<td class="right">1</td>', '<td class="right">1</td>'], false)
        // gráfico: el cliente 仮登録 cuenta como 非会員 (solo 本登録 vigentes), como el legacy
        ->assertSee('data: [0,0,0,0,0,0,0,0,0,2,0,0,0,0,0,0,0,0,0,0,0,0,0,0]', false)
        ->assertSee('data: [0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,1,0,0,0]', false)
        ->assertSeeInOrder(['<td>K101</td>', '<td>山田太郎</td>', '<td>2</td>', '<td>K102</td>'], false)
        ->assertDontSee('<td>K103</td>', false)
        ->assertSee(route('admin.terminales.paper-cards.csv', ['cd' => 1]), false);

    $this->get(route('admin.terminales.paper-cards.aggregate'))->assertNotFound();
});

it('exporta el CSV en Shift_JIS de una tarjeta o de todas', function () {
    $response = $this->get(route('admin.terminales.paper-cards.csv', ['cd' => 1]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=shukei.csv');

    $csv = $response->streamedContent();
    expect(mb_check_encoding($csv, 'SJIS-win'))->toBeTrue()
        ->and(mb_convert_encoding($csv, 'UTF-8', 'SJIS-win'))->toBe(
            "アクセス時間,顧客ID,カードID\n"
            ."2026-11-27 09:10:00,K101,CRM000000001\n"
            ."2026-11-27 09:40:00,K101,CRM000000001\n"
            ."2026-11-27 13:00:00,K102,CRM000000001\n"
            ."2026-11-27 13:05:00,,CRM000000001\n"
            ."2026-11-27 20:00:00,,CRM000000001\n"
        );

    $all = mb_convert_encoding($this->get(route('admin.terminales.paper-cards.csv'))->streamedContent(), 'UTF-8', 'SJIS-win');
    expect(substr_count($all, "\n"))->toBe(7)->and($all)->toContain('CRM000000002');
});
