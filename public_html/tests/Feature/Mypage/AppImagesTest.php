<?php

use App\Services\Mypage\MypageConfig;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/helpers.php';

/* マイコレ管理 (mobile_mycolle_edit*.php) y su endpoint ajax_apliFileUpload.php */

const MYCOLLE_URL = '/system/category6/user_hp/ajax_apliFileUpload.php';

beforeEach(function () {
    mypageLogin();
    DB::table('tb_area')->insert([
        ['cd' => 7001, 'pid' => 0, 'name' => '関西', 'row_lis' => 1, 'flg' => '1', 'disp_flg' => '1'],
        ['cd' => 7002, 'pid' => 7001, 'name' => '大阪', 'row_lis' => 2, 'flg' => '1', 'disp_flg' => '1'],
    ]);
    DB::table('tb_shop')->insert([
        ['cd' => 9001, 'kaishamei' => '梅田店', 'disp_flg' => '1', 'area_cd' => 7002, 'touroku_date' => '2'],
        ['cd' => 9002, 'kaishamei' => '無area店', 'disp_flg' => '1', 'area_cd' => null, 'touroku_date' => '1'],
        ['cd' => 9003, 'kaishamei' => '閉鎖店', 'disp_flg' => '9', 'area_cd' => 7002, 'touroku_date' => '3'],
    ]);
});

function mycolleTempImage(int $width = 1000, int $height = 1000): string
{
    $response = test()->post(MYCOLLE_URL, [
        'type' => 'picUpload',
        'file' => UploadedFile::fake()->image('photo.jpg', $width, $height),
    ])->assertOk()->json();

    return $response['imageName'];
}

it('carga las 5 pantallas de マイコレ', function (string $section, string $text) {
    $this->get(route('admin.mypage.app-images.edit', ['section' => $section]))
        ->assertOk()
        ->assertSee('<title>ショップページ設定 - shop</title>', false)
        ->assertSee($text);
})->with([
    'all' => ['all', 'マイコレHOMEに追加するコメント'],
    'cover' => ['cover', 'マイページアプリのカバーフロー画像設定'],
    'banner' => ['banner', 'マイページアプリのバナー画像設定'],
    'main' => ['main', 'マイページアプリのHOMEに表示されるメインイメージ'],
    'thumbnail' => ['thumbnail', 'マイページアプリのHOMEに表示されるサムネイル画像'],
]);

it('no muestra nada si la app no está activa (application_use_flg)', function () {
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 0]);

    $this->get(route('admin.mypage.app-images.edit', ['section' => 'all']))
        ->assertOk()
        ->assertDontSee('マイコレHOMEに追加するコメント');
});

it('pinta los huecos de カバーフロー con imagen, enlace, tiendas por área y banderas', function () {
    Storage::disk('public')->put('hp/uploads/c6/aplicover/0.jpg', 'x');
    app(MypageConfig::class)->saveLinks('aplicover', ['https://a.example']);
    app(MypageConfig::class)->saveMypageConfig(['cover' => [0 => [9001 => 1]]]);

    $html = $this->get(route('admin.mypage.app-images.edit', ['section' => 'cover']))->assertOk()->getContent();

    expect($html)->toContain('hp/uploads/c6/aplicover/0.jpg?t=')
        ->toContain('name="pic[0]" value="def"')
        ->toContain('value="https://a.example"')
        ->toContain('name="image20"')
        ->not->toContain('name="image21"')
        ->toContain('関西')
        ->toContain('└ ')
        ->toMatch('/id="area_check_btn_0_9001" name="mypage_config\[cover\]\[0\]\[9001\]" value="9001"\s+checked/')
        ->toContain('<div class="areaNonShopLabel"><label for="area_check_btn_0_9002">')
        ->not->toContain('閉鎖店');
});

it('muestra メインイメージ por shop_cd (archivo <shop_cd>.jpg) y su bandera 全店舗画像', function () {
    Storage::disk('public')->put('hp/uploads/c6/apli_home_image/9001.jpg', 'x');
    app(MypageConfig::class)->saveMypageConfig(['home_image_flg' => [9002 => '1']]);

    $html = $this->get(route('admin.mypage.app-images.edit', ['section' => 'main']))->assertOk()->getContent();

    expect($html)->toContain('【梅田店】')
        ->toContain('hp/uploads/c6/apli_home_image/9001.jpg?t=')
        ->toContain('name="image9001"')
        ->toContain('class="deleteMainPic" target="9001"')
        ->toMatch('/id="mypage_config_home_image_flg_9002" value="1"\s+checked="checked"/');
});

