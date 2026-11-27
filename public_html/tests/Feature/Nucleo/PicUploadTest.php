<?php

use App\Services\Nucleo\TempUploads;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__ . '/helpers.php';

// Subida temporal común: system/inc/ajax_picupload.php y system/uploads/

beforeEach(function () {
    Storage::fake('public');
    seedNucleo();
});

it('exige sesión del personal', function () {
    $this->post('/system/inc/ajax_picupload.php?up=true', [])->assertRedirect('/system/login.php');
    $this->get('/system/uploads/a.jpg')->assertRedirect('/system/login.php');
});

it('guarda la imagen y su miniatura de 240 px y las sirve en /system/uploads', function () {
    loginAs('master', 'secreto1');

    $this->post('/system/inc/ajax_picupload.php?up=true', [
        'file' => 'picfile_1', 'tempfilename' => 'abc123pic1', 'ext' => 'png',
        'picfile_1' => UploadedFile::fake()->image('foto.png', 800, 400),
    ])->assertOk()->assertContent('abc123pic1.jpg');

    Storage::disk('public')->assertExists(['system/uploads/abc123pic1.jpg', 'system/uploads/abc123pic1th.jpg']);
    expect(getimagesize(Storage::disk('public')->path('system/uploads/abc123pic1th.jpg'))[0])->toBe(240);

    $this->get('/system/inc/ajax_picupload.php?f=abc123pic1.jpg&h=80')->assertSee('<img height="80" src="/system/uploads/abc123pic1th.jpg?t=', false);
    $this->get('/system/uploads/abc123pic1.jpg')->assertOk();
    $this->get('/system/uploads/..%2F.env')->assertNotFound();
});

it('responde los errores del legacy y limpia los nombres', function () {
    loginAs('master', 'secreto1');

    $this->post('/system/inc/ajax_picupload.php?up=true', ['file' => 'picfile_1'])->assertSee('error:エラーが発生しました。', false);
    $this->post('/system/inc/ajax_picupload.php?up=true', ['file' => 'otro', 'tempfilename' => 'x', 'otro' => UploadedFile::fake()->image('a.jpg')])
        ->assertContent('error:' . TempUploads::UPLOAD_ERROR);
    // nombre con ../ y extensión php: se limpian (gif → .gif; resto → .jpg)
    $this->post('/system/inc/ajax_picupload.php?up=true', [
        'file' => 'picfile_2', 'tempfilename' => '../../evil', 'ext' => 'php', 'picfile_2' => UploadedFile::fake()->image('a.gif'),
    ])->assertContent('evil.jpg');
});

it('borra los temporales de más de un día y lo programa cada hora', function () {
    $disk = Storage::disk('public');
    $disk->put('system/uploads/viejo.jpg', 'x');
    $disk->put('system/uploads/nuevo.jpg', 'x');
    touch($disk->path('system/uploads/viejo.jpg'), time() - 2 * 86400);

    expect(app(TempUploads::class)->purge())->toBe(1);
    $disk->assertMissing('system/uploads/viejo.jpg');
    $disk->assertExists('system/uploads/nuevo.jpg');

    $events = collect(app(Schedule::class)->events())->map(fn ($e) => $e->description);
    expect($events)->toContain('nucleo:limpiar-subidas');
});
