<?php

use App\Models\Mypage\Recommendation;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/helpers.php';

/* オススメ管理 (user_hp/recommend.php, recommend_register.php, recommend_delete_register.php) */

beforeEach(function () {
    mypageLogin();
    Http::fake();
    // Nombre del menú en tb_mobile_navi cd 9, en 半角カナ: la pantalla lo pasa a 全角 (mb_convert_kana 'KV')
    DB::table('tb_mobile_navi')->insert(['cd' => 9, 'name' => 'ｵｽｽﾒ', 'link' => 'recommend.php', 'disp_flg' => '1']);
});

function recommendForm(array $overrides = []): array
{
    return $overrides + [
        'act' => 'new', 'tb_shop_cd' => '', 'hyouji_flg' => '', 'news_year' => '2026', 'news_month' => '01',
        'news_day' => '05', 'title' => 'セール', 'link_type' => 'http://', 'link' => 'example.com',
        'news_naiyou' => "本日\n限定", 'pic_name' => '', 'pic_link' => '', 'push' => '0',
    ];
}

it('carga la pantalla con el nombre del menú y la プッシュ通知 de la app', function () {
    $this->get(route('admin.mypage.recommendations.index'))
        ->assertOk()
        ->assertSee('<title>オススメ管理 - shop</title>', false)
        ->assertSee('■オススメ情報登録')
        ->assertSee('アプリへのプッシュ通知')
        ->assertSee('データがありません。');
});

it('da de alta una オススメ, la lista y envía el push a la app', function () {
    $this->post(route('admin.mypage.recommendations.store'), recommendForm(['push' => '1']))
        ->assertOk()
        ->assertSee('登録中です。')
        ->assertSee('<meta http-equiv="Refresh" content="2; URL=' . route('admin.mypage.recommendations.index') . '">', false);

    $recommendation = Recommendation::query()->sole();
    expect($recommendation->disp_date)->toBe('2026/01/05')
        ->and($recommendation->tb_shop_cd)->toBeNull()
        ->and($recommendation->hyouji_flg)->toBe('')
        ->and($recommendation->update)->toMatch('/^\d+$/');

    Http::assertSent(fn (HttpRequest $request) => str_starts_with($request->url(), 'http://b-develop.g-api.net/api/mpa/pushMessage?')
        && $request['domain'] === 'localhost' && $request['type'] === 'recommend' && (int) $request['cd'] === $recommendation->cd);

    $this->get(route('admin.mypage.recommendations.index'))
        ->assertSee('■オススメ情報一覧')
        ->assertSee('<a href="http://example.com" target="_blank">セール</a>', false)
        ->assertSee("本日<br />\n限定", false)
        ->assertSee('全店舗');
});

it('no envía push con push=0 ni con la app desactivada', function () {
    $this->post(route('admin.mypage.recommendations.store'), recommendForm());
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 0]);
    $this->post(route('admin.mypage.recommendations.store'), recommendForm(['push' => '1']));

    Http::assertNothingSent();
    expect(Recommendation::query()->count())->toBe(2);
});

it('sube la imagen por AjaxUpload y la guarda como <cd>_big y <cd> de 150px', function () {
    $this->post('/system/category6/user_hp/inc/upload.php', [
        'userfile' => UploadedFile::fake()->image('foto.jpg', 960, 720), 'ext' => 'jpg', 'time' => '1700000000123',
    ])->assertOk()->assertSeeText('success');

    $this->get('/system/category6/user_hp/inc/disp_pic.php?ext=jpg&t=1700000000123')
        ->assertSee('hp/uploads/c6/user_hp/recommend/temp/1700000000123.jpg', false);

    $this->post(route('admin.mypage.recommendations.store'), recommendForm(['pic_name' => '1700000000123']));

    $cd = Recommendation::query()->sole()->cd;
    $disk = Storage::disk('public');
    $small = getimagesizefromstring($disk->get("hp/uploads/c6/user_hp/recommend/{$cd}/{$cd}.jpg"));
    $big = getimagesizefromstring($disk->get("hp/uploads/c6/user_hp/recommend/{$cd}/{$cd}_big.jpg"));

    expect([$small[0], $big[0]])->toBe([150, 480])
        ->and($disk->exists('hp/uploads/c6/user_hp/recommend/temp/1700000000123.jpg'))->toBeFalse()
        ->and(Recommendation::query()->sole()->only(['img_flg', 'img_type']))->toBe(['img_flg' => '1', 'img_type' => 'jpg']);

    $this->get(route('admin.mypage.recommendations.index', ['cd' => $cd]))
        ->assertSee("recommend/{$cd}/{$cd}_big.jpg?t=", false)
        ->assertSee('画像添付解除');
});

it('modifica, oculta con 表示情報を更新 y borra', function () {
    $this->post(route('admin.mypage.recommendations.store'), recommendForm());
    $cd = Recommendation::query()->sole()->cd;

    $this->get(route('admin.mypage.recommendations.index', ['cd' => $cd]))
        ->assertSee('この内容で修正する')
        ->assertSee('value="セール"', false);

    $this->post(route('admin.mypage.recommendations.store'), recommendForm(['act' => 'up', 'cd' => $cd, 'title' => '変更', 'tb_shop_cd' => '1']));
    expect(Recommendation::query()->sole()->only(['title', 'tb_shop_cd']))->toBe(['title' => '変更', 'tb_shop_cd' => 1]);

    $this->post(route('admin.mypage.recommendations.index'), ['update_hyouji_flg' => 'update', 'update_cd' => [$cd]])
        ->assertOk();
    expect(Recommendation::query()->sole()->hyouji_flg)->toBe('9');

    $this->post(route('admin.mypage.recommendations.destroy'), ['delete_flg' => 'del', 'cd' => $cd])
        ->assertSee('削除中です。');
    expect(Recommendation::query()->withDeleted()->sole()->disp_flg)->toBe('9');
});

it('guarda el texto como get_form2 (LegacyText::input) y lo imprime sin escapar dos veces', function () {
    $this->post(route('admin.mypage.recommendations.store'), recommendForm(['title' => '  A&B "特価"　', 'link' => '']));

    expect(Recommendation::query()->sole()->title)->toBe('A&amp;B &quot;特価&quot;');
    $this->get(route('admin.mypage.recommendations.index'))->assertSee('A&amp;B &quot;特価&quot;<br />', false);
});