it('guarda la コメント HOME y comment_flg como el legacy', function () {
    $this->post(route('admin.mypage.app-images.home-comment'), [
        'home_comment' => [0 => 'ようこそ "全店"', 9001 => '梅田'],
        'mypage_config' => ['comment_flg' => [-1 => 1, 9002 => 1]],
        'type' => 'home_comment',
    ])->assertRedirect(route('admin.mypage.app-images.edit', ['section' => 'all']));

    $config = app(MypageConfig::class);
    $raw = DB::table('contents_master')->where('field', 'apli_home_comment')->value('text_data');

    // Como mobile_mycolle_edit.php: sin get_form2; las comillas dobles se guardan como simples y vuelven al leer
    expect($raw)->toContain("ようこそ '全店'")
        ->and($config->homeComments())->toBe([0 => 'ようこそ "全店"', 9001 => '梅田'])
        ->and($config->mypageConfig()['comment_flg'])->toEqual([-1 => 1, 9002 => 1]);

    $this->get(route('admin.mypage.app-images.edit', ['section' => 'all']))
        ->assertSee('ようこそ &quot;全店&quot;', false)
        ->assertSee('id="mypage_config_comment_flg_9002" value="1"  checked="checked"', false);
});

it('guarda 顧客グループ表示 en int_data', function () {
    $this->post(route('admin.mypage.app-images.customer-group'), [
        'mode' => 'apli_customer_group_disp_upload',
        'apli_customer_group_disp' => '1',
    ])->assertRedirect();

    expect(DB::table('contents_master')->where('field', 'apli_customer_group_disp')->value('int_data'))->toBe(1);

    $this->post(route('admin.mypage.app-images.customer-group'), ['apli_customer_group_disp' => '0']);
    expect(DB::table('contents_master')->where('field', 'apli_customer_group_disp')->count())->toBe(1)
        ->and(app(MypageConfig::class)->customerGroupDisplay())->toBeFalse();
});

it('sube una imagen temporal recortada a 800×600 y rechaza extensiones no permitidas', function () {
    $name = mycolleTempImage(1200, 400);

    expect($name)->toMatch('/^\d+_[a-f0-9]+\.jpg$/');
    $size = getimagesizefromstring(Storage::disk('public')->get('hp/uploads/c6/apli_temp/' . $name));
    expect([$size[0], $size[1]])->toBe([800, 600]);

    $this->post(MYCOLLE_URL, ['type' => 'picUpload', 'file' => UploadedFile::fake()->create('a.txt', 1)])
        ->assertExactJson(['status' => 'ng', 'errMsg' => '許可されているファイル拡張子ではありません。']);

    $this->post(MYCOLLE_URL, ['type' => 'bannerUpload', 'file' => UploadedFile::fake()->image('b.png', 900, 900)])
        ->assertJsonPath('status', 'ok');
});

it('sendData compacta la カバーフロー moviendo imagen, enlace y banderas juntos', function () {
    $disk = Storage::disk('public');
    $disk->put('hp/uploads/c6/aplicover/0.jpg', 'cero');
    $disk->put('hp/uploads/c6/aplicover/2.jpg', 'dos');
    $name = mycolleTempImage();

    $this->post(MYCOLLE_URL, [
        'type' => 'sendData',
        'pic' => [0 => 'def', 2 => 'def', 5 => $name],
        'target_url' => [0 => 'http://0', 2 => 'http://2', 5 => 'tel:5'],
        'mypage_config' => ['cover' => [-1 => 1, 2 => [9001 => 9001], 5 => [0 => 1]]],
    ])->assertExactJson(['status' => 'ok', 'result' => '1枚目は現状保存。2枚目は現状保存。3枚目アップロード成功。']);

    $config = app(MypageConfig::class);
    expect($disk->get('hp/uploads/c6/aplicover/0.jpg'))->toBe('cero')
        ->and($disk->get('hp/uploads/c6/aplicover/1.jpg'))->toBe('dos')
        ->and($disk->exists('hp/uploads/c6/aplicover/2.jpg'))->toBeTrue()
        ->and($disk->exists('hp/uploads/c6/aplicover/5.jpg'))->toBeFalse()
        ->and(array_slice($config->links('aplicover'), 0, 4))->toBe(['http://0', 'http://2', 'tel:5', ''])
        ->and($config->mypageConfig()['cover'])->toEqual([-1 => 1, 1 => [9001 => 9001], 2 => [0 => 1]]);
});

