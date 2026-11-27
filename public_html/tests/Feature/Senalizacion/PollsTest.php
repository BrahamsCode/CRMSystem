<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/* Sondeos del reproductor: mismas respuestas (texto o <script>) que el legacy */

beforeEach(function () {
    DB::table('tb_c7_movie_program')->insert(['cd' => 30, 'name' => 'P', 'disp_conf' => '1', 'disp_flg' => '1', 'update_date' => '2000']);
    DB::table('tb_display')->insert(['cd' => 40, 'name' => 'D', 'tb_c7_movie_program_cds' => '30', 'disp_flg' => '1', 'update_date' => '3000']);
});

it('responde a ajax_network_check.php', function () {
    $this->post('/digitalsignage/ajax_network_check.php')->assertOk()->assertContent('確認できました');
});

it('recarga con ajax_xml_check_2.php cuando el programa es más nuevo', function () {
    $this->post('/digitalsignage/ajax_xml_check_2.php', ['xml_mod' => '1000', 'shop_cd' => 1, 'tb_c7_movie_program_cd' => 30, 'display_cd' => 40])
        ->assertContent('<script type="text/javascript">xmlMod = 2000;location.href = "./?shop_cd=1&display_cd=40";</script>');

    $this->post('/digitalsignage/ajax_xml_check_2.php', ['xml_mod' => '2000', 'shop_cd' => 1, 'display_cd' => 40])
        ->assertContent('');
});

it('recarga con ajax_xml_check.php según la fecha del XML <display_cd>.xml', function () {
    Storage::fake('public');
    Storage::disk('public')->put('hp/uploads/c7/display/40.xml', '<data/>');
    $mtime = Storage::disk('public')->lastModified('hp/uploads/c7/display/40.xml');

    $this->post('/digitalsignage/ajax_xml_check.php', ['xml_mod' => $mtime - 10, 'shop_cd' => '1', 'display_cd' => '40'])
        ->assertContent('<script type="text/javascript">xmlMod = '.$mtime.';location.href = "./?shop_cd=1&display_cd=40";</script>');

    $this->post('/digitalsignage/ajax_xml_check.php', ['xml_mod' => $mtime, 'display_cd' => '40'])->assertContent('');
});

it('recarga con ajax_display_config_check(_2).php cuando el display cambió', function () {
    foreach (['ajax_display_config_check.php', 'ajax_display_config_check_2.php'] as $url) {
        $this->post("/digitalsignage/{$url}", ['display_check_time' => '2500', 'shop_cd' => '1', 'display_cd' => '40'])
            ->assertContent('<script type="text/javascript">location.href = "./?shop_cd=1&display_cd=40";</script>');

        $this->post("/digitalsignage/{$url}", ['display_check_time' => '3000', 'shop_cd' => '1', 'display_cd' => '40'])
            ->assertContent('');
    }
});

it('escapa shop_cd y display_cd en la URL de recarga', function () {
    $this->post('/digitalsignage/ajax_display_config_check.php', ['display_check_time' => '1', 'shop_cd' => '"><b>', 'display_cd' => '40'])
        ->assertContent('<script type="text/javascript">location.href = "./?shop_cd=&quot;&gt;&lt;b&gt;&display_cd=40";</script>');
});
