<?php

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/* appview/photo_gallery.php y visit_history.php: leen la API externa g-api.net (simulada con Http::fake) */

beforeEach(function () {
    DB::table('admin_master')->insert(['cd' => 1, 'push_api_host' => '1']);
    config(['crm.gapi.enabled' => true]); // las llamadas se simulan con Http::fake (interruptor común, NUCLEO.md §9)
});

it('photo_gallery: muestra solo las fotos de g-api.net cuyo archivo existe en hp/uploads', function () {
    Storage::fake('public');
    Storage::disk('public')->put('hp/uploads/photo_album/1/a.jpg', 'x');
    Http::fake(['b.g-api.net/*' => Http::response(json_encode(['status' => 'ok', 'data' => [
        ['largeImageUrl' => 'http://crm.example.test/hp/uploads/photo_album/1/a.jpg?r=1'],
        ['largeImageUrl' => 'http://crm.example.test/hp/uploads/photo_album/1/borrada.jpg?r=1'],
    ]]))]);

    $body = $this->get('http://crm.example.test/appview/photo_gallery.php')->assertOk()->getContent();

    Http::assertSent(fn (HttpRequest $r) => $r->url() === 'http://b.g-api.net/api/mpa/getPhotoGallery?domain=crm.example.test');
    expect($body)->toContain('<a href="http://crm.example.test/hp/uploads/photo_album/1/a.jpg?r=1" rel="lightbox">')
        ->not->toContain('borrada.jpg')
        ->toContain("<title>フォトギャラリー</title>\r\n");
});

it('visit_history: 来店履歴 de g-api.net por cutomer_cd (sic) y el aviso cuando no hay datos', function () {
    DB::table('admin_master')->update(['push_api_host' => '0']);
    Http::fake([
        'b-develop.g-api.net/*' => Http::sequence()
            ->push(json_encode(['status' => 'ok', 'data' => [['shop_name' => '本店', 'coming_date_time' => '2026/03/04 10:00']]]))
            ->push('', 500),
    ]);

    $body = $this->get('http://crm.example.test/appview/visit_history.php?cutomer_cd=1000001')->assertOk()->getContent();
    Http::assertSent(fn (HttpRequest $r) => $r->url() === 'http://b-develop.g-api.net/api/mpa/getComingHistory?domain=crm.example.test&login_id=1000001');
    expect($body)->toContain('<p class="shop_name">本店</p>')->toContain('<p class="coming_date_time">2026/03/04 10:00</p>');

    $empty = $this->get('http://crm.example.test/appview/visit_history.php?cutomer_cd=1000001')->getContent();
    expect($empty)->not->toContain('shop_name">')->toContain('来店履歴はありません。');
});

it('con g-api apagado (CRM_GAPI_ENABLED=false) no sale la petición y las vistas quedan sin datos', function () {
    config(['crm.gapi.enabled' => false]);
    Http::fake();

    $this->get('http://crm.example.test/appview/visit_history.php?cutomer_cd=1000001')->assertOk()->assertSee('来店履歴はありません。');
    $this->get('http://crm.example.test/appview/photo_gallery.php')->assertOk()->assertDontSee('rel="lightbox"', false);
    Http::assertNothingSent();
});

it('sin fila en admin_master no se llama a g-api.net', function () {
    DB::table('admin_master')->delete();
    Http::fake();

    $this->get('/appview/visit_history.php?cutomer_cd=1')->assertOk();
    Http::assertNothingSent();
});
