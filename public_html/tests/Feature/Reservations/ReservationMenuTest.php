<?php

use App\Models\Reservations\Facility;
use App\Models\Reservations\ReservationMenu;
use App\Models\Reservations\ReservationMenuCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// 予約項目管理 (legacy: yoyaku_koumoku_new.php, yoyaku_koumoku_register.php, ajax_yoyaku_koumoku_new.php)

beforeEach(function () {
    \Tests\Feature\Reservations\ScreenSetup::boot();
});

function menuFormData(array $overrides = []): array
{
    return array_merge([
        'tb_shop_cd' => 1, 'type_flg' => '1', 'name' => 'カット', 'naiyou' => "シャンプー込み\n仕上げ",
        'shoyou_time' => '60', 'tb_yoyaku_koumoku_category_cd' => '', 'menu_end_time_flg' => '',
        'menu_end_time1' => '', 'menu_end_time2' => '',
    ], $overrides);
}

function menuRow(array $attributes): ReservationMenu
{
    return ReservationMenu::create(array_merge(['tb_shop_cd' => 1, 'shoyou_time' => 30, 'type_flg' => '1', 'disp_flg' => '1'], $attributes));
}

it('carga la pantalla con el formulario de la tienda', function () {
    $this->get(route('admin.reservas.menus.index', ['shop' => 1]))
        ->assertOk()
        ->assertSee('予約項目登録情報')
        ->assertSee('最終受付時間')
        ->assertSee('この内容で登録する')
        ->assertSee('予約項目カテゴリ設定');
});

it('muestra 設備設定 solo si admin_master.yoyaku_setsubi_flg = 1', function () {
    $this->get(route('admin.reservas.menus.index', ['shop' => 1]))->assertDontSee('設備設定');

    DB::table('admin_master')->insert(['cd' => 1, 'yoyaku_setsubi_flg' => '1']);
    Facility::create(['tb_shop_cd' => 1, 'name' => 'ベッドA', 'quantity' => 2, 'disp_flg' => '1']);

    $this->get(route('admin.reservas.menus.index', ['shop' => 1]))
        ->assertOk()
        ->assertSee('設備設定')
        ->assertSee('ベッドA');
});

it('da de alta un menú con hatuban y 最終受付時間', function () {
    $category = ReservationMenuCategory::create(['name' => 'カット系', 'rank' => 1, 'disp_flg' => '1', 'hyouji_flg' => '1']);

    $this->post(route('admin.reservas.menus.store'), menuFormData([
        'tb_yoyaku_koumoku_category_cd' => (string) $category->cd,
        'menu_end_time_flg' => '1', 'menu_end_time1' => '25', 'menu_end_time2' => '30',
    ]))->assertRedirect(route('admin.reservas.menus.index', ['shop' => 1]));

    $menu = ReservationMenu::firstOrFail();
    expect($menu->name)->toBe('カット')
        ->and($menu->shoyou_time)->toBe(60)
        ->and($menu->type_flg)->toBe('1')
        ->and($menu->tb_yoyaku_koumoku_category_cd)->toBe($category->cd)
        ->and($menu->menu_end_time_flg)->toBe('1')
        ->and($menu->menu_end_time)->toBe(25 * 3600 + 30 * 60)
        ->and($menu->hyouji_flg)->toBe('1')
        ->and($menu->disp_flg)->toBe('1')
        ->and($menu->touroku_date)->not->toBeEmpty();

    // Edición: los selectores muestran 25:30
    $this->get(route('admin.reservas.menus.index', ['cd' => $menu->cd]))
        ->assertOk()
        ->assertSee('この内容で修正する')
        ->assertSee('<option value="25" selected="selected">25</option>', false);
});

it('rechaza el alta sin nombre ni 所要時間 con los textos del legacy', function () {
    $this->from(route('admin.reservas.menus.index', ['shop' => 1]))
        ->post(route('admin.reservas.menus.store'), menuFormData(['name' => '', 'shoyou_time' => '']))
        ->assertSessionHasErrors(['name' => '「名称」が未入力です。', 'shoyou_time' => '「所要時間」が未入力です。']);

    expect(ReservationMenu::count())->toBe(0);
});

