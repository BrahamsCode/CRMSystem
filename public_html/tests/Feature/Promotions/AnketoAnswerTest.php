<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/anketo_helpers.php';

/* F6: アンケートテスト, 回答解析, 回答一覧 (texto), CSV SJIS-win y, en お客様アンケート, 回答一覧 / 回答詳細 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-27 10:00:00', 'Asia/Tokyo'));
    s4Login();
});

it('アンケートテスト: pinta la encuesta, exige los obligatorios y guarda un grupo nuevo', function (string $family) {
    anketoSeed($family);
    $f = \App\Services\Promotions\AnketoFamily::from($family);
    DB::table('hatuban')->insert(['koumoku' => $f->subTable(), 'kazu' => 500]);

    $this->get(route("admin.promocion.$family.test"))
        ->assertOk()
        ->assertSee('テスト入力')
        ->assertSee('満足度<span class="attention">※</span>', false)
        ->assertSee('name="form21_0"', false)
        ->assertSee('name="kaiin_cd" value="10', false);

    $this->post(route("admin.promocion.$family.test.store"), ['act' => 'up', 'anketo_cate_cd' => 10, 'kaiin_cd' => 101, 'add_record_verify' => '1'])
        ->assertOk()
        ->assertSee('<td  class="Ng" >', false)
        ->assertSee('※必須項目です。');

    $data = ['act' => 'up', 'anketo_cate_cd' => 10, 'kaiin_cd' => 101, 'add_record_verify' => '2', 'form20' => '悪い', 'form21_1' => '安い', 'form22' => 'テスト'];
    $this->post(route("admin.promocion.$family.test.store"), $data)
        ->assertOk()
        ->assertSee('送信中です。')
        ->assertSee('content="1; URL='.route("admin.promocion.$family.test", ['ok' => 'ok']), false);
    // Recarga con el mismo token: no se duplica
    $this->post(route("admin.promocion.$family.test.store"), $data)->assertOk();

    $rows = DB::table($f->subTable())->where('category_inner_group', '3')->orderBy('tb_c3_anketo_form_cd')->get();
    expect($rows)->toHaveCount(3)
        ->and($rows->pluck('text_data')->all())->toBe(['悪い', '安い<>', 'テスト'])
        ->and($rows[0]->form_title)->toBe('満足度調査<>満足度')
        ->and($rows[0]->tb_customer_cd)->toBe(101)
        ->and($rows[0]->cd)->toBe(500)
        ->and(DB::table($f->subTable())->count())->toBe(9);

    $this->get(route("admin.promocion.$family.test", ['ok' => 'ok']))->assertSee('alert("送信しました。")', false);
})->with(['anketo', 'anketo2']);

it('回答解析: recuento por opción, gráficos y CSV (solo master)', function (string $family) {
    anketoSeed($family);

    $response = $this->get(route("admin.promocion.$family.analyze", ['pcd' => 10, 'which' => 'sended', 'page' => 1, 'kensu' => 20]))
        ->assertOk()
        ->assertSee('■『満足度調査』 回答解析')
        ->assertSeeInOrder(['[良い]', '1</span>人', '(50.0%)', '[普通]', '[悪い]', '0</span>人', '(0.0%)'], false)
        ->assertSeeInOrder(['A： ', '[近い]', '1</span>人', 'C： ', '[美味しい]', '2</span>人'], false)
        ->assertSee('/system/inc/graph_pie.php?w=500&amp;h=150&amp;v='.rawurlencode('良い,1|普通,1|悪い,0'), false)
        ->assertSee('graph_bar.php', false)
        ->assertSee(route("admin.promocion.$family.answer", ['pcd' => 10, 'frmcd' => 22]))
        ->assertSee('CSV出力する');

    if ($family === 'anketo2') {
        $response->assertSee('2</span>件の回答がありました。', false)->assertSee('value="内訳"', false);
    } else {
        DB::table('mailmag_master')->insert(['cd' => 900, 'type_flg' => 'anketo', 'type_data_cd' => 10, '配信総数' => 8]);
        $this->get(route('admin.promocion.anketo.analyze', ['pcd' => 10, 'which' => 'sended']))
            ->assertSee('8</span>人に配信して', false)
            ->assertSee('2</span>人が回答しました。', false);
    }

    $this->get(route("admin.promocion.$family.analyze", ['pcd' => 'x']))->assertRedirect(route("admin.promocion.$family.index"));
})->with(['anketo', 'anketo2']);

it('回答一覧 (popup): respuestas de texto escapadas y paginadas', function (string $family) {
    anketoSeed($family);

    $this->get(route("admin.promocion.$family.answer", ['pcd' => 10, 'frmcd' => 22]))
        ->assertOk()
        ->assertSee('■回答一覧')
        ->assertSee('1件～1件目/1件中')
        ->assertSee('また&lt;来ます&gt;', false);
})->with(['anketo', 'anketo2']);

it('CSV de respuestas en SJIS-win', function (string $family) {
    anketoSeed($family);

    $response = $this->post(route("admin.promocion.$family.csv"), ['pcd' => 10])->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/x-csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('anketo_answer.csv');

    $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
    expect($csv)->toStartWith("\"アンケート番号: 10\"\r\n\"アンケート名: 満足度調査\"\r\n")
        ->toContain("\"開始年月日: 2026/11/01\"\r\n\"終了年月日: 2026/12/31\"\r\n\r\n\r\n\"満足度\"\r\n\"\t良い: 1人    (50.0%)\"")
        ->toContain("\"来店理由\"\r\n\"\t近い: 1人\"\r\n\"\t安い: 0人\"\r\n\"\t美味しい: 2人\"\r\n")
        ->not->toContain('ご意見');

    expect($this->post(route("admin.promocion.$family.csv"), ['pcd' => 999])->getContent())->toBe('');
})->with(['anketo', 'anketo2']);

it('お客様アンケート回答一覧: respuestas por cliente, búsqueda por fecha, 内訳 y 回答詳細', function () {
    anketoSeed('anketo2');
    DB::table('tb_raiten_rireki')->insert([
        ['cd' => 1, 'user_cd' => 101, 'shop_cd' => 1, 'disp_flg' => '1'],
        ['cd' => 2, 'user_cd' => 101, 'shop_cd' => 1, 'disp_flg' => '9'],
    ]);

    $this->get(route('admin.promocion.anketo2.answers', ['pcd' => 10, 'kensu2' => 20, 'page2' => 1, 'which' => 'sended']))
        ->assertOk()
        ->assertSee('<title>お客様アンケート回答一覧 - ', false)
        ->assertSee('■お客様アンケート回答検索')
        ->assertSee('<th>満足度</th>', false)
        ->assertDontSee('<th>来店理由</th>', false)
        ->assertSee('1件～2件目/2件中')
        // la última respuesta primero
        ->assertSeeInOrder(['K102', '佐藤花子', '女性', '2026/11/25 18:30', '普通', 'K101', '山田太郎', '36', '1', '2026/11/20 10:00', '良い'], false)
        ->assertSee('name="reply_mail[306]" value="hanako@example.com"', false)
        ->assertSee('/system/category1/inc/customer_detail.php?kaiin_cd=101&amp;wintype=no_headers', false)
        ->assertSee(route('admin.promocion.anketo2.answers.show', ['pcd' => 10, 'ccd' => 101, 'group' => '1']))
        ->assertSee(route('admin.promocion.anketo2.reply.create'), false)
        ->assertSee('一覧へ戻る');

    // Periodo: desde el 21 de noviembre
    $this->post(route('admin.promocion.anketo2.answers'), ['pcd' => 10, 'from_year' => '2026', 'from_month' => '11', 'from_day' => '21', 'act' => '検索する'])
        ->assertOk()
        ->assertSee('1件～1件目/1件中')
        ->assertSee('<option value="11" selected>11</option>', false)
        ->assertSee('佐藤花子')
        ->assertDontSee('山田太郎');

    // 内訳 de 回答解析: los que eligieron «良い», con todas sus respuestas
    $this->get(route('admin.promocion.anketo2.answers', ['pcd' => 10, 'fcd' => 20, 'answer' => '良い']))
        ->assertOk()
        ->assertSee('山田太郎')
        ->assertDontSee('佐藤花子')
        ->assertSee('解析へ戻る');

    $this->get(route('admin.promocion.anketo2.answers.show', ['pcd' => 10, 'ccd' => 101, 'group' => '1']))
        ->assertOk()
        ->assertSee('■回答内容詳細')
        ->assertSeeInOrder(['満足度', '良い', '来店理由', '近い,美味しい,', 'ご意見', 'また&lt;来ます&gt;'], false);

    $this->get(route('admin.promocion.anketo2.answers', ['pcd' => 'x']))->assertRedirect(route('admin.promocion.anketo2.index'));
});
