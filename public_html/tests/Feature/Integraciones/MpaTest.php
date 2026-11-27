<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
 * mpa_* con imágenes y escrituras (las fixtures del legacy se grabaron sin imágenes en hp/uploads/c6):
 * reglas de visibilidad por tienda de mypage_config, beacon y subida al álbum.
 */

beforeEach(function () {
    Storage::fake('public');
    DB::table('admin_master')->insert(['cd' => 1, 'application_use_flg' => '1']);
    DB::table('tb_shop')->insert([['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1'], ['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']]);
    DB::table('tb_customer')->insert(['cd' => 10, 'id' => '1000001', 'tb_shop_cd' => 2, 'login_id' => 'u1', 'login_pass' => 'p1',
        'name' => '山田', 'touroku_kubun' => '2', 'disp_flg' => '1']);
});

function mpaConfig(array $config): void
{
    DB::table('contents_master')->insert(['cd' => 1, 'category' => 'tb_c6_user_hp', 'field' => 'mypage_config',
        'text_data' => str_replace('"', "'", serialize($config)), 'disp_flg' => '1', 'hyouji_flg' => '1']);
}

it('mpa_corresponding: imagen de la tienda del socio o la común (home_image_flg) y su comentario', function () {
    $disk = Storage::disk('public');
    foreach (['0.jpg', '0_s.jpg', '2.jpg', '2_s.jpg'] as $file) {
        $disk->put('hp/uploads/c6/apli_home_image/'.$file, 'x');
    }
    $disk->put('hp/uploads/c6/apli_home_thumbnail/2.gif', 'x');
    DB::table('contents_master')->insert(['cd' => 2, 'field' => 'apli_home_comment', 'text_data' => "a:2:{i:0;s:3:'all';i:2;s:6:'branch';}",
        'disp_flg' => '1', 'hyouji_flg' => '1']);
    mpaConfig(['home_image_flg' => [2 => '']]);

    $body = json_decode($this->get('/api/mpa_corresponding.php?login_id=u1')->getContent(), true);

    expect($body['application_main_image']['main'])->toStartWith('http://localhost/hp/uploads/c6/apli_home_image/2.jpg?r=')
        ->and($body['apli_home_thumbnail']['main'])->toStartWith('http://localhost/hp/uploads/c6/apli_home_thumbnail/2.gif?r=')
        ->and($body['apli_home_comment'])->toBe('branch');

    // Sin socio: la común (0) y el comentario 0; el .gif de la miniatura no cuenta
    $body = json_decode($this->get('/api/mpa_corresponding.php')->getContent(), true);
    expect($body['application_main_image']['small'])->toContain('/apli_home_image/0_s.jpg?r=')
        ->and($body)->not->toHaveKey('apli_home_thumbnail')
        ->and($body['apli_home_comment'])->toBe('all');
});

it('mpa_getTopBanner y mpa_getCoverFlow: con socio solo los huecos visibles para su tienda; sin socio, todos', function () {
    $disk = Storage::disk('public');
    $disk->put('hp/uploads/c6/aplibanner/0.jpg', 'x');
    $disk->put('hp/uploads/c6/aplibanner/1.jpg', 'x');
    $disk->put('hp/uploads/c6/aplicover/10.jpg', 'x');
    $disk->put('hp/uploads/c6/aplicover/2.jpg', 'x');
    DB::table('contents_master')->insert([
        ['cd' => 3, 'category' => 'tb_c6_user_hp', 'field' => 'aplibanner', 'text_data' => 'http://a||http://b', 'disp_flg' => '1', 'hyouji_flg' => '1'],
        ['cd' => 4, 'category' => 'tb_c6_user_hp', 'field' => 'aplicover', 'text_data' => 'http://c2||http://c10', 'disp_flg' => '1', 'hyouji_flg' => '1'],
    ]);
    mpaConfig(['banner' => [1 => [2 => '1']], 'cover' => [1 => [0 => '1']]]);

    $banner = $this->get('/api/mpa_getTopBanner.php?login_id=u1')->assertHeader('Content-Type', 'application/json; charset=utf-8');
    expect(json_decode($banner->getContent(), true)['data'])->toHaveCount(1)
        ->and(json_decode($banner->getContent(), true)['data'][0]['url'])->toBe('http://b');

    // Portada: orden numérico (2 antes que 10); solo el hueco 1 (10.jpg) es visible
    $cover = json_decode($this->get('/api/mpa_getCoverFlow.php?login_id=u1')->getContent(), true);
    expect($cover['data'])->toHaveCount(1)
        ->and($cover['data'][0]['imageUrl'])->toContain('/aplicover/10.jpg?r=')
        ->and($cover['data'][0]['url'])->toBe('http://c10');

    // Sin socio: todas, en orden de glob (10.jpg antes que 2.jpg)
    $all = json_decode($this->get('/api/mpa_getCoverFlow.php')->getContent(), true);
    expect(array_column($all['data'], 'url'))->toBe(['http://c2', 'http://c10'])
        ->and($all['data'][0]['imageUrl'])->toContain('/aplicover/10.jpg');
});

it('mpa_setraiten con membership=1 registra el beacon en la tienda pedida', function () {
    $this->get('/api/mpa_setraiten.php?regist_id=1000001&membership=1&shop_cd=1&beacon_cd=7')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    $row = DB::table('tb_beacon_history')->first();
    expect($row->beacon_cd)->toBe(7)
        ->and($row->tb_customer_id)->toBe('1000001')
        ->and($row->tb_shop_cd)->toBe(1)
        ->and(DB::table('tb_raiten_rireki')->count())->toBe(0);
});

it('mpa_albumUpdate: guarda la foto y sus tamaños, la fila del álbum y vuelve a la app con status=ok', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_photo_album', 'kazu' => 5]);

    $this->post('/api/mpa_albumUpdate.php', [
        'domain' => 'test', 'cd' => '1000001', 'comment' => 'いい "写真"',
        'pic_name' => UploadedFile::fake()->image('foto.png', 1000, 500),
    ])->assertStatus(302)->assertHeader('Location', 'http://b-develop.g-api.net/api/mpa/myAlbumPage?domain=test&cd=1000001&status=ok');

    $album = DB::table('tb_photo_album')->where('cd', 5)->first();
    expect($album->tb_customer_cd)->toBe(10)
        ->and($album->comment)->toBe('いい &quot;写真&quot;')
        ->and($album->pic_flg)->toBe('1')
        ->and(DB::table('hatuban')->where('koumoku', 'tb_photo_album')->value('kazu'))->toBe(6);

    $disk = Storage::disk('public');
    foreach (['0_moto.png', '0_moto.jpg', '0_l.jpg', '0_m.jpg', '0_s.jpg', '0_ss.jpg'] as $file) {
        $disk->assertExists('hp/uploads/c1/photoalbum/5/'.$file);
    }
    expect(getimagesizefromstring($disk->get('hp/uploads/c1/photoalbum/5/0_l.jpg'))[0])->toBe(480)
        ->and(getimagesizefromstring($disk->get('hp/uploads/c1/photoalbum/5/0_s.jpg'))[0])->toBe(150);

    // Sin domain: err1 con el comentario
    $this->post('/api/mpa_albumUpdate.php', ['cd' => '1000001', 'comment' => 'a b'])
        ->assertHeader('Location', 'http://b-develop.g-api.net/api/mpa/myAlbumPage?domain=&cd=1000001&status=err1&comment=a+b');
});
