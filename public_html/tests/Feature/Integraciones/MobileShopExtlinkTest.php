<?php

use App\Services\Integraciones\MobileShop\HttpMobileShopClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/* API extlink de la tienda móvil (有料会員) y su interruptor services.mobileshop.extlink_enabled (como App\Support\GApi) */

beforeEach(function () {
    config([
        'services.mobileshop.extlink_url' => 'https://extlink.example.test',
        'services.mobileshop.extlink_pass' => 'clave-de-prueba',
        'services.mobileshop.domain_list' => '',
    ]);
    DB::table('tb_customer')->insert(['cd' => 1, 'id' => '1000001', 'name' => '山田', 'disp_flg' => '1']);
});

it('encendido: pide el token y el estado de pago y guarda paid_member_flg', function () {
    config(['services.mobileshop.extlink_enabled' => true]);
    Http::fake([
        'extlink.example.test/apiSnderAuth.php' => Http::response(['jwt' => 'token-extlink']),
        'extlink.example.test/apiJudServiceTgt.php' => Http::response(['result' => ['data' => [['flg_service' => 1, 'srv_strt_ymd' => '2026/11/01']]]]),
    ]);
    $client = app(HttpMobileShopClient::class);

    $token = $client->fetchToken();
    $paid = $client->fetchPaidMemberFlg($token, '1000001');

    expect($token)->toBe('token-extlink')
        ->and($paid->flg_service)->toBe(1)
        ->and(DB::table('tb_customer')->where('cd', 1)->value('paid_member_flg'))->toBe(1);
    Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://extlink.example.test/apiSnderAuth.php'
        && $r['user_id'] === 'extlink' && $r['password'] === 'clave-de-prueba');
    Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://extlink.example.test/apiJudServiceTgt.php'
        && $r->hasHeader('Authorization', 'Bearer token-extlink') && str_ends_with((string) $r['user_id'], '001000001'));
});

it('apagado (fuera de producción): no sale ninguna petición ni se toca tb_customer', function () {
    config(['services.mobileshop.extlink_enabled' => false]);
    Http::fake();
    $client = app(HttpMobileShopClient::class);

    expect($client->fetchToken())->toBeFalse()
        ->and($client->fetchPaidMemberFlg('token', '1000001'))->toBeFalse()
        ->and($client->sendUnpaidMemberStatus('token', '1000001'))->toBeFalse()
        ->and(DB::table('tb_customer')->where('cd', 1)->value('paid_member_flg_confirmation_time'))->toBeNull();
    Http::assertNothingSent();
});

it('por defecto solo está encendido en producción', function () {
    expect(config('services.mobileshop.extlink_enabled'))->toBeFalse(); // APP_ENV=testing sin MOBILESHOP_EXTLINK_ENABLED
});
