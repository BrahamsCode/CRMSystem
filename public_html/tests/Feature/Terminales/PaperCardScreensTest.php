<?php

use App\Models\Terminales\PaperCard;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* ペーパーサイネージ (card_signage/list.php, new.php, edit.php, control.php): pantallas y acciones AJAX */

beforeEach(function () {
    s4Login();
});

function s4PaperCard(int $cd, array $attributes = []): PaperCard
{
    return PaperCard::create($attributes + [
        'cd' => $cd, 'id' => 'CRM'.sprintf('%09d', $cd), 'title' => "カード{$cd}", 'url' => 'http://example.jp/'.$cd,
        'hyouji_flg' => '1', 'disp_flg' => '1',
    ]);
}

function s4PaperControl(array $data)
{
    return test()->post(route('legacy.terminales.paper-card-control'), $data);
}

it('lista las tarjetas vigentes con su QR, 集計 y paginación', function () {
    foreach (range(1, 12) as $cd) {
        s4PaperCard($cd);
    }
    s4PaperCard(13, ['hyouji_flg' => '9', 'title' => '止めたカード']);
    s4PaperCard(14, ['disp_flg' => '9', 'title' => '削除済み']);

    $this->get(route('admin.terminales.paper-cards.index'))
        ->assertOk()
        ->assertSee('<title>ペーパーサイネージ - ', false)
        ->assertSee('1件～10件目/13件中', false)
        ->assertSee('止めたカード')
        ->assertSee('停止')
        ->assertDontSee('削除済み')
        ->assertSee('data="http://localhost/ptag/CRM000000013"', false)
        ->assertSee(route('admin.terminales.paper-cards.aggregate', ['cd' => 13]), false)
        ->assertSee('<li class="btn"><a href="'.route('admin.terminales.paper-cards.index').'?page=2&amp;kensu=10">次へ</a></li>', false);

    $this->get(route('admin.terminales.paper-cards.create'))
        ->assertOk()
        ->assertSee('カード登録フォーム')
        ->assertSee(route('legacy.terminales.paper-card-control'), false);
});

it('da de alta con código automático CRM + cd y guarda el texto como el legacy', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_url_list', 'kazu' => 57]);

    s4PaperControl(['type' => 'new', 'id' => '', 'title' => ' ｶｰﾄﾞ A ', 'url' => 'http://example.jp/?a=1&b="x"'])
        ->assertOk()
        ->assertContent('成功');

    $card = PaperCard::query()->find(57);
    expect($card->id)->toBe('CRM000000057')
        ->and($card->title)->toBe('カード A')
        ->and($card->url)->toBe('http://example.jp/?a=1&amp;b=&quot;x&quot;')
        ->and($card->hyouji_flg)->toBe('1')
        ->and($card->disp_flg)->toBe('1');

    s4PaperControl(['type' => 'new', 'title' => 'x', 'url' => ''])->assertContent('入力項目が空白です。');
});

it('rechaza un código propio que ya usa otra tarjeta vigente', function () {
    s4PaperCard(1, ['id' => 'shop-a']);
    s4PaperCard(2, ['id' => 'shop-b']);
    s4PaperCard(3, ['id' => 'viejo', 'disp_flg' => '9']);

    s4PaperControl(['type' => 'new', 'id' => 'shop-a', 'title' => 't', 'url' => 'http://x'])->assertContent('ERROR:既に使用されています。');

    s4PaperControl(['type' => 'update', 'cd' => 2, 'id' => 'shop-a', 'title' => 't', 'url' => 'http://x', 'returnfunc' => 1])
        ->assertContent('ERROR:既に使用されています。');

    s4PaperControl(['type' => 'check', 'id' => 'shop-a'])
        ->assertContent('既に使用されています。-- ID:「shop-a」のURLは「http://example.jp/1」です。');
    s4PaperControl(['type' => 'check', 'id' => 'viejo'])->assertContent('取得可能です。');
    s4PaperControl(['type' => 'check', 'id' => 'ñ'])->assertContent('アルファベット・数字・ハイフン・アンダーバーのみが使用できます。');
});

it('edita sin reactivar una tarjeta detenida, y la detiene, reactiva y borra', function () {
    s4PaperCard(5, ['hyouji_flg' => '9']);

    $this->post(route('admin.terminales.paper-cards.edit'), ['cd' => 5])
        ->assertOk()
        ->assertSee('<div>NO.5</div>', false)
        ->assertSee('<option value="9" selected="selected">停止</option>', false)
        ->assertSee('value="CRM000000005"', false);

    s4PaperControl(['type' => 'update', 'cd' => 5, 'id' => 'nuevo_5', 'title' => '新名称', 'url' => 'http://example.jp/new', 'returnfunc' => 1])
        ->assertContent('成功<script type="text/javascript">location.reload();</script>');
    expect(PaperCard::query()->find(5)->only(['id', 'title', 'url', 'hyouji_flg']))
        ->toBe(['id' => 'nuevo_5', 'title' => '新名称', 'url' => 'http://example.jp/new', 'hyouji_flg' => '9']);

    s4PaperControl(['type' => 'hyouji_switch', 'cd' => 5, 'hyouji_flg' => '1', 'returnfunc' => 1])
        ->assertContent('変更しました。<script type="text/javascript">location.reload();</script>');
    expect(PaperCard::query()->find(5)->hyouji_flg)->toBe('1');

    s4PaperControl(['type' => 'hyouji_switch', 'cd' => 5, 'hyouji_flg' => '9'])->assertContent('変更しました。');
    expect(PaperCard::query()->find(5)->hyouji_flg)->toBe('9');

    s4PaperControl(['type' => 'delete', 'cd' => 5, 'returnfunc' => 1])
        ->assertContent('削除しました。<script type="text/javascript">location.reload();</script>');
    expect(PaperCard::withDeleted()->find(5)->disp_flg)->toBe('9');
});

it('guarda el título y el id sin interpretar SQL (el legacy los concatenaba)', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_url_list', 'kazu' => 9]);

    s4PaperControl(['type' => 'new', 'title' => "x', disp_flg = '9", 'url' => 'http://x'])->assertContent('成功');

    expect(PaperCard::query()->find(9)->title)->toBe("x', disp_flg = '9")
        ->and(PaperCard::query()->find(9)->disp_flg)->toBe('1');
});

it('sirve el QR en JPEG y lo descarga como <id>.jpg', function () {
    $this->get(route('admin.terminales.paper-cards.qr', ['d' => 'http://localhost/ptag/CRM000000001', 't' => 'J']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    $response = $this->get(route('admin.terminales.paper-cards.index', ['qrCodeDownloadData' => 'http://localhost/ptag/CRM000000001', 'fileName' => 'CRM000000001']))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="CRM000000001.jpg"');

    expect(getimagesizefromstring($response->getContent())['mime'])->toBe('image/jpeg');
});