it('del borra un hueco de カバーフロー y sube los siguientes', function () {
    $disk = Storage::disk('public');
    foreach (['a', 'b', 'c'] as $i => $content) {
        $disk->put("hp/uploads/c6/aplicover/{$i}.jpg", $content);
    }
    $config = app(MypageConfig::class);
    $config->saveLinks('aplicover', ['l0', 'l1', 'l2']);
    $config->saveMypageConfig(['cover' => [-1 => 1, 0 => [0 => 1], 2 => [9001 => 1]]]);

    $this->post(MYCOLLE_URL, ['type' => 'del', 'pic' => '1'])
        ->assertExactJson(['status' => 'ok', 'res' => '削除しました。']);

    expect($disk->get('hp/uploads/c6/aplicover/0.jpg'))->toBe('a')
        ->and($disk->get('hp/uploads/c6/aplicover/1.jpg'))->toBe('c')
        ->and($disk->exists('hp/uploads/c6/aplicover/2.jpg'))->toBeFalse()
        ->and($config->links('aplicover'))->toBe(['l0', 'l2'])
        ->and($config->mypageConfig()['cover'])->toEqual([-1 => 1, 0 => [0 => 1], 1 => [9001 => 1]]);
});

it('sendBannerData guarda en la posición del hueco con cell_status/cell_img y bannerdel desplaza', function () {
    $disk = Storage::disk('public');
    $name = mycolleTempImage();

    $this->post(MYCOLLE_URL, [
        'type' => 'sendBannerData',
        'pic' => [1 => 'def', 4 => $name],
        'target_url' => [1 => 'http://1', 4 => 'http://4'],
        'mypage_config' => [
            'banner' => [-1 => 1, 4 => [0 => 1]],
            'cell_status' => [0 => 1, 1 => 2, 4 => 1],
            'cell_img' => [1 => 'LINE_1.jpg'],
        ],
    ])->assertExactJson(['status' => 'ok', 'result' => '1枚目は現状保存。2枚目アップロード成功。']);

    $config = app(MypageConfig::class);
    expect($disk->exists('hp/uploads/c6/aplibanner/4.jpg'))->toBeTrue()
        ->and(array_slice($config->links('aplibanner'), 0, 5))->toBe(['', 'http://1', '', '', 'http://4'])
        ->and($config->mypageConfig()['cell_img'])->toBe([1 => 'LINE_1.jpg']);

    $this->post(MYCOLLE_URL, ['type' => 'bannerdel', 'pic' => '1'])->assertJsonPath('status', 'ok');

    $banner = $config->mypageConfig();
    expect($disk->exists('hp/uploads/c6/aplibanner/3.jpg'))->toBeTrue()
        ->and($disk->exists('hp/uploads/c6/aplibanner/4.jpg'))->toBeFalse()
        ->and($banner['banner'])->toEqual([-1 => 1, 3 => [0 => 1]])
        ->and($banner['cell_status'])->toEqual([0 => 1, 3 => 1])
        ->and($config->links('aplibanner')[3])->toBe('http://4');
});

it('sendMainData guarda <shop_cd>.jpg y la miniatura _s, y delMain borra ambas', function () {
    $disk = Storage::disk('public');
    $name = mycolleTempImage(1600, 1200);

    $this->post(MYCOLLE_URL, [
        'type' => 'sendMainData',
        'dir' => 'apli_home_image',
        'pic' => [9001 => $name, 0 => 'def'],
        'mypage_config' => ['home_image_flg' => [-1 => 1, 9002 => 1]],
    ])->assertExactJson(['status' => 'ok', 'result' => '1枚目は現状保存。2枚目アップロード成功。']);

    $small = getimagesizefromstring($disk->get('hp/uploads/c6/apli_home_image/9001_s.jpg'));
    expect($disk->exists('hp/uploads/c6/apli_home_image/9001.jpg'))->toBeTrue()
        ->and([$small[0], $small[1]])->toBe([320, 240])
        ->and(app(MypageConfig::class)->mypageConfig()['home_image_flg'])->toEqual([-1 => 1, 9002 => 1]);

    $this->post(MYCOLLE_URL, ['type' => 'delMain', 'dir' => 'apli_home_image', 'num' => '9001'])
        ->assertExactJson(['status' => 'ok', 'res' => '削除しました。']);
    expect($disk->files('hp/uploads/c6/apli_home_image'))->toBe([]);
});

it('ignora nombres temporales que salen de la carpeta', function () {
    Storage::disk('public')->put('secret.jpg', 'x');

    $this->post(MYCOLLE_URL, [
        'type' => 'sendThumbnailData',
        'pic' => [0 => '../../../../secret.jpg'],
        'mypage_config' => ['home_thum_flg' => [-1 => 1]],
    ])->assertJsonPath('result', '1枚目アップロード失敗。');

    expect(Storage::disk('public')->files('hp/uploads/c6/apli_home_thumbnail'))->toBe([]);
});
