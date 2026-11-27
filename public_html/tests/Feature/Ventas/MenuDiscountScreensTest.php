<?php

use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;

require_once __DIR__.'/helpers.php';

/* メニュー管理 (karute_menu.php) y 割引管理 (karute_waribiki.php): pantalla, alta, edición, ▲▼ y borrado */

beforeEach(function () {
    s4Login();
});

it('carga メニュー管理: sin tienda pide elegirla y con tienda muestra el formulario', function () {
    $this->get(route('admin.ventas.menus.index'))
        ->assertOk()
        ->assertSee('<title>メニュー管理 - ', false)
        ->assertSee('※登録・閲覧したい店舗を選択してください。')
        ->assertDontSee('メニュー登録情報');

    $this->get(route('admin.ventas.menus.index', ['shop' => 1]))
        ->assertOk()
        ->assertSee('■本店')
        ->assertSee('メニュー登録情報')
        ->assertSee('この内容で登録する')
        ->assertSee('/system/category1/karute_menu/ajax_karute_menu_list.php', false);
});

it('da de alta menús con rank max + 1, los edita, los ordena y los borra corriendo el rank', function () {
    $this->post(route('admin.ventas.menus.store'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => ' ｶｯﾄ ', 'kingaku' => '5,000', 'type' => '1'])
        ->assertRedirect(route('admin.ventas.menus.index', ['shop' => 1]));
    $this->post(route('admin.ventas.menus.store'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => 'シャンプー', 'kingaku' => '1500', 'type' => '2']);
    $this->post(route('admin.ventas.menus.store'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => 'カラー', 'kingaku' => '7000', 'type' => '3']);

    $menus = ServiceMenu::query()->ofShop(1)->ordered()->get();
    expect($menus->pluck('name')->all())->toBe(['カット', 'シャンプー', 'カラー'])
        ->and($menus->pluck('rank')->all())->toBe([1, 2, 3])
        ->and($menus[0]->kingaku)->toBe(5000)
        ->and($menus[0]->touroku_date)->not->toBeEmpty();

    // Edición
    $this->get(route('admin.ventas.menus.index', ['shop' => 1, 'cd' => $menus[1]->cd]))
        ->assertOk()->assertSee('この内容で修正する')->assertSee('value="シャンプー"', false);
    $this->post(route('admin.ventas.menus.store'), ['act' => 'up', 'cd' => $menus[1]->cd, 'tb_shop_cd' => 1, 'name' => 'ヘッドスパ', 'kingaku' => '3000', 'type' => '2']);
    expect($menus[1]->fresh()->only('name', 'kingaku', 'rank'))->toBe(['name' => 'ヘッドスパ', 'kingaku' => 3000, 'rank' => 2]);

    // Listado AJAX con ▲ (カラー sube por encima de ヘッドスパ)
    $this->post(route('legacy.ventas.ajax-menu-list'), ['shop_cd' => 1, 'send_type' => 'rankup', 'send_targetCd' => $menus[2]->cd])
        ->assertOk()
        ->assertSeeInOrder(['カット', '5,000', '技術', 'カラー', 'その他', 'ヘッドスパ', '物販'])
        ->assertSee('▲')
        ->assertSee(route('admin.ventas.menus.destroy'), false);

    // Borrado: disp_flg 9, rank null y los siguientes suben
    $this->post(route('admin.ventas.menus.destroy'), ['act' => 'del', 'cd' => $menus[0]->cd, 'tb_shop_cd' => 1])
        ->assertRedirect(route('admin.ventas.menus.index', ['shop' => 1]));

    $deleted = ServiceMenu::withDeleted()->find($menus[0]->cd);
    expect($deleted->disp_flg)->toBe('9')->and($deleted->rank)->toBeNull()
        ->and(ServiceMenu::query()->ordered()->pluck('rank', 'name')->all())->toBe(['カラー' => 1, 'ヘッドスパ' => 2]);
});

it('el listado AJAX sin tienda responde vacío y sin datos dice データがありません。', function () {
    $this->post(route('legacy.ventas.ajax-menu-list'), ['shop_cd' => ''])->assertOk()->assertContent('');
    $this->post(route('legacy.ventas.ajax-menu-list'), ['shop_cd' => 1])->assertOk()->assertSee('データがありません。');
});

it('da de alta, edita, lista y borra descuentos de 割引管理', function () {
    $this->get(route('admin.ventas.discounts.index', ['shop' => 1]))
        ->assertOk()
        ->assertSee('<title>割引管理 - ', false)
        ->assertSee('割引登録情報')
        ->assertSee('割引額を歩合に含まない');

    $this->post(route('admin.ventas.discounts.store'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => '会員割', 'value' => '10', 'type' => '1', 'buai_flg' => '1'])
        ->assertRedirect(route('admin.ventas.discounts.index', ['shop' => 1]));
    $this->post(route('admin.ventas.discounts.store'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => 'クーポン', 'value' => '500', 'type' => '2', 'buai_flg' => '2']);

    $discounts = Discount::query()->ordered()->get();
    expect($discounts->pluck('rank')->all())->toBe([1, 2])
        ->and($discounts[1]->only('value', 'type', 'buai_flg'))->toBe(['value' => 500, 'type' => '2', 'buai_flg' => '2']);

    $this->post(route('admin.ventas.discounts.store'), ['act' => 'up', 'cd' => $discounts[0]->cd, 'tb_shop_cd' => 1, 'name' => '会員割', 'value' => '15', 'type' => '1', 'buai_flg' => '2']);
    expect($discounts[0]->fresh()->only('value', 'buai_flg'))->toBe(['value' => 15, 'buai_flg' => '2']);

    $this->post(route('legacy.ventas.ajax-waribiki-list'), ['shop_cd' => 1, 'send_type' => 'rankdown', 'send_targetCd' => $discounts[0]->cd])
        ->assertOk()
        ->assertSeeInOrder(['クーポン', '500円', '割引額を歩合に含まない', '会員割', '15％']);

    $this->post(route('admin.ventas.discounts.destroy'), ['act' => 'del', 'cd' => $discounts[1]->cd]);
    expect(Discount::query()->pluck('rank', 'name')->all())->toBe(['会員割' => 1]);
});

it('valida 名称 y 金額 en el servidor', function () {
    $this->from(route('admin.ventas.menus.index', ['shop' => 1]))
        ->post(route('admin.ventas.menus.store'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => '', 'kingaku' => ''])
        ->assertSessionHasErrors(['name', 'kingaku']);

    expect(ServiceMenu::query()->count())->toBe(0);
});
