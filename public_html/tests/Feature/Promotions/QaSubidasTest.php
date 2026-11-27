<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Nucleo/helpers.php';

/*
 * Repaso de subidas en :8081 (Doc/qa/regresion.md «Promoción (S3)»): un caso por fallo encontrado.
 */

it('フォロー y 自動 suben la imagen del envío a su carpeta (staff_entry.js: inc/upload.php relativo)', function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    Storage::fake('public');

    foreach (['follow', 'auto'] as $folder) {
        $this->post("/admin/promocion/{$folder}/inc/upload.php", ['userfile' => UploadedFile::fake()->image('a.jpg', 200, 100), 'ext' => 'jpg', 'time' => '1700000000000'])
            ->assertOk()->assertContent('success');
        Storage::disk('public')->assertExists("system/category3/{$folder}/uploads/temp/1700000000000.jpg");

        $this->get("/admin/promocion/{$folder}/inc/disp_pic.php?ext=jpg&t=1700000000000")
            ->assertOk()->assertSee("{$folder}/uploads/temp/1700000000000.jpg");
    }
});

it('新着情報: el enlace de la subida temporal apunta al disco public (/system/category3/… no tiene ruta)', function () {
    seedNucleo();
    loginAs('master', 'secreto1');
    Storage::fake('public');

    $this->post('/admin/promocion/digital_signage/inc/upload.php', ['userfile' => UploadedFile::fake()->image('a.png'), 'ext' => 'png', 'time' => '1764205200000'])
        ->assertContent('success');
    $this->get('/admin/promocion/digital_signage/inc/disp_pic.php?ext=png&t=1764205200000')
        ->assertSee(Storage::disk('public')->url('system/category3/digital_signage/uploads/temp/1764205200000.swf'), false);
});
