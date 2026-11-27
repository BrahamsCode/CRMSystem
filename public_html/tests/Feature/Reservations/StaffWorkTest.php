<?php

require_once __DIR__ . '/helpers.php';

use Illuminate\Support\Facades\DB;

/*
 * スタッフ施術管理 (staff_works.php + ajax_menu.php).
 */

beforeEach(function () {
    reservasLogin();

    DB::table('tb_yoyaku_koumoku_category')->insert(['cd' => 1, 'name' => 'カット系', 'hyouji_flg' => '1', 'disp_flg' => '1', 'rank' => 1]);
    DB::table('tb_yoyaku_koumoku_master')->insert([
        ['cd' => 100, 'name' => 'カット', 'shoyou_time' => 60, 'hyouji_flg' => '1', 'disp_flg' => '1', 'naiyou' => "説明\n2行目", 'tb_shop_cd' => 1, 'type_flg' => '1', 'rank' => 1, 'tb_yoyaku_koumoku_category_cd' => 1],
        ['cd' => 101, 'name' => '全店トリートメント', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1', 'naiyou' => '', 'tb_shop_cd' => -1, 'type_flg' => '2', 'rank' => 1, 'tb_yoyaku_koumoku_category_cd' => null],
        ['cd' => 102, 'name' => '他店メニュー', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '1', 'naiyou' => '', 'tb_shop_cd' => 2, 'type_flg' => '1', 'rank' => 1, 'tb_yoyaku_koumoku_category_cd' => null],
        ['cd' => 103, 'name' => '削除済み', 'shoyou_time' => 30, 'hyouji_flg' => '1', 'disp_flg' => '9', 'naiyou' => '', 'tb_shop_cd' => 1, 'type_flg' => '1', 'rank' => 2, 'tb_yoyaku_koumoku_category_cd' => null],
    ]);
});

it('muestra la pantalla con el selector de スタッフ', function () {
    $this->get(route('admin.reservas.staff-works'))
        ->assertOk()
        ->assertSee('スタッフを選択')
        ->assertSee('店舗を選択して下さい')
        ->assertSee(route('admin.reservas.staff-works.menu'), false);
});

it('carga los menús asignables del empleado (ajax_menu.php)', function () {
    DB::table('tb_staff_works')->insert([
        'cd' => 1, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 100, 'shoyou_time' => 45,
        'hyouji_flg' => '1', 'disp_flg' => '1', 'mypage_flg' => '1',
    ]);
    DB::table('tb_staff')->where('cd', 10)->update(['reserve_flg' => '1']);

    $html = $this->post(route('admin.reservas.staff-works.menu'), ['shop_cd' => 1, 'staff_cd' => 10])
        ->assertOk()
        ->assertSee('指名許諾')
        ->assertSee('ベースメニュー')
        ->assertSee('オプションメニュー')
        ->assertSee('カット系')
        ->assertSee('(全店舗表示メニュー)')
        ->assertSee('説明<br />', false)
        ->assertDontSee('他店メニュー')
        ->assertDontSee('削除済み')
        ->getContent();

    // tiempo propio del empleado, registrado y 予約非表示 marcados, 指名許諾 = 受ける
    expect($html)->toContain('name="shoyou_time[100]" value="45"')
        ->toContain('name="yoyaku_menu_cd[100]" value="1" checked')
        ->toContain('name="mypage_flg[100]" value="1" checked')
        ->toContain('name="shoyou_time[101]" value="30"')
        ->toMatch('/value="1"\s+checked="checked"\s*\/>\s*受ける/');
});

it('sin menús responde メニューデータはありません', function () {
    DB::table('tb_yoyaku_koumoku_master')->where('cd', 101)->delete(); // el único de 全店舗
    $this->post(route('admin.reservas.staff-works.menu'), ['shop_cd' => 3, 'staff_cd' => 10])
        ->assertOk()
        ->assertSee('メニューデータはありません');
});

it('reescribe las filas del empleado y su 指名許諾 al guardar', function () {
    DB::table('tb_staff_works')->insert([
        ['cd' => 5, 'tb_staff_cd' => 10, 'tb_yoyaku_koumoku_master_cd' => 103, 'shoyou_time' => 10, 'hyouji_flg' => '1', 'disp_flg' => '1', 'mypage_flg' => ''],
        ['cd' => 7, 'tb_staff_cd' => 99, 'tb_yoyaku_koumoku_master_cd' => 100, 'shoyou_time' => 10, 'hyouji_flg' => '1', 'disp_flg' => '1', 'mypage_flg' => ''],
    ]);

    $this->post(route('admin.reservas.staff-works.update'), [
        'shop' => 1,
        'staff' => 10,
        'reserve_flg' => '1',
        'yoyaku_menu_cd' => [100 => '1', 101 => '1'],
        'shoyou_time' => [100 => '50', 101 => '', 102 => '20'],
        'mypage_flg' => [101 => '1'],
        'act' => 'reg',
        'enter' => '設定',
    ])->assertRedirect(route('admin.reservas.staff-works', ['shop' => 1, 'staff' => 10]))
        ->assertSessionHas('staffWorksSaved', true);

    $rows = DB::table('tb_staff_works')->where('tb_staff_cd', 10)->orderBy('cd')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('cd')->all())->toBe([8, 9]) // MAX(cd) + 1
        ->and($rows[0]->tb_yoyaku_koumoku_master_cd)->toBe(100)
        ->and($rows[0]->shoyou_time)->toBe(50)
        ->and($rows[0]->mypage_flg)->toBe('')
        ->and($rows[1]->shoyou_time)->toBe(0)
        ->and($rows[1]->mypage_flg)->toBe('1')
        ->and($rows[1]->disp_flg)->toBe('1');

    // las filas de otros empleados no se tocan
    expect(DB::table('tb_staff_works')->where('tb_staff_cd', 99)->count())->toBe(1);
    expect(DB::table('tb_staff')->where('cd', 10)->value('reserve_flg'))->toBe('1');

    // tras guardar: mensaje y recarga solo de otra ventana (sin bucle)
    $this->withSession(['staffWorksSaved' => true])
        ->get(route('admin.reservas.staff-works', ['shop' => 1, 'staff' => 10]))
        ->assertOk()
        ->assertSee('設定しました。')
        ->assertSee("setStaff('1', '10')", false)
        ->assertSee('window.parent !== window', false);
});

it('imprime 名前 y 内容 del menú (guardados con get_form2()) sin escapar dos veces', function () {
    DB::table('tb_yoyaku_koumoku_master')->where('cd', 100)->update([
        'name' => "&lt;b&gt;&quot;カット&quot;&lt;/b&gt; 'A'", 'naiyou' => "&lt;i&gt;説明&lt;/i&gt;\n2行目",
    ]);

    $this->post(route('admin.reservas.staff-works.menu'), ['shop_cd' => 1, 'staff_cd' => 10])
        ->assertOk()
        ->assertSee("<span style=\"font-weight:bold;\">&lt;b&gt;&quot;カット&quot;&lt;/b&gt; 'A'</span>", false)
        ->assertSee('&lt;i&gt;説明&lt;/i&gt;<br />', false)
        ->assertDontSee('&amp;lt;', false);
});
