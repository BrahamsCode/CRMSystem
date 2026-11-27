<?php

require_once __DIR__ . '/helpers.php';

use App\Models\Reservations\ReservationMenuCategory;

// 予約項目カテゴリ管理 (legacy: yoyaku_koumoku_category.php, _register.php, ajax_yoyaku_koumoku_category.php)

beforeEach(function () {
    reservasLogin();
});

function menuCategoryRow(string $name, int $rank, string $hyouji = '1'): ReservationMenuCategory
{
    return ReservationMenuCategory::create(['name' => $name, 'rank' => $rank, 'disp_flg' => '1', 'hyouji_flg' => $hyouji]);
}

it('carga la pantalla y la lista vacía', function () {
    $this->get(route('admin.reservas.menu-categories.index'))
        ->assertOk()
        ->assertSee('■予約項目カテゴリ管理')
        ->assertSee('新規登録');

    $this->post(route('admin.reservas.menu-categories.list'))
        ->assertOk()
        ->assertSee('登録カテゴリ一覧')
        ->assertSee('データがありません。');
});

it('da de alta al final del orden y edita el nombre', function () {
    menuCategoryRow('既存', 1);

    $this->post(route('admin.reservas.menu-categories.store'), ['name' => 'カラー'])
        ->assertRedirect(route('admin.reservas.menu-categories.index'));

    $category = ReservationMenuCategory::where('name', 'カラー')->firstOrFail();
    expect($category->rank)->toBe(2)->and($category->hyouji_flg)->toBe('1')->and($category->disp_flg)->toBe('1');

    $this->get(route('admin.reservas.menu-categories.index', ['cd' => $category->cd]))->assertSee('編集');

    $this->put(route('admin.reservas.menu-categories.update', $category->cd), ['name' => 'カラー系'])->assertRedirect();
    expect($category->fresh()->name)->toBe('カラー系')->and($category->fresh()->rank)->toBe(2);
});

it('no guarda sin nombre', function () {
    $this->post(route('admin.reservas.menu-categories.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => '入力がありません。']);

    expect(ReservationMenuCategory::count())->toBe(0);
});

it('ordena, cambia 表示 y borra desde la lista', function () {
    $a = menuCategoryRow('A', 1);
    $b = menuCategoryRow('B', 2);
    $c = menuCategoryRow('C', 3);

    $this->post(route('admin.reservas.menu-categories.sort', $c->cd), ['send_type' => 'rankup'])
        ->assertOk()
        ->assertSeeInOrder(['<td>A</td>', '<td>C</td>', '<td>B</td>'], false);

    $this->post(route('admin.reservas.menu-categories.toggle', $a->cd))->assertOk();
    expect($a->fresh()->hyouji_flg)->toBe('9');

    $this->delete(route('admin.reservas.menu-categories.destroy', $a->cd))
        ->assertOk()
        ->assertDontSee('<td>A</td>', false);

    expect(ReservationMenuCategory::withDeleted()->find($a->cd)->disp_flg)->toBe('9')
        ->and($c->fresh()->rank)->toBe(1)
        ->and($b->fresh()->rank)->toBe(2);
});
