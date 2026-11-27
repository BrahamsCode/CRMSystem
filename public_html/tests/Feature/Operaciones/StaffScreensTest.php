<?php

use App\Models\Operaciones\Information;
use App\Models\Operaciones\StaffGroup;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// Pantallas de スタッフ管理: スタッフグループ設定, スタッフ検索項目設定, インフォメーション管理

// --- スタッフグループ設定 ---

it('スタッフグループ設定: carga con sesión de master, con el HTML del legacy y el menú de category5', function () {
    seedOperaciones();
    StaffGroup::create(['name' => 'ホール', 'rank' => 1, 'disp_flg' => '1']);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/staff-groups')
        ->assertOk()
        ->assertSee('<title>スタッフグループ管理 - shop</title>', false)
        ->assertSee('■スタッフグループ 登録')
        ->assertSee('1件</span>の該当結果を表示しています。', false)
        ->assertSee('<td>ホール</td>', false)
        ->assertSee('<p class="loginName5">', false)
        ->assertSee('スタッフ検索項目設定');
});

it('スタッフグループ設定: solo para el master (como el menú del legacy)', function () {
    seedOperaciones();
    staffUser();
    loginAs('u2', 'p');

    $this->get('/admin/operaciones/staff-groups')->assertForbidden();
});

it('スタッフグループ設定: alta al final, edición, ▲▼ y baja', function () {
    seedOperaciones();
    loginAs('master', 'secreto1');

    $this->post('/admin/operaciones/staff-groups', ['name' => ' ﾎｰﾙ '])->assertRedirect('/admin/operaciones/staff-groups');
    $this->post('/admin/operaciones/staff-groups', ['name' => 'キッチン']);

    $hall = StaffGroup::where('rank', 1)->firstOrFail();
    expect($hall->name)->toBe('ホール') // form2Db: kana de ancho medio a completo y recorte
        ->and(StaffGroup::where('rank', 2)->value('name'))->toBe('キッチン');

    $this->put("/admin/operaciones/staff-groups/{$hall->cd}", ['name' => 'ホールA']);
    $this->get('/admin/operaciones/staff-groups/sort?thisCd='.$hall->cd.'&kind=down')->assertRedirect();

    expect(StaffGroup::ordered()->pluck('name')->all())->toBe(['キッチン', 'ホールA']);

    $this->delete("/admin/operaciones/staff-groups/{$hall->cd}");

    expect(StaffGroup::pluck('name')->all())->toBe(['キッチン'])
        ->and(DB::table('tb_staff_group')->where('cd', $hall->cd)->value('disp_flg'))->toBe('9');
});

it('スタッフグループ設定: el nombre es obligatorio', function () {
    seedOperaciones();
    loginAs('master', 'secreto1');

    $this->post('/admin/operaciones/staff-groups', ['name' => '　'])->assertSessionHasErrors('name');

    expect(StaffGroup::count())->toBe(0);
});

// --- スタッフ検索項目設定 ---

it('スタッフ検索項目設定: pinta las filas de tb_staff y guarda 検索 y CSV', function () {
    seedOperaciones();
    DB::table('tb_koumoku')->insert([
        ['cd' => 43, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'id', 'tb_koumoku_name' => 'スタッフID', 'mail_mag_select' => '0', 'koumoku_select' => '1'],
        ['cd' => 44, 'tb_name' => 'tb_staff', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'mail_mag_select' => '1', 'koumoku_select' => '0'],
        ['cd' => 1, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'mail_mag_select' => '1', 'koumoku_select' => '1'],
    ]);
    loginAs('master', 'secreto1');

    $html = $this->get('/admin/operaciones/staff-fields')
        ->assertOk()
        ->assertSee('■スタッフ検索項目設定')
        ->assertSee('<input type="hidden" name="item_max_no" value="2">', false)
        ->getContent();

    // スタッフID: la casilla de búsqueda refleja mail_mag_select (el legacy pintaba koumoku_select)
    expect($html)->toContain('name="item_cd3_1" id="item_cd3_1" value="1" />')
        ->and($html)->toContain('name="item_cd3_2" id="item_cd3_2" value="1" checked="checked" />');

    $this->put('/admin/operaciones/staff-fields', [
        'item_max_no' => 2,
        'item_cd1' => 43, 'item_cd3_1' => '1', 'item_cd4_1' => '1',
        'item_cd2' => 44, 'item_cd4_2' => '1',
    ])->assertRedirect('/admin/operaciones/staff-fields');

    expect(DB::table('tb_koumoku')->where('cd', 43)->value('mail_mag_select'))->toBe('1')
        ->and(DB::table('tb_koumoku')->where('cd', 44)->value('mail_mag_select'))->toBe('')
        ->and(DB::table('tb_koumoku')->where('cd', 44)->value('koumoku_select'))->toBe('1')
        ->and(DB::table('tb_koumoku')->where('cd', 1)->value('mail_mag_select'))->toBe('1'); // la de clientes no se toca
});

// --- インフォメーション管理 ---

it('インフォメーション管理: alta y edición con get_form2, lista y baja', function () {
    seedOperaciones();
    DB::table('tb_officialposition')->insert([['cd' => 1, 'officialposition' => '店長'], ['cd' => 2, 'officialposition' => 'チーフ']]);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/information')
        ->assertOk()
        ->assertSee('■インフォメーション登録')
        ->assertSee('インフォメーションは登録されていません。');

    $this->post('/admin/operaciones/information', [
        'shop_cd' => '', 'comment' => ' 15時 <b>集合</b> ', 'staff_name' => '管理者',
        'officialposition' => ['店長', 'チーフ'], 'employmentsystem' => '', 'belongpost' => '',
    ])->assertRedirect('/admin/operaciones/information');

    $info = Information::firstOrFail();
    expect($info->comment)->toBe('15時 &lt;b&gt;集合&lt;/b&gt;')
        ->and($info->shop_cd)->toBeNull()
        ->and($info->officialposition)->toBe('店長 チーフ');

    $this->get('/admin/operaciones/information')
        ->assertSee('<td>15時 &lt;b&gt;集合&lt;/b&gt;</td>', false)
        ->assertSee('全店舗');

    $this->put("/admin/operaciones/information/{$info->cd}", ['shop_cd' => '1', 'comment' => '変更']);
    expect($info->fresh()->comment)->toBe('変更')->and($info->fresh()->shop_cd)->toBe(1);

    $this->delete("/admin/operaciones/information/{$info->cd}");
    expect(Information::count())->toBe(0);
});

it('インフォメーション管理: quien no es master solo ve los de su tienda y los de 全店舗', function () {
    seedOperaciones();
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '支店', 'disp_flg' => '1']);
    staffUser();
    Information::create(['comment' => '全店舗あて', 'disp_flg' => '1']);
    Information::create(['shop_cd' => 1, 'comment' => '本店あて', 'disp_flg' => '1']);
    Information::create(['shop_cd' => 2, 'comment' => '支店あて', 'disp_flg' => '1']);
    loginAs('u2', 'p');

    $this->get('/admin/operaciones/information')
        ->assertOk()
        ->assertSee('全店舗あて')
        ->assertSee('本店あて')
        ->assertDontSee('支店あて')
        ->assertSee('※全店舗配信の同報は管理者から配信されたものなので、修正・削除は管理者のみが行えます。');
});
