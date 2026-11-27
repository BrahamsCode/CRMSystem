<?php

use App\Services\Base\ShopService;
use App\Support\GApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

require_once __DIR__ . '/helpers.php';

// Interruptor común de g-api (App\Support\GApi): fuera de producción no sale ninguna llamada a la API de la app

it('está apagado fuera de producción y no envía nada', function () {
    Http::fake();

    expect(config('crm.gapi.enabled'))->toBeFalse()
        ->and(GApi::enabled())->toBeFalse()
        ->and(GApi::get('http://b.g-api.net/api/mpa/setStoreTel', ['domain' => 'x']))->toBeNull()
        ->and(GApi::postForm('http://b.g-api.net/api/support/verifyAccountLogin.php', ['password' => 'x']))->toBeNull()
        ->and(GApi::send('http://b.g-api.net/api/mpa/pushMessage', fn () => Http::get('http://b.g-api.net/api/mpa/pushMessage')))->toBeNull();

    Http::assertNothingSent();
});

it('encendido hace la petición', function () {
    config(['crm.gapi.enabled' => true]);
    Http::fake(['b.g-api.net/*' => Http::response(['status' => 'ok'])]);

    expect(GApi::get('http://b.g-api.net/api/mpa/setStoreTel', ['domain' => 'crm.example.com'])->json('status'))->toBe('ok');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'http://b.g-api.net/api/mpa/setStoreTel?domain=crm.example.com'));
});

it('al guardar una tienda con la app activa solo avisa setStoreTel si g-api está encendido', function () {
    seedNucleo();
    Mail::fake();
    Http::fake();
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => '1', 'push_api_host' => '1']);
    $input = ['kaishamei' => '本店', 'mycolle_tel' => '0612345678'];

    app(ShopService::class)->save($input, 1, 'crm.example.com');
    Http::assertNothingSent();

    config(['crm.gapi.enabled' => true]);
    app(ShopService::class)->save($input, 1, 'crm.example.com');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'b.g-api.net/api/mpa/setStoreTel') && $request['tel'] === '0612345678');
});
