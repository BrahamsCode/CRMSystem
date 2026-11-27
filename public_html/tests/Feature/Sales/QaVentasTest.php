<?php

require_once __DIR__.'/../Reservations/helpers.php';

use App\Models\Customers\Customer;
use App\Models\Sales\ServiceMenu;

// Fallos del testeo real de ventas en el navegador (Doc/qa/regresion.md, tercera pasada, S2)

beforeEach(function () {
    reservasLogin();
});

it('pasa el cd de 19 cifras como cadena al botón 削除 de 売上データ削除 (en JS perdía precisión y no borraba)', function () {
    $customer = Customer::factory()->create(['cd' => 1773810038002900241, 'name' => '長谷川 さくら']);
    $menu = ServiceMenu::create(['tb_shop_cd' => 1, 'name' => 'カット', 'kingaku' => 3000, 'type' => '1', 'rank' => 1, 'disp_flg' => '1', 'hyouji_flg' => '1']);
    $this->post(route('admin.ventas.sales.store'), [
        'update_date' => now('Asia/Tokyo')->format('Y/m/d'), 'add_record_verify' => '1', 'shop' => 1,
        'kaiin_cd' => '1773810038002900241', 'staff_cd' => '10', 'menu_cd' => [$menu->cd],
    ])->assertRedirect();

    $this->get('/system/category1/karute_menu/karute_delete_list.php?shop=1')->assertOk()
        ->assertSee("deleteHandler('1773810038002900241','1')", false)
        ->assertDontSee('deleteHandler(1773810038002900241', false);
});
