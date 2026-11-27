<?php

require_once __DIR__ . '/helpers.php';

use App\Models\Reservations\Facility;

// 設備管理 (legacy: setsubi_new.php, setsubi_register.php)

beforeEach(function () {
    reservasLogin();
});

it('carga la pantalla de la tienda con su lista', function () {
    $this->get(route('admin.reservas.facilities.index', ['shop' => 1]))
        ->assertOk()
        ->assertSee('設備登録情報')
        ->assertSee('設備一覧')
        ->assertSee('登録データがありません。');
});

it('da de alta, edita y borra un 設備', function () {
    $this->post(route('admin.reservas.facilities.store'), ['shop_cd' => 1, 'name' => '個室', 'quantity' => '3'])
        ->assertRedirect(route('admin.reservas.facilities.index', ['shop' => 1]));

    $facility = Facility::firstOrFail();
    expect($facility->name)->toBe('個室')
        ->and($facility->quantity)->toBe(3)
        ->and($facility->tb_shop_cd)->toBe(1)
        ->and($facility->disp_flg)->toBe('1');

    $this->get(route('admin.reservas.facilities.index', ['cd' => $facility->cd]))
        ->assertOk()
        ->assertSee('この内容で修正する')
        ->assertSee('個室');

    $this->put(route('admin.reservas.facilities.update', $facility->cd), ['shop_cd' => 1, 'name' => '個室B', 'quantity' => '4'])
        ->assertRedirect();
    expect($facility->fresh()->name)->toBe('個室B')->and($facility->fresh()->quantity)->toBe(4);

    $this->delete(route('admin.reservas.facilities.destroy', $facility->cd), ['shop_cd' => 1])
        ->assertRedirect(route('admin.reservas.facilities.index', ['shop' => 1]));
    expect(Facility::withDeleted()->find($facility->cd)->disp_flg)->toBe('9');
});

it('valida 名称 y 数量 con los textos del legacy', function () {
    $this->post(route('admin.reservas.facilities.store'), ['shop_cd' => 1, 'name' => '', 'quantity' => '0'])
        ->assertSessionHasErrors(['name' => '「名称」が未入力です。', 'quantity' => '「数量」半角数字で入力してください。']);

    expect(Facility::count())->toBe(0);
});

it('responde 404 a un cd inexistente (el legacy: 不正アクセス)', function () {
    $this->get(route('admin.reservas.facilities.index', ['cd' => 999]))->assertNotFound();
});
