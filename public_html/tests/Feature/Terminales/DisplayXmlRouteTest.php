<?php

use App\Services\Terminales\SignageFeedBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/* URL del legacy que pide el reproductor: /hp/uploads/c7/display/<cd>.xml y floormap.xml */

beforeEach(fn () => Storage::fake('public'));

it('genera y sirve el XML de un programa en la URL del legacy, sin sesión', function () {
    DB::table('tb_screensaver')->insert([
        'cd' => 801, 'title' => 'A', 'play_frame_sec' => '10', 'filetype' => '画像ファイル',
        'pic_extension' => 'jpg', 'pic_flg' => '1', 'disp_flg' => '1',
    ]);
    DB::table('tb_c7_movie_program')->insert([
        'cd' => 850, 'name' => 'P', 'disp_conf' => '1', 'disp_flg' => '1', 'tb_screensaver_cds' => '801<>',
    ]);

    $response = $this->get('/hp/uploads/c7/display/850.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/xml')
        ->and($response->getContent())->toContain('<p cd="801" time="10"  title="A"')
        ->and(Storage::disk('public')->exists('hp/uploads/c7/display/850.xml'))->toBeTrue();
});

it('entrega el archivo ya guardado sin regenerarlo', function () {
    Storage::disk('public')->put(SignageFeedBuilder::DIR.'/42.xml', '<data>guardado</data>');

    expect($this->get('/hp/uploads/c7/display/42.xml')->assertOk()->getContent())->toBe('<data>guardado</data>');
});

it('sirve floormap.xml', function () {
    $this->get('/hp/uploads/c7/display/floormap.xml')
        ->assertOk()
        ->assertSee('<data>', false);
});

it('no responde a nombres que no son de programa', function () {
    $this->get('/hp/uploads/c7/display/otro.xml')->assertNotFound();
    expect(route('publico.senalizacion.display-xml', '850.xml'))->toEndWith('/hp/uploads/c7/display/850.xml');
});
