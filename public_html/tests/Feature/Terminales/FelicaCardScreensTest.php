<?php

use App\Models\Customers\Customer;
use App\Models\Terminales\FelicaCard;
use App\Services\Customers\CustomerFelicaService;
use App\Services\Terminales\FelicaCardService;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* ICカード管理 / カード管理 (felica/card_list.php, ajax_search_kaiin.php, ajax_del.php) y FelicaCardService */

beforeEach(function () {
    s4Login(); // master de la tienda 1

    DB::table('tb_customer')->insert([
        ['cd' => 101, 'id' => 'M0101', 'tb_shop_cd' => 1, 'name' => '山田 太郎', 'name_kana' => 'やまだ たろう', 'touroku_kubun' => '2', 'felica_no' => 'IDM-A', 'disp_flg' => '1', 'kojin_birth' => '1990/01/02'],
        ['cd' => 102, 'id' => 'M0102', 'tb_shop_cd' => 1, 'name' => '', 'name_kana' => '', 'touroku_kubun' => '2', 'felica_no' => 'IDM-B', 'disp_flg' => '1', 'kojin_birth' => '//'],
        ['cd' => 103, 'id' => 'M0103', 'tb_shop_cd' => 1, 'name' => '佐藤 花子', 'name_kana' => 'さとう はなこ', 'touroku_kubun' => '2', 'felica_no' => '', 'disp_flg' => '1', 'kojin_birth' => '1985/05/05'],
    ]);

    DB::table('tb_customer_card')->insert([
        ['cd' => 11, 'tb_shop_cd' => 1, 'tb_customer_cd' => 101, 'cid' => 'IDM-A', 'typ_exp' => '非モバイルFellica', 'flg' => '1', 'update_date' => '1700000300'],
        ['cd' => 12, 'tb_shop_cd' => 1, 'tb_customer_cd' => 102, 'cid' => 'IDM-B', 'typ_exp' => 'TypeA マイフェア 1k', 'flg' => '1', 'update_date' => '1700000200'],
        ['cd' => 13, 'tb_shop_cd' => 1, 'tb_customer_cd' => null, 'cid' => 'IDM-C', 'typ_exp' => '非モバイルFellica', 'flg' => '1', 'update_date' => '1700000100'],
        ['cd' => 14, 'tb_shop_cd' => 1, 'tb_customer_cd' => 101, 'cid' => 'IDM-X', 'typ_exp' => 'モバイルFeliCa', 'flg' => '1', 'update_date' => '1700000000'],
    ]);
    DB::table('tb_user_card')->insert(['cd' => 1, 'felica_no' => 'IDM-A', 'staff_cd' => '101', 'card_flg' => '1']);
});

function felicaSearch(array $data = [])
{
    return test()->post(route('admin.terminales.felica-cards.index'), $data + [
        'search_id' => '', 'search_name' => '', 'search_name_kana' => '', 'sch_joint_flg' => '全', 'andor' => '', 'search' => 'each_search',
    ]);
}

it('sin buscar pide 検索して下さい; la URL del legacy redirige', function () {
    $this->get(route('admin.terminales.felica-cards.index', ['sesClear' => 'card']))
        ->assertOk()
        ->assertSee('■カード管理', false)
        ->assertSee('検索して下さい')
        ->assertSee('value="未" checked', false);

    $this->get('/system/category7/felica/card_list.php?sesClear=card')
        ->assertRedirect(route('admin.terminales.felica-cards.index', ['sesClear' => 'card']));
});

it('lista con 全 las tarjetas de los tipos visibles, con socio y tienda', function () {
    felicaSearch()
        ->assertOk()
        ->assertSee('3件', false)
        ->assertSee('【M0101】', false)
        ->assertSee('山田 太郎')
        ->assertSee('IDM-C')
        ->assertDontSee('IDM-X') // typ_exp fuera de la lista del legacy
        ->assertSee('<option value="1"  selected="selected"  >本店</option>', false)
        ->assertSeeInOrder(['IDM-A', 'IDM-B', 'IDM-C']); // update_date desc
});

