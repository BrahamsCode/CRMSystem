<?php

require_once __DIR__.'/../Reservations/helpers.php';

use App\Models\Sales\SalesItem;
use App\Services\Sales\ProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// 販売管理 (legacy: system/category2/index.php, item/list.php, item/edit.php, item/ajax_edit.php, item/ajax_list_delete.php)

beforeEach(function () {
    reservasLogin();
    Storage::fake('public');
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
});

function ventasProductData(array $overrides = []): array
{
    return $overrides + [
        'name' => 'シャンプー', 'display_name' => 'しっとりシャンプー', 'display_title' => '人気商品',
        'discription' => '<p>一覧用</p>', 'discription_detail' => '<p>詳細用</p>', 'is_exchangeable_of_point' => '1',
        'number_of_points_exchanged' => '300', 'available_shop_cds' => ['1', '2'], 'hyouji_flg' => '1',
    ];
}

it('carga la portada de 販売管理', function () {
    $this->get('/system/category2/')->assertOk()->assertSee('cover2.gif')->assertSee('<title>トップ - ', false);
    $this->get(route('admin.ventas.home'))->assertOk();
});

it('lista y busca productos (list.php)', function () {
    $item = new SalesItem;
    app(ProductService::class)->save($item, ventasProductData(), null, false);
    app(ProductService::class)->save(new SalesItem, ventasProductData(['name' => 'リンス', 'available_shop_cds' => ['2'], 'hyouji_flg' => '0']), null, false);

    $this->get('/system/category2/item/list.php')->assertOk()
        ->assertSee('■商品検索')->assertSee('■商品一覧')->assertSee('シャンプー')->assertSee('リンス')->assertSee('本店')->assertSee('支店');
    $this->get(route('admin.ventas.products.index', ['name' => 'シャン　ベビー']))->assertOk()
        ->assertSee('検索条件に合った商品は、見つかりませんでした。再度検索してください');
    $this->get(route('admin.ventas.products.index', ['name' => 'シャン', 'act' => 'search']))->assertOk()
        ->assertSee('シャンプー')->assertDontSee('リンス')
        ->assertSee(route('admin.ventas.products.edit', $item->cd).'?name=', false);
});

it('valida como ajax_edit.php con los mensajes del legacy', function () {
    $this->getJson(route('legacy.ventas.products.validate', ventasProductData()))->assertOk()->assertExactJson(['status' => 'success']);

    $response = $this->getJson(route('legacy.ventas.products.validate', ['is_exchangeable_of_point' => '1', 'number_of_points_exchanged' => 'abc', 'hyouji_flg' => '1']))
        ->assertOk()->assertJsonPath('status', 'error');
    expect($response->json('errorMsg.name'))->toBe(['商品名(正式)を入力してください'])
        ->and($response->json('errorMsg.available_shop_cds'))->toBe(['使用可能な店舗を入力してください'])
        ->and($response->json('errorMsg.number_of_points_exchanged'))->toBe(['ポイント交換数は数字で入力してください']);

    // Sin intercambio por puntos, ポイント交換数 no se comprueba
    $this->getJson(route('legacy.ventas.products.validate', ventasProductData(['is_exchangeable_of_point' => '0', 'number_of_points_exchanged' => 'x'])))
        ->assertExactJson(['status' => 'success']);
});

it('da de alta un producto con imagen 800/320 y lo edita (edit.php)', function () {
    $this->get('/system/category2/item/edit.php')->assertOk()->assertSee('■商品新規登録')->assertSee('ckeditor.js')->assertSee('使用可能な店舗');

    $this->post('/system/category2/item/edit.php', ventasProductData([
        'act' => 'add', 'myImage' => UploadedFile::fake()->image('item.jpg', 1000, 750),
    ]))->assertRedirect();

    $item = SalesItem::query()->firstOrFail();
    expect($item->name)->toBe('シャンプー')->and($item->discription)->toBe('<p>一覧用</p>')
        ->and($item->availableShopCds())->toBe([1, 2])->and($item->number_of_points_exchanged)->toBe(300)
        ->and($item->disp_flg)->toBe(1)->and($item->hyouji_flg)->toBe(1)->and($item->touroku_date)->not->toBeEmpty();

    $dir = 'hp/uploads/c2/item/'.$item->cd;
    Storage::disk('public')->assertExists([$dir.'/'.$item->cd.'.jpg', $dir.'/'.$item->cd.'_big.jpg', $dir.'/'.$item->cd.'_small.jpg']);
    expect(getimagesizefromstring(Storage::disk('public')->get($dir.'/'.$item->cd.'_big.jpg'))[0])->toBe(800)
        ->and(getimagesizefromstring(Storage::disk('public')->get($dir.'/'.$item->cd.'_small.jpg'))[0])->toBe(320);

    $this->get('/system/category2/item/edit.php?cd='.$item->cd)->assertOk()
        ->assertSee('■商品編集')->assertSee($item->cd.'_big.jpg', false)->assertSee('value="register"', false);

    // Edición: sin intercambio (ポイント交換数 se conserva), una tienda y borrado de imagen
    $this->post(route('admin.ventas.products.update', $item->cd), ventasProductData([
        'name' => 'シャンプーL', 'is_exchangeable_of_point' => '0', 'number_of_points_exchanged' => '', 'available_shop_cds' => ['2'], 'img_delete' => '1',
    ]))->assertRedirect(route('admin.ventas.products.edit', $item->cd));
    $item->refresh();
    expect($item->name)->toBe('シャンプーL')->and($item->availableShopCds())->toBe([2])->and($item->number_of_points_exchanged)->toBe(300);
    Storage::disk('public')->assertMissing($dir.'/'.$item->cd.'.jpg');
});

it('rechaza el alta sin los campos obligatorios', function () {
    $this->from('/system/category2/item/edit.php')->post(route('admin.ventas.products.store'), ['hyouji_flg' => '1'])
        ->assertSessionHasErrors(['name' => '商品名(正式)を入力してください']);
    expect(SalesItem::query()->count())->toBe(0);
});

it('borra con disp_flg = 0 (ajax_list_delete.php)', function () {
    $item = new SalesItem;
    app(ProductService::class)->save($item, ventasProductData(), null, false);

    $this->postJson('/system/category2/item/ajax_list_delete.php', ['cd' => $item->cd])->assertExactJson(['status' => 'success']);
    expect(DB::table('sales_items')->where('cd', $item->cd)->value('disp_flg'))->toBe(0)
        ->and(SalesItem::query()->count())->toBe(0);
    $this->postJson('/system/category2/item/ajax_list_delete.php', ['cd' => 999])->assertExactJson(['status' => 'error']);
});
