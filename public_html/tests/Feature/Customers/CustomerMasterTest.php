<?php

require_once __DIR__ . '/../Reservations/helpers.php';

use App\Models\Customers\CustomerLevel;
use App\Models\Customers\VisitMotive;
use App\Models\Promotions\CustomerGroup;
use Illuminate\Support\Facades\DB;

// 顧客グループ設定 (customer_group_category_*), 初回来店動機項目設定 (raiten_douki*) y 顧客レベル (customer_level_category_*)

beforeEach(function () {
    reservasLogin();
});

it('carga 顧客グループ desde el menú del legacy con formulario y lista vacía', function () {
    $this->get('/system/category1/customer/customer_group_category_new.php')
        ->assertOk()
        ->assertSee('顧客グループ管理 - ', false)
        ->assertSee('■顧客グループ 登録')
        ->assertSee('顧客グループ初期値')
        ->assertSee('この値を新規顧客の初期値として使用する')
        ->assertSee('この内容で登録する')
        ->assertSee('■顧客グループ一覧')
        ->assertSee('顧客グループは登録されていません。');
});

it('da de alta grupos con cd createHatsuban, rank consecutivo y get_form() en el nombre', function () {
    $this->post(route('admin.clientes.groups.store'), ['name' => ' 会員"A\'s ', 'act' => 'new'])
        ->assertRedirect(route('admin.clientes.groups'));
    $this->post(route('admin.clientes.groups.store'), ['name' => 'VIP', 'act' => 'new']);

    $groups = CustomerGroup::query()->orderBy('rank')->get();
    expect($groups)->toHaveCount(2)
        ->and($groups[0]->name)->toBe('会員A’s')
        ->and($groups[0]->rank)->toBe(1)
        ->and($groups[1]->rank)->toBe(2)
        ->and($groups[0]->disp_flg)->toBe('1')
        ->and($groups[0]->default_flg)->toBe('')
        ->and($groups[0]->synchronize_flg)->toBe('')
        ->and($groups[0]->touroku_date)->not->toBeEmpty()
        // createHatsuban(): epoch (10) + tienda (3) + aleatorio (6)
        ->and(strlen((string) $groups[0]->cd))->toBe(19)
        ->and(substr((string) $groups[0]->cd, 10, 3))->toBe('001');

    $this->get(route('admin.clientes.groups'))
        ->assertOk()
        ->assertSee('2件の該当結果を表示しています。')
        ->assertSee('会員A’s')
        ->assertSee('▲');
});

it('default_flg: solo un grupo es el 初期値 y se edita con ?cd=', function () {
    $a = CustomerGroup::create(['name' => 'A', 'rank' => 1, 'disp_flg' => '1', 'default_flg' => '1']);
    $b = CustomerGroup::create(['name' => 'B', 'rank' => 2, 'disp_flg' => '1', 'default_flg' => '']);

    $this->get(route('admin.clientes.groups', ['cd' => $b->cd]))
        ->assertOk()
        ->assertSee('この内容で修正する')
        ->assertSee('value="B"', false);

    $this->put(route('admin.clientes.groups.update', $b->cd), ['name' => 'B2', 'default_flg' => '1', 'act' => 'up'])
        ->assertRedirect(route('admin.clientes.groups'));

    expect($a->fresh()->default_flg)->toBe('')
        ->and($b->fresh()->default_flg)->toBe('1')
        ->and($b->fresh()->name)->toBe('B2')
        ->and($b->fresh()->rank)->toBe(2);

    $this->get(route('admin.clientes.groups'))->assertSeeInOrder(['B2', '選択中']);
});

it('no toca los campos de puntos del grupo al editar', function () {
    $g = CustomerGroup::create(['name' => 'A', 'rank' => 1, 'disp_flg' => '1', 'keisan_flg' => '2', 'shiyou_point_jyougen' => 500]);

    $this->put(route('admin.clientes.groups.update', $g->cd), ['name' => 'A2']);

    expect($g->fresh()->keisan_flg)->toBe('2')->and($g->fresh()->shiyou_point_jyougen)->toBe(500);
});

it('borra con disp_flg = 9 y ▲ intercambia y renumera', function () {
    $a = CustomerGroup::create(['name' => 'A', 'rank' => 1, 'disp_flg' => '1']);
    $b = CustomerGroup::create(['name' => 'B', 'rank' => 3, 'disp_flg' => '1']);
    $c = CustomerGroup::create(['name' => 'C', 'rank' => 5, 'disp_flg' => '1']);

    $this->post(route('admin.clientes.groups.sort', ['cd' => $c->cd, 'kind' => 'up']))
        ->assertRedirect(route('admin.clientes.groups'));
    expect([$a->fresh()->rank, $c->fresh()->rank, $b->fresh()->rank])->toBe([1, 2, 3]);

    $this->delete(route('admin.clientes.groups.destroy', $b->cd))->assertRedirect(route('admin.clientes.groups'));
    expect(DB::table('tb_customer_group')->where('cd', $b->cd)->value('disp_flg'))->toBe('9');

    $this->get(route('admin.clientes.groups'))
        ->assertSee('2件の該当結果を表示しています。')
        ->assertSeeInOrder(['>A<', '>C<'], false)
        ->assertDontSee('>B<', false);
});

it('初回来店動機: carga desde el menú, alta y borrado', function () {
    $this->get('/system/category1/customer/raiten_douki.php')
        ->assertOk()
        ->assertSee('来店動機管理 - ', false)
        ->assertSee('■来店動機 登録')
        ->assertDontSee('初期値')
        ->assertSee('来店動機は登録されていません。');

    $this->post(route('admin.clientes.visit-motives.store'), ['name' => 'チラシ'])
        ->assertRedirect(route('admin.clientes.visit-motives'));

    $motive = VisitMotive::query()->firstOrFail();
    expect($motive->name)->toBe('チラシ')->and($motive->rank)->toBe(1)->and($motive->disp_flg)->toBe('1');

    $this->get(route('admin.clientes.visit-motives'))->assertSee('1件の該当結果を表示しています。')->assertSee('チラシ');

    $this->delete(route('admin.clientes.visit-motives.destroy', $motive->cd));
    expect(VisitMotive::withDeleted()->find($motive->cd)->disp_flg)->toBe('9');
});

it('顧客レベル: numera con hatuban y usa los botones de imagen', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_customer_level', 'kazu' => 7]);

    $this->get('/system/category1/customer/customer_level_category_new.php')
        ->assertOk()
        ->assertSee('■顧客レベル 登録')
        ->assertSee('顧客レベルは登録されていません。');

    $this->post(route('admin.clientes.levels.store'), ['name' => 'ゴールド'])->assertRedirect(route('admin.clientes.levels'));

    $level = CustomerLevel::query()->firstOrFail();
    expect($level->cd)->toBe(7)->and($level->rank)->toBe(1)
        ->and(DB::table('hatuban')->where('koumoku', 'tb_customer_level')->value('kazu'))->toBe(8);

    $this->get(route('admin.clientes.levels'))
        ->assertSee('btn_revise_off.gif', false)
        ->assertSee('1件の該当結果を表示しています。');
});

it('exige el nombre', function () {
    $this->post(route('admin.clientes.groups.store'), ['name' => ''])->assertSessionHasErrors('name');
    expect(CustomerGroup::query()->count())->toBe(0);
});