it('edita un menú y lo pasa a オプション sin tocar el 最終受付時間', function () {
    $menu = menuRow(['name' => '旧', 'rank' => 1, 'menu_end_time_flg' => '1', 'menu_end_time' => 3600]);
    menuRow(['name' => '別', 'rank' => 2]);

    $this->put(route('admin.reservas.menus.update', $menu->cd), menuFormData(['name' => '新', 'type_flg' => '2', 'shoyou_time' => '45']))
        ->assertRedirect();

    $menu->refresh();
    expect($menu->name)->toBe('新')
        ->and($menu->type_flg)->toBe('2')
        ->and($menu->shoyou_time)->toBe(45)
        ->and($menu->rank)->toBe(3) // cambió el tipo: MAX(rank) de la tienda + 1
        ->and($menu->menu_end_time_flg)->toBe('1')
        ->and($menu->menu_end_time)->toBe(3600);
});

it('borra con disp_flg 9 y cierra el hueco del rank', function () {
    $a = menuRow(['name' => 'A', 'rank' => 1]);
    $b = menuRow(['name' => 'B', 'rank' => 2]);

    $this->delete(route('admin.reservas.menus.destroy', $a->cd))
        ->assertRedirect(route('admin.reservas.menus.index', ['shop' => 1]));

    $deleted = ReservationMenu::withDeleted()->find($a->cd);
    expect($deleted->disp_flg)->toBe('9')
        ->and($deleted->rank)->toBeNull()
        ->and($b->fresh()->rank)->toBe(1);
});

it('lista por tipo y categoría, con los menús 全店舗, y ordena con ▲▼', function () {
    $category = ReservationMenuCategory::create(['name' => 'ヘッドスパ', 'rank' => 1, 'disp_flg' => '1', 'hyouji_flg' => '9']);
    $a = menuRow(['name' => 'スパA', 'tb_yoyaku_koumoku_category_cd' => $category->cd, 'rank' => 1]);
    $b = menuRow(['name' => 'スパB', 'tb_yoyaku_koumoku_category_cd' => $category->cd, 'rank' => 2]);
    menuRow(['tb_shop_cd' => -1, 'name' => '全店トリートメント', 'type_flg' => '2', 'rank' => 1]);

    $this->post(route('admin.reservas.menus.list'), ['shop_cd' => 1])
        ->assertOk()
        ->assertSee('ベースメニュー登録情報一覧')
        ->assertSee('オプションメニュー登録情報一覧')
        ->assertSee('ヘッドスパ')
        ->assertSee('※非表示')
        ->assertSee('カテゴリ設定無し')
        ->assertSee('全店共通項目')
        ->assertSeeInOrder(['スパA', 'スパB', '全店トリートメント']);

    $this->post(route('admin.reservas.menus.sort'), ['shop_cd' => 1, 'send_type' => 'rankup', 'send_targetCd' => $b->cd, 'send_vl' => 2])
        ->assertOk()
        ->assertSeeInOrder(['スパB', 'スパA']);

    expect($b->fresh()->rank)->toBe(1)->and($a->fresh()->rank)->toBe(2);
});

it('renumera los rank vacíos al cargar la lista, como el legacy', function () {
    $a = menuRow(['name' => 'A', 'rank' => 5]);
    $b = menuRow(['name' => 'B', 'rank' => null]);

    $this->post(route('admin.reservas.menus.list'), ['shop_cd' => 1])->assertOk();

    expect($a->fresh()->rank)->toBe(1)->and($b->fresh()->rank)->toBe(2);
});

it('guarda las miniaturas de la imagen y las borra con 画像を削除', function () {
    Storage::fake('public');

    $this->post(route('admin.reservas.menus.store'), menuFormData(['img' => UploadedFile::fake()->image('menu.jpg', 400, 300)]))
        ->assertRedirect();
    $cd = ReservationMenu::firstOrFail()->cd;
    $dir = "hp/uploads/c9/yoyaku_koumoku/{$cd}";

    Storage::disk('public')->assertExists(["{$dir}/1_moto.jpg", "{$dir}/1_m.jpg", "{$dir}/1_s.jpg"]);
    expect(getimagesizefromstring(Storage::disk('public')->get("{$dir}/1_s.jpg"))[0])->toBe(90);

    $this->get(route('admin.reservas.menus.index', ['cd' => $cd]))->assertSee('画像を削除');

    $this->put(route('admin.reservas.menus.update', $cd), menuFormData(['img_del_flg' => '1']))->assertRedirect();
    Storage::disk('public')->assertMissing(["{$dir}/1_m.jpg", "{$dir}/1_s.jpg"]);
});
