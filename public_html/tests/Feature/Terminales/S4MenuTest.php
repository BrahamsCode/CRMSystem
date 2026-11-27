<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* Menú lateral de マイページ管理 (left_navi6) y 端末管理 (left_navi7) en config/menu */

function s4MenuChildren(): void
{
    DB::table('tb_base_system_child')->insert([
        ['cd' => 601, 'tb_base_system_cd' => 6, 'system_name' => 'マイページ管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 603, 'tb_base_system_cd' => 6, 'system_name' => '求人情報管理', 'system_flg' => '1', 'rank' => 3],
        ['cd' => 701, 'tb_base_system_cd' => 7, 'system_name' => '電子看板管理', 'system_flg' => '1', 'rank' => 1],
        ['cd' => 704, 'tb_base_system_cd' => 7, 'system_name' => 'ペーパーサイネージ', 'system_flg' => '1', 'rank' => 4],
    ]);
}

it('muestra el menú de マイページ管理 con マイコレ solo si la app está activa', function () {
    s4Login();
    s4MenuChildren();

    $this->get(route('admin.mypage.recruits.index'))
        ->assertOk()
        ->assertSee('/admin/mypage/recruits/create', false)
        ->assertSee('/admin/mypage/recommendations', false)
        ->assertSee('求人職種管理');

    // El Gate de la app: sin application_use_flg, un staff con todos los permisos (sin filas de tb_kengen) no ve マイコレ
    DB::table('tb_staff')->insert(['cd' => 2, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'otro', 'login_pass' => 'x', 'disp_flg' => '1']);
    DB::table('tb_kengen_contents')->insert(['cd' => 1035, 'hyouji_flg' => '1']); // 権限項目 de マイコレ
    $staff = \App\Models\Staff::find(2);
    expect(Gate::forUser($staff)->allows('mypage.mycolle.enabled'))->toBeFalse();
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 1]);
    expect(Gate::forUser($staff)->allows('mypage.mycolle.enabled'))->toBeTrue();
});

it('muestra el menú de 端末管理 con ペーパーサイネージ solo en la UI CRM2', function () {
    s4Login();
    s4MenuChildren();

    $this->get(route('admin.terminales.signage-programs.index'))
        ->assertOk()
        ->assertSee('/admin/terminales/signage-displays', false)
        ->assertSee('/admin/terminales/paper-cards', false); // seedNucleo usa la UI 2

    DB::table('tb_staff')->insert(['cd' => 2, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'otro', 'login_pass' => 'x', 'disp_flg' => '1']);
    $staff = \App\Models\Staff::find(2);
    DB::table('admin_master')->where('cd', 1)->update(['base_system_version_flg' => '1']);
    expect(Gate::forUser($staff)->allows('terminales.paper-signage.enabled'))->toBeFalse();
});