it('filtra 未 (socio sin nombre), 済 (con nombre) y por nombre', function () {
    felicaSearch(['sch_joint_flg' => '未'])
        ->assertSee('1件', false)->assertSee('IDM-B')->assertDontSee('IDM-A')->assertDontSee('IDM-C');

    felicaSearch(['sch_joint_flg' => '済'])
        ->assertSee('1件', false)->assertSee('IDM-A')->assertDontSee('IDM-B');

    felicaSearch(['sch_joint_flg' => '全', 'search_name' => '山田'])
        ->assertSee('1件', false)->assertSee('IDM-A')->assertDontSee('IDM-B');

    // OR: 済 o ID M0102 → las dos asignadas
    felicaSearch(['sch_joint_flg' => '済', 'search_id' => 'M0102', 'andor' => '1'])
        ->assertSee('2件', false)->assertSee('IDM-A')->assertSee('IDM-B');

    // Sin resultados: mensaje, sin error (el legacy daba OFFSET negativo)
    felicaSearch(['search_name' => 'no-existe'])
        ->assertOk()->assertSee('見つかりませんでした');
});

it('recuerda las condiciones en la sesión al paginar', function () {
    felicaSearch(['sch_joint_flg' => '済']);

    $this->get(route('admin.terminales.felica-cards.index', ['search' => 'each_search', 'page' => 1, 'kensu' => 10]))
        ->assertOk()->assertSee('1件', false)->assertSee('value="済" checked', false);
});

it('busca socios para asignar (colorbox) por tienda, nombre y sin tarjeta', function () {
    $this->post(route('legacy.terminales.felica-search-kaiin'), ['shop_cd' => 1, 'name' => '', 'id' => '', 'felica_no' => '1'])
        ->assertOk()
        ->assertSee('1件', false)
        ->assertSee('佐藤 花子')
        ->assertDontSee('山田 太郎')
        ->assertSee('setMember(103, ', false);

    $this->post(route('legacy.terminales.felica-search-kaiin'), ['shop_cd' => 1, 'name' => '山田', 'id' => '', 'felica_no' => ''])
        ->assertSee('山田 太郎')->assertDontSee('佐藤 花子');

    // Sin tienda: el legacy no buscaba
    $this->post(route('legacy.terminales.felica-search-kaiin'), ['name' => ''])
        ->assertOk()->assertSee('データが見つかりませんでした');
});

it('asigna una tarjeta libre a un socio con 更新する y actualiza felica_no vía S2', function () {
    $this->post(route('admin.terminales.felica-cards.update'), [
        'cnt' => 1, 'shop_cd0' => 1, 'main_table_cd0' => 13, 'kaiin_cd0' => 103, 'felica_no0' => 'manipulado', 'act' => '更新する',
    ])->assertRedirect(route('admin.terminales.felica-cards.index', ['search' => 'each_search']));

    expect(FelicaCard::find(13)->tb_customer_cd)->toBe(103)
        ->and(app(CustomerFelicaService::class)->findByIdm('IDM-C')?->cd)->toBe(103)
        ->and(DB::table('tb_customer')->where('felica_no', 'manipulado')->exists())->toBeFalse();
});

it('reasigna una tarjeta: se la quita a quien la tenía', function () {
    $this->post(route('admin.terminales.felica-cards.update'), [
        'cnt' => 1, 'shop_cd0' => 1, 'main_table_cd0' => 11, 'kaiin_cd0' => 103,
    ]);

    $felica = app(CustomerFelicaService::class);
    expect($felica->findByIdm('IDM-A')?->cd)->toBe(103)
        ->and(Customer::find(101)->felica_no)->toBe('')
        ->and(FelicaCard::find(11)->tb_customer_cd)->toBe(103)
        ->and(DB::table('tb_user_card')->where('staff_cd', '101')->exists())->toBeFalse();
});

it('quita la tarjeta al socio con FelicaCardService::unassign', function () {
    $service = app(FelicaCardService::class);

    expect($service->unassign(12))->toBeTrue();

    expect(FelicaCard::find(12)->tb_customer_cd)->toBeNull()
        ->and(Customer::find(102)->felica_no)->toBe('')
        ->and(app(CustomerFelicaService::class)->findByIdm('IDM-B'))->toBeNull();

    // También con el modelo; y false si no existe
    expect($service->unassign(FelicaCard::find(11)))->toBeTrue()
        ->and(Customer::find(101)->felica_no)->toBe('')
        ->and($service->unassign(999))->toBeFalse();
});

it('borra una tarjeta (físico) por AJAX y vacía el felica_no del socio', function () {
    $this->post(route('legacy.terminales.felica-del-card'), ['cd' => 11])
        ->assertOk()
        ->assertSee("\$('#row_tr11').remove();", false);

    expect(DB::table('tb_customer_card')->where('cd', 11)->exists())->toBeFalse()
        ->and(Customer::find(101)->felica_no)->toBe('');

    $this->post(route('legacy.terminales.felica-del-card'), ['cd' => 'x'])
        ->assertSee('削除失敗しました。');
});
