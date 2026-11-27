<?php

use App\Models\Sales\SalesItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Terminales/helpers.php';

/* 販売管理 (system/category2): portada, 商品検索・一覧, alta/edición, validación AJAX, borrado, menú y URL legacy */

function ventasSeed(): void
{
    s4Login();
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '二号店', 'disp_flg' => '1']);
    DB::table('tb_shop')->insert(['cd' => 3, 'master_cd' => 1, 'kaishamei' => '閉店', 'disp_flg' => '9']);
    DB::table('tb_base_system_child')->insert(['cd' => 201, 'tb_base_system_cd' => 2, 'system_name' => '商品情報管理', 'system_flg' => '1', 'rank' => 1]);
}

function ventasItem(array $data = []): SalesItem
{
    $item = new SalesItem($data + [
        'name' => 'コーヒー', 'display_name' => '珈琲', 'display_title' => '見出し', 'discription' => '<p>一覧</p>',
        'discription_detail' => '<p>詳細</p>', 'is_exchangeable_of_point' => 1, 'number_of_points_exchanged' => 30,
        'hyouji_flg' => 1,
    ]);
    $item->setAvailableShopCds([1, 2]);
    $item->save();

    return $item;
}

function ventasForm(array $data = []): array
{
    return $data + [
        'name' => '紅茶', 'display_name' => 'こうちゃ', 'display_title' => 'おすすめ', 'discription' => '<p>一覧用</p>',
        'discription_detail' => '<p>詳細用 "引用"</p>', 'is_exchangeable_of_point' => '1', 'number_of_points_exchanged' => '50',
        'available_shop_cds' => ['1', '2'], 'hyouji_flg' => '1', 'act' => 'add',
    ];
}

it('muestra la portada de 販売管理 con su menú lateral', function () {
    ventasSeed();

    $this->get(route('admin.ventas.home'))
        ->assertOk()
        ->assertSee('/system/img/cover2.gif', false)
        ->assertSee('<title>販売管理トップ - shop</title>', false)
        ->assertSee('商品情報管理')
        ->assertSee('/admin/ventas/products', false)
        ->assertSee('class="loginName2"', false);
});

it('oculta el bloque del menú si 商品情報管理 no está activo', function () {
    ventasSeed();
    DB::table('tb_base_system_child')->where('cd', 201)->update(['system_flg' => '0']);

    $this->get(route('admin.ventas.home'))->assertOk()->assertDontSee('/admin/ventas/products', false);
});

it('lista los productos visibles y busca por varias palabras en AND', function () {
    ventasSeed();
    $coffee = ventasItem();
    ventasItem(['name' => 'アイスコーヒー', 'discription' => '冷たい', 'is_exchangeable_of_point' => 0]);
    ventasItem(['name' => '削除済み', 'disp_flg' => 0]);

    $this->get(route('admin.ventas.products.index'))
        ->assertOk()
        ->assertSee('<title>商品管理 - shop</title>', false)
        ->assertSee('コーヒー')
        ->assertSee('アイスコーヒー')
        ->assertDontSee('削除済み')
        ->assertSee('本店<br>', false)
        ->assertSee('二号店<br>', false)
        ->assertSee('/admin/ventas/products/'.$coffee->cd.'/edit', false);

    // 全角スペースで区切った2語: 両方を含むものだけ
    $this->get(route('admin.ventas.products.index', ['name' => 'アイス　コーヒー', 'act' => 'search']))
        ->assertOk()
        ->assertSee('アイスコーヒー')
        ->assertSee('/admin/ventas/products/'.($coffee->cd + 1).'/edit?act=search&amp;name=', false)
        ->assertDontSee('/admin/ventas/products/'.$coffee->cd.'/edit', false);

    $this->get(route('admin.ventas.products.index', ['discription' => '存在しない']))
        ->assertOk()
        ->assertSee('検索条件に合った商品は、見つかりませんでした。再度検索してください');
});

it('pagina de 10 en 10 como Pagenation del legacy', function () {
    ventasSeed();
    foreach (range(1, 12) as $i) {
        ventasItem(['name' => '商品'.$i]);
    }

    $this->get(route('admin.ventas.products.index'))
        ->assertOk()
        ->assertSee('商品12')
        ->assertDontSee('>商品2<', false)
        ->assertSee('<li class="page-item visit"><a class="page-link">1</a></li>', false)
        ->assertSee('href="?page=2">＞</a>', false);

    $this->get(route('admin.ventas.products.index', ['page' => 2, 'name' => '商品']))
        ->assertOk()
        ->assertSee('商品2')
        ->assertSee('href="?name=%E5%95%86%E5%93%81&amp;page=1">＜</a>', false);
});

