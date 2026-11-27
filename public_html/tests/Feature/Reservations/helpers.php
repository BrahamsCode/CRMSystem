<?php

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

/**
 * Pantallas de 予約管理 con el layout y el login reales del núcleo (S1): datos base de seedNucleo()
 * (tienda 1 「本店」, usuario master cd 1), empleado 10 「山田」 de la tienda 1, y sesión del master.
 */
function reservasLogin(): Staff
{
    $master = seedNucleo();
    DB::table('tb_staff')->insert(['cd' => 10, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'staff10', 'login_pass' => 'x', 'name' => '山田', 'disp_flg' => '1']);
    // Login real del núcleo (formulario de /system/login.php): deja la sesión como en producción
    loginAs('master', 'secreto1');

    return $master;
}
