<?php

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * Datos mínimos del legacy para el núcleo: empresa, configuración (UI v2), tienda, módulos y el usuario master.
 */
function seedNucleo(array $staff = []): Staff
{
    DB::table('tb_office')->insert(['cd' => 1, 'kaishamei' => 'テスト株式会社', 'hp_hyouji_name' => 'shop']);
    DB::table('admin_master')->insert(['cd' => 1, 'base_system_version_flg' => '2', 'seller_id' => 2]);
    DB::table('tb_shop')->insert(['cd' => 1, 'master_cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1']);
    DB::table('tb_base_system')->insert([
        ['cd' => 1, 'system_name' => '顧客管理', 'system_flg' => '1', 'narabi' => 1],
        ['cd' => 2, 'system_name' => '販売管理', 'system_flg' => '0', 'narabi' => 2],
        ['cd' => 3, 'system_name' => '販売促進管理', 'system_flg' => '1', 'narabi' => 3],
        ['cd' => 8, 'system_name' => '女の子管理', 'system_flg' => '1', 'narabi' => 10],
        ['cd' => 12, 'system_name' => 'SNS管理', 'system_flg' => '1', 'narabi' => 11],
        ['cd' => 0, 'system_name' => '基本情報管理', 'system_flg' => '1', 'narabi' => 12],
    ]);
    DB::table('hatuban')->insert(['koumoku' => 'tb_login_rireki', 'kazu' => 1]);
    DB::table('tb_staff')->insert($staff + [
        'cd' => 1, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'master', 'login_pass' => 'secreto1',
        'name' => '管理者', 'disp_flg' => '1',
    ]);

    return Staff::find($staff['cd'] ?? 1);
}

/** Inicia sesión como lo hace el formulario del legacy */
function loginAs(string $loginId, string $password)
{
    return test()->post('/system/login.php', ['admin_id' => $loginId, 'admin_pass' => $password, 'act' => 'login']);
}