it('muestra el formulario de alta con CKEditor y las tiendas no borradas', function () {
    ventasSeed();

    $this->get(route('admin.ventas.products.create'))
        ->assertOk()
        ->assertSee('■商品新規登録')
        ->assertSee('/common/ckeditor/ckeditor.js', false)
        ->assertSee('/system/category2/item/ajax_edit.php', false)
        ->assertSee('<label for="available_shop_cds1">二号店</label>', false)
        ->assertDontSee('閉店')
        ->assertSee('id="hyouji_flg1" value="1" checked', false)
        ->assertSee('id="is_exchangeable_of_point1" value="0" checked', false);
});

it('da de alta un producto con imagen y tiendas, y va a su edición', function () {
    ventasSeed();
    Storage::fake('public');

    $response = $this->post(route('admin.ventas.products.store'), ventasForm([
        'myImage' => UploadedFile::fake()->image('foto.jpg', 1000, 500),
    ]));

    $item = SalesItem::query()->where('name', '紅茶')->firstOrFail();
    $response->assertRedirect(route('admin.ventas.products.edit', $item->cd));
    expect($item)
        ->disp_flg->toBe(1)
        ->hyouji_flg->toBe(1)
        ->is_exchangeable_of_point->toBe(1)
        ->number_of_points_exchanged->toBe(50)
        ->discription_detail->toBe('<p>詳細用 "引用"</p>')
        ->and($item->availableShopCds())->toBe([1, 2])
        ->and((int) $item->touroku_date)->toBeGreaterThan(0)
        ->and(DB::table('sales_items')->where('cd', $item->cd)->value('available_shop_cds'))->toBe('{1,2}');

    $dir = "hp/uploads/c2/item/{$item->cd}";
    Storage::disk('public')->assertExists(["{$dir}/{$item->cd}.jpg", "{$dir}/{$item->cd}_big.jpg", "{$dir}/{$item->cd}_small.jpg"]);
    $big = getimagesizefromstring(Storage::disk('public')->get("{$dir}/{$item->cd}_big.jpg"));
    $small = getimagesizefromstring(Storage::disk('public')->get("{$dir}/{$item->cd}_small.jpg"));
    expect([$big[0], $big[1], $small[0], $small[1]])->toBe([800, 400, 320, 160]);

    $this->get(route('admin.ventas.products.edit', $item->cd))
        ->assertOk()
        ->assertSee('■商品編集')
        ->assertSee("/{$dir}/{$item->cd}_big.jpg?t=", false)
        ->assertSee('&lt;p&gt;詳細用 &quot;引用&quot;&lt;/p&gt;', false)
        ->assertSee('id="available_shop_cds0" checked', false);

    // La lista muestra la miniatura
    $this->get(route('admin.ventas.products.index'))->assertSee("/{$dir}/{$item->cd}_small.jpg?t=", false);
});

it('rechaza en el servidor un alta incompleta con los mensajes del legacy', function () {
    ventasSeed();

    $this->from(route('admin.ventas.products.create'))
        ->post(route('admin.ventas.products.store'), ventasForm(['name' => '', 'available_shop_cds' => null]))
        ->assertRedirect(route('admin.ventas.products.create'))
        ->assertSessionHasErrors(['name' => '商品名(正式)を入力してください', 'available_shop_cds' => '使用可能な店舗を入力してください']);
    expect(SalesItem::query()->count())->toBe(0);
});

it('valida por AJAX como ajax_edit.php', function () {
    ventasSeed();

    $this->post('/system/category2/item/ajax_edit.php', ventasForm())
        ->assertOk()
        ->assertExactJson(['status' => 'success']);

    $this->getJson('/system/category2/item/ajax_edit.php?'.http_build_query(ventasForm([
        'display_title' => '', 'hyouji_flg' => 'x', 'number_of_points_exchanged' => '1a',
    ])))
        ->assertOk()
        ->assertExactJson([
            'status' => 'error',
            'errorMsg' => [
                'display_title' => ['表示見出しを入力してください'],
                'hyouji_flg' => ['表示を正しく選択してください'],
                'number_of_points_exchanged' => ['ポイント交換数は数字で入力してください'],
            ],
        ]);

    // Sin canje por puntos, la ポイント交換数 no se valida
    $this->postJson(route('admin.ventas.products.validate'), ventasForm(['is_exchangeable_of_point' => '0', 'number_of_points_exchanged' => '']))
        ->assertExactJson(['status' => 'success']);
});

