<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Integraciones\MobileShop\MobileShopClient;

// 有料会員 (legacy: system/category1/inc/ajax_changePaidMemberFlg.php)

beforeEach(fn () => reservasLogin());

function clientesFakeMobileShop(int|false $code): object
{
    $fake = Mockery::mock(MobileShopClient::class);
    $fake->shouldReceive('fetchToken')->andReturn('token');
    $fake->shouldReceive('sendUnpaidMemberStatus')->andReturn($code);
    app()->instance(MobileShopClient::class, $fake);

    return $fake;
}

it('avisa a MobileShop y responde ok como el legacy', function () {
    $customer = Customer::factory()->create();
    $fake = clientesFakeMobileShop(1);

    $this->post('/system/category1/inc/ajax_changePaidMemberFlg.php', ['cd' => (string) $customer->cd])
        ->assertOk()->assertExactJson(['status' => 'ok', 'message' => 'Success.']);

    $fake->shouldHaveReceived('sendUnpaidMemberStatus')->with('token', $customer->id);
});

it('responde ng con el código si MobileShop falla y type error si cd no es numérico', function () {
    $customer = Customer::factory()->create();
    clientesFakeMobileShop(3);

    $this->post('/system/category1/inc/ajax_changePaidMemberFlg.php', ['cd' => (string) $customer->cd])
        ->assertExactJson(['status' => 'ng', 'message' => '処理失敗しました。code:3']);
    $this->post('/system/category1/inc/ajax_changePaidMemberFlg.php', ['cd' => 'x'])
        ->assertSeeText('type error');
});
