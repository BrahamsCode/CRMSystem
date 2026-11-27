<?php

use App\Services\Promotions\DmLabelPdf;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';

/* M8 DM発行: búsqueda de clientes (customer_list) y 宛名ラベル A4×12 en PDF (aterna_a4_12) */

beforeEach(function () {
    s4Login();
    DB::table('tb_customer')->insert([
        ['cd' => 101, 'id' => 'K101', 'name' => '山田太郎', 'zip' => '530-0001', 'pref' => '大阪府', 'add' => '大阪市北区梅田', 'add_number' => '1-1',
            'add_build' => '梅田ビル101', 'tel' => '06-0000-0001', 'email1' => 'yamada@example.jp', 'kojin_birth' => '1990/01/02', 'mailmaga_flg' => '1',
            'error_send_cnt' => 0, 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1, 'touroku_date' => '1700000000'],
        ['cd' => 102, 'id' => 'K102', 'name' => '佐藤花子', 'zip' => '100-0001', 'pref' => '東京都', 'add' => '千代田区', 'add_number' => '2-2',
            'add_build' => '', 'tel' => '', 'email1' => 'sato@example.jp', 'kojin_birth' => '', 'mailmaga_flg' => '0',
            'error_send_cnt' => 2, 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1, 'touroku_date' => '1700000001'],
        ['cd' => 103, 'id' => 'K103', 'name' => '削除済', 'zip' => '', 'pref' => '', 'add' => '', 'add_number' => '',
            'add_build' => '', 'tel' => '', 'email1' => '', 'kojin_birth' => '', 'mailmaga_flg' => '0',
            'error_send_cnt' => 0, 'disp_flg' => '9', 'touroku_kubun' => '2', 'tb_shop_cd' => 1, 'touroku_date' => '1700000002'],
    ]);
});

/** Texto tal como queda en el PDF (UTF-16BE en hexadecimal) */
function pdfHex(string $text): string
{
    return '<'.strtoupper(bin2hex(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'))).'>';
}

function dmUniqid($response): string
{
    preg_match('/name="search_uniqid" id="search_uniqid" value="([^"]+)"/', $response->getContent(), $m);

    return $m[1] ?? '';
}

it('muestra el buscador y, al buscar, los clientes vigentes con el botón de etiquetas', function () {
    $this->get(route('admin.promocion.dm.index'))
        ->assertOk()
        ->assertSee('<title>DM発行 - ', false)
        ->assertSee('■DM発行顧客検索条件')
        ->assertSee('この条件で検索する')
        ->assertDontSee('■顧客一覧');

    $this->post(route('admin.promocion.dm.index'), ['act' => 'up', 'se_flg' => '1', 'kensu' => 10, 'enter' => 'この条件で検索する'])
        ->assertOk()
        ->assertSee('<span class="text3L attention">2</span>件見つかりました。', false)
        ->assertSee('この内容で宛名出力する')
        ->assertSee('action="'.route('admin.promocion.dm.labels').'" target="_blank"', false)
        ->assertSee('1件～2件目/2件中')
        // Orden cd DESC, como el legacy
        ->assertSeeInOrder(['K102', '佐藤花子', '100-0001', '---', 'sato@...', '×', '送信エラー：2回', 'K101', '山田太郎', '1990/01/02', '530-0001', '大阪市北区梅田', '06-0000-0001', 'yamada@...', '○'], false)
        ->assertDontSee('削除済');
});

it('filtra por nombre y pagina con la búsqueda guardada', function () {
    $response = $this->post(route('admin.promocion.dm.index'), ['act' => 'up', 'se_flg' => '1', 'name' => '山田', 'enter' => 'この条件で検索する'])
        ->assertOk()
        ->assertSee('<span class="text3L attention">1</span>件見つかりました。', false)
        ->assertSee('山田太郎')
        ->assertDontSee('佐藤花子');

    $uniqid = dmUniqid($response);
    expect($uniqid)->not->toBe('');

    // Página con kensu=1 sobre la búsqueda guardada (sin volver a mandar el filtro)
    $all = dmUniqid($this->post(route('admin.promocion.dm.index'), ['act' => 'up', 'se_flg' => '1', 'enter' => '1']));
    $this->get(route('admin.promocion.dm.index', ['act' => 'up', 'se_flg' => 1, 'kensu' => 1, 'page' => 2, 'search_uniqid' => $all]))
        ->assertOk()
        ->assertSee('2件～2件目/2件中')
        ->assertSee('山田太郎')
        ->assertDontSee('<td>K102</td>', false);
});

it('この内容で宛名出力する: PDF A4 con las direcciones de los clientes elegidos', function () {
    $uniqid = dmUniqid($this->post(route('admin.promocion.dm.index'), ['act' => 'up', 'se_flg' => '1', 'enter' => '1']));

    $response = $this->post(route('admin.promocion.dm.labels'), ['search_uniqid' => $uniqid, 'dm_submit' => '1'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    $pdf = $response->getContent();

    expect($pdf)->toStartWith('%PDF-1.4')->toEndWith("%%EOF\n")
        ->toContain('/MediaBox [0 0 595.28 841.89]')
        ->toContain('/Encoding /UniJIS-UTF16-H')
        ->toContain('/Count 1')
        // Orden cd: 山田 (columna izquierda) y 佐藤 (derecha), 4 líneas cada uno
        ->toContain(pdfHex('〒530-0001'))
        ->toContain(pdfHex('大阪府大阪市北区梅田1-1'))
        ->toContain(pdfHex('梅田ビル101'))
        ->toContain(pdfHex('山田太郎'))
        ->toContain(pdfHex('東京都千代田区2-2'))
        ->toContain(pdfHex('佐藤花子'))
        ->not->toContain(pdfHex('削除済'));

    // Posiciones de aterna_a4_12.php: x = 19.3 + 10 (+ 83.8 + 3.8), y = 21.5 + 10 desde arriba
    $k = 72 / 25.4;
    expect($pdf)->toContain(sprintf('%.2F %.2F Td %s', 29.3 * $k, 841.89 - 31.5 * $k, pdfHex('〒530-0001')))
        ->toContain(sprintf('%.2F %.2F Td %s', 116.9 * $k, 841.89 - 40.0 * $k, pdfHex('佐藤花子')));

    $this->post(route('admin.promocion.dm.labels'), ['search_uniqid' => 'nope'])->assertNotFound();
});

it('12 etiquetas por hoja', function () {
    $customers = array_map(fn ($i) => ['zip' => "000-$i", 'name' => "客$i"], range(1, 13));
    $pdf = app(DmLabelPdf::class)->render($customers);

    expect($pdf)->toContain('/Count 2')
        ->and(substr_count($pdf, '/Type /Page '))->toBe(2);
});
