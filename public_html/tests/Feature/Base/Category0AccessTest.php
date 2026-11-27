<?php

use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 基本情報管理 solo para master (el legacy lo ocultaba a los demás); el asistente de primer acceso queda abierto

beforeEach(function () {
    seedNucleo();
    DB::table('tb_staff')->insert([
        'cd' => 2, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'tantou', 'login_pass' => 'secreto2', 'name' => '担当', 'disp_flg' => '1',
    ]);
});

it('no deja entrar en category0 a quien no es master', function () {
    loginAs('tantou', 'secreto2');

    foreach (['/system/category0', '/system/category0/shop/shop_list.php', '/system/category0/config/authority.php',
        '/system/category0/config/config.php', '/system/category0/config/area.php', '/system/category0/config/bookmark.php'] as $url) {
        $this->get($url)->assertForbidden();
    }
    $this->post('/system/category0/config/ajax_group_authority_register.php', [])->assertForbidden();

    // los endpoints comunes del núcleo y el asistente de primer acceso siguen abiertos
    $this->get('/system/inc/zip.php?zip=1000001')->assertOk();
    $this->get('/system/config.php')->assertOk();
});

it('master entra en category0', function () {
    loginAs('master', 'secreto1');

    $this->get('/system/category0')->assertOk();
    $this->get('/system/category0/shop/shop_list.php')->assertOk();
});
