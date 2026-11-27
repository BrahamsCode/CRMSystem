<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Nucleo/helpers.php';

/** Núcleo + 業務支援管理 (category5) con su bloque スタッフ管理 activo */
function seedOperaciones(): void
{
    seedNucleo();
    DB::table('tb_base_system')->insert(['cd' => 5, 'system_name' => '業務支援管理', 'system_flg' => '1', 'narabi' => 5]);
    DB::table('tb_base_system_child')->insert(['cd' => 50, 'tb_base_system_cd' => 5, 'system_name' => 'スタッフ管理', 'system_flg' => '1', 'rank' => 1]);
    // Contenidos de permisos de スタッフ管理 e インフォメーション (sin filas en tb_kengen: todo permitido)
    DB::table('tb_kengen_contents')->insert([['cd' => 1024, 'hyouji_flg' => '1'], ['cd' => 1025, 'hyouji_flg' => '1']]);
}

/** Empleado que no es master (sin filas en tb_kengen: todos los permisos, como el legacy) */
function staffUser(int $cd = 2, array $data = []): void
{
    DB::table('tb_staff')->insert($data + ['cd' => $cd, 'shop_cd' => 1, 'login_id' => "u{$cd}", 'login_pass' => 'p', 'name' => '一般', 'disp_flg' => '1']);
}