it('edita un producto, conserva la consulta y borra la imagen con img_delete', function () {
    ventasSeed();
    Storage::fake('public');
    $item = ventasItem();
    $dir = "hp/uploads/c2/item/{$item->cd}";
    Storage::disk('public')->put("{$dir}/{$item->cd}.jpg", 'x');

    $this->get(route('admin.ventas.products.edit', ['cd' => $item->cd, 'name' => 'コ', 'page' => 2]))
        ->assertOk()
        ->assertSee('action="'.route('admin.ventas.products.update', $item->cd).'?name=', false)
        ->assertSee('href="'.route('admin.ventas.products.index').'?name=%E3%82%B3&amp;discription="', false);

    $this->post(route('admin.ventas.products.update', $item->cd).'?name=x', ventasForm([
        'act' => 'register', 'name' => '新しい名前', 'is_exchangeable_of_point' => '0', 'number_of_points_exchanged' => '',
        'available_shop_cds' => ['2'], 'hyouji_flg' => '0', 'img_delete' => '1',
    ]))->assertRedirect(route('admin.ventas.products.edit', $item->cd).'?name=x');

    $item->refresh();
    expect($item)
        ->name->toBe('新しい名前')
        ->hyouji_flg->toBe(0)
        ->is_exchangeable_of_point->toBe(0)
        ->number_of_points_exchanged->toBe(30) // como el legacy: sin canje no se toca
        ->and($item->availableShopCds())->toBe([2]);
    Storage::disk('public')->assertMissing("{$dir}/{$item->cd}.jpg");
});

it('borra con disp_flg = 0 desde ajax_list_delete.php y desde la ruta nueva', function () {
    ventasSeed();
    $a = ventasItem();
    $b = ventasItem(['name' => '二つ目']);

    $this->post('/system/category2/item/ajax_list_delete.php?cd='.$a->cd)
        ->assertOk()
        ->assertExactJson(['status' => 'success']);
    expect(DB::table('sales_items')->where('cd', $a->cd)->value('disp_flg'))->toBe(0);

    $this->deleteJson(route('admin.ventas.products.destroy', $b->cd))->assertExactJson(['status' => 'success']);
    expect(DB::table('sales_items')->where('cd', $b->cd)->value('disp_flg'))->toBe(0);

    // Ya borrado o inexistente: error (el legacy daba un error fatal)
    $this->post('/system/category2/item/ajax_list_delete.php?cd='.$a->cd)->assertExactJson(['status' => 'error']);
    $this->get(route('admin.ventas.products.edit', $a->cd))->assertNotFound();
    // El legacy borraba por GET: ahora solo POST
    // (sin el manejador de excepciones: la página de error 405 en modo debug tarda ~30 s)
    $this->withoutExceptionHandling();
    expect(fn () => $this->get('/system/category2/item/ajax_list_delete.php?cd='.$b->cd))
        ->toThrow(Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class);
});

it('redirige las URL del legacy a las pantallas nuevas', function () {
    ventasSeed();

    $this->get('/system/category2/')->assertRedirect(route('admin.ventas.home'));
    $this->get('/system/category2/index.php')->assertRedirect(route('admin.ventas.home'));
    $this->get('/system/category2/item/list.php?name=a')->assertRedirect(route('admin.ventas.products.index', ['name' => 'a']));
    $this->get('/system/category2/item/edit.php?cd=7&name=a')->assertRedirect(route('admin.ventas.products.edit', ['cd' => 7, 'name' => 'a']));
    $this->get('/system/category2/item/edit.php')->assertRedirect(route('admin.ventas.products.create'));
});

it('pide sesión', function () {
    $this->get(route('admin.ventas.products.index'))->assertRedirect();
    $this->post('/system/category2/item/ajax_list_delete.php?cd=1')->assertRedirect();
});
