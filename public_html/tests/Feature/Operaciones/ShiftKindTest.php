<?php

use App\Models\Operaciones\StaffShiftKind;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// シフト区分管理 (sección de kintai/config.php + staff_shift_kind_register.php)

it('シフト区分管理: alta al final con color, edición, ▲▼ y baja; solo master', function () {
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 53, 'tb_base_system_cd' => 5, 'system_name' => 'スケジュール管理', 'system_flg' => '1', 'rank' => 3]);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/shift-kinds')
        ->assertOk()
        ->assertSee('■シフト区分管理 登録')
        ->assertSee('/system/category5/kintai/js/colorpicker.js', false)
        ->assertSee('シフト区分管理は登録されていません。');

    $this->post('/admin/operaciones/shift-kinds', ['name' => ' ﾊﾔﾊﾞﾝ ', 'color' => '#ff0000'])->assertRedirect('/admin/operaciones/shift-kinds');
    $this->post('/admin/operaciones/shift-kinds', ['name' => '遅番', 'color' => '']);

    $early = StaffShiftKind::where('rank', 1)->firstOrFail();
    expect($early->name)->toBe('ハヤバン')
        ->and($early->color)->toBe('#ff0000')
        ->and($early->disp_flg)->toBe('1');

    $this->get('/admin/operaciones/shift-kinds')
        ->assertSee('background-color: #ff0000;border', false)
        ->assertSee('<td>遅番</td>', false);

    $this->put("/admin/operaciones/shift-kinds/{$early->cd}", ['name' => '早番', 'color' => '#00ff00']);
    $this->get('/admin/operaciones/shift-kinds/sort?thisCd='.$early->cd.'&kind=down');
    expect(StaffShiftKind::ordered()->pluck('name')->all())->toBe(['遅番', '早番']);

    $this->delete("/admin/operaciones/shift-kinds/{$early->cd}");
    expect(StaffShiftKind::pluck('name')->all())->toBe(['遅番'])
        ->and(DB::table('tb_staff_shift_kind')->where('cd', $early->cd)->value('disp_flg'))->toBe('9');

    staffUser();
    auth('admin')->logout();
    $this->flushSession();
    loginAs('u2', 'p');
    $this->get('/admin/operaciones/shift-kinds')->assertForbidden();
});
