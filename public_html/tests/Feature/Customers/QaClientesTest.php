<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Services\Nucleo\LegacyUi;

// Fallos encontrados en el testeo real del navegador (Doc/qa/regresion.md, tercera pasada, S2 clientes)

beforeEach(fn () => reservasLogin());

it('titula la ficha y 来店処理 con el nombre del sitio, no con APP_NAME', function () {
    $customer = Customer::factory()->create(['name' => '山田 花子']);
    $site = app(LegacyUi::class)->siteName();
    expect($site)->not->toBe('');

    $this->get(route('admin.clientes.show', $customer->cd))
        ->assertOk()
        ->assertSee('<title>顧客登録情報 - ' . e($site) . '</title>', false)
        ->assertDontSee('<title>顧客登録情報 - ' . config('app.name') . '</title>', false);

    $this->get('/system/category1/customer/customer_search.php')
        ->assertOk()
        ->assertSee('<title>来店処理 - ' . e($site) . '</title>', false);
});

it('no lanza error de JS en la pestaña 有料会員状況 de un 無料会員 (sin botón 変更する)', function () {
    $customer = Customer::factory()->create(['paid_member_flg' => 0]);

    $html = $this->get(route('admin.clientes.show', [$customer->cd, 'tab' => '14']))->assertOk()->getContent();

    expect($html)->not->toContain('id="paidMemberSendBtn"')
        ->and($html)->toContain('if (! paidMemberSendBtn)');
});

it('declara Shift_JIS en el Content-Type de los CSV de clientes (el cuerpo va en SJIS)', function () {
    Customer::factory()->create(['name' => '佐藤 花子']);

    $response = $this->get(route('admin.clientes.export', ['format' => 'standard']));
    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('text/x-csv; charset=Shift_JIS');

    $response = $this->get(route('admin.clientes.export', ['format' => 'ex02']));
    expect($response->headers->get('Content-Type'))->toBe('text/x-csv; charset=Shift_JIS');
});
