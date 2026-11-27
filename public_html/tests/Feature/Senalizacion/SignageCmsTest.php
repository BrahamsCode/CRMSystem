<?php

use App\Models\Senalizacion\SignageWebContent;
use App\Models\Senalizacion\SignageWebContentCategory;
use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebFile;
use App\Models\Senalizacion\SignageWebMovieContent;
use App\Models\Senalizacion\SignageWebMovieContentCategory;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    snSignageDatabase();
    s4Login();
});

afterEach(fn () => Carbon::setTestNow());

it('da de alta, cambia y borra un terminal', function () {
    $this->post(route('admin.senalizacion.terminals.store'), [
        'insert' => 1, 'flg' => 0, 'name' => '入口', 'packages_cd' => '7',
        'display_direction' => '横', 'display_number' => '2', 'display_type' => 'h_2_1',
    ])->assertRedirect(route('admin.senalizacion.terminals.index'));

    $terminal = SignageWebTerminal::sole();
    expect($terminal->only(['name', 'flg', 'packages_cd', 'display_direction', 'display_number', 'display_type']))
        ->toBe(['name' => '入口', 'flg' => 1, 'packages_cd' => 7, 'display_direction' => '横', 'display_number' => 2, 'display_type' => 'h_2_1'])
        ->and($terminal->insert_date)->not->toBeNull();

    $this->followingRedirects()->post(route('admin.senalizacion.terminals.update', $terminal->cd), [
        'update' => 1, 'cd' => $terminal->cd, 'flg' => 0, 'name' => '出口', 'packages_cd' => '8',
        'display_direction' => '縦', 'display_number' => '1', 'display_type' => '../x',
    ])->assertOk()->assertSee('登録が完了しました。')->assertSee('value="出口"', false);

    expect($terminal->fresh()->only(['name', 'flg', 'packages_cd', 'display_direction', 'display_type']))
        ->toBe(['name' => '出口', 'flg' => 0, 'packages_cd' => 8, 'display_direction' => '縦', 'display_type' => null]);

    $this->followingRedirects()->post(route('admin.senalizacion.terminals.destroy', $terminal->cd))
        ->assertOk()->assertSee('削除が完了しました。');
    expect(SignageWebTerminal::count())->toBe(0);
});

it('crea un paquete con un ciclo por サイクル数 y los ajusta al cambiarlo', function () {
    $this->post(route('admin.senalizacion.packages.store'), [
        'insert' => 1, 'flg' => 1, 'name' => 'ロビー', 'cycle' => '3',
        'display_direction' => '横', 'display_number' => '1', 'display_type' => 'h_1_1',
    ])->assertRedirect(route('admin.senalizacion.packages.index'));

    $package = SignageWebPackage::sole();
    expect($package->cycles()->where('flg', 1)->count())->toBe(3)
        ->and($package->cycles()->first()->only(['status', 'flame_cd', 'schedule_repeat_type']))
        ->toBe(['status' => 1, 'flame_cd' => 1, 'schedule_repeat_type' => 1]);

    $form = fn (int $cycle, int $above) => [
        'update' => 1, 'cd' => $package->cd, 'flg' => 1, 'name' => 'ロビー', 'cycle' => $cycle, 'above_cycle' => $above,
        'display_direction' => '横', 'display_number' => '1', 'display_type' => 'h_1_1',
    ];

    // Baja a 1: los dos últimos quedan con flg = 0 (no se borran)
    $this->post(route('admin.senalizacion.packages.update', $package->cd), $form(1, 3))->assertRedirect();
    $cds = $package->cycles()->orderBy('cd')->pluck('flg', 'cd')->all();
    expect(array_values($cds))->toBe([1, 0, 0]);

    // Sube a 4: reactiva los dos apagados (desde el menor) y crea uno nuevo
    $this->post(route('admin.senalizacion.packages.update', $package->cd), $form(4, 1))->assertRedirect();
    expect($package->cycles()->count())->toBe(4)
        ->and($package->cycles()->where('flg', 1)->count())->toBe(4)
        ->and($package->fresh()->cycle)->toBe(4);

    $this->post(route('admin.senalizacion.packages.destroy', $package->cd))->assertRedirect(route('admin.senalizacion.packages.index'));
    expect(SignageWebPackage::count())->toBe(0)
        ->and(SignageWebCycle::count())->toBe(4);
});

it('guarda un ciclo: estado, marco, imágenes y efectos con serialize()', function () {
    $f = snSignageFixture();
    $cycle = $f['cycles'][0];

    $this->post(route('admin.senalizacion.cycles.update', [$f['package']->cd, $cycle->cd]), [
        'update' => 1, 'cd' => $cycle->cd, 'packages_cd' => $f['package']->cd,
        'status' => '0', 'schedule_repeat_type' => '1', 'flame_cd' => '1',
        'img_path_ary' => [0 => [0 => (string) $f['content']->cd, 1 => 'blank']],
        'action_ary' => [0 => ['action' => '2', 0 => '1']],
    ])->assertRedirect(route('admin.senalizacion.cycles.edit', [$f['package']->cd, 'cd' => $cycle->cd]));

    $cycle->refresh();
    expect($cycle->status)->toBe(0)
        ->and($cycle->schedule_start_time)->toBeNull()
        ->and($cycle->img_path_ary)->toBe(serialize([0 => [0 => (string) $f['content']->cd, 1 => 'blank']]))
        ->and($cycle->actions()[0]['action'])->toBe('2');
});

it('guarda la franja de 毎日特定の時間帯のみ con la fecha de hoy y la de 特定の期間のみ con sus fechas', function () {
    Carbon::setTestNow('2026-11-27 08:30:12');
    $f = snSignageFixture();
    $cycle = $f['cycles'][0];
    $url = route('admin.senalizacion.cycles.update', [$f['package']->cd, $cycle->cd]);
    $base = ['status' => 1, 'flame_cd' => 1, 'start_y' => 2026, 'start_m' => 12, 'start_d' => 1, 'start_h' => 9, 'start_i' => 15,
        'end_y' => 2027, 'end_m' => 1, 'end_d' => 15, 'end_h' => 18, 'end_i' => 45];

    $this->post($url, $base + ['schedule_repeat_type' => 2])->assertRedirect();
    expect($cycle->fresh()->only(['schedule_start_time', 'schedule_end_time']))
        ->toBe(['schedule_start_time' => '2026-11-27 09:15:12', 'schedule_end_time' => '2026-11-27 18:45:12']);

    $this->post($url, $base + ['schedule_repeat_type' => 3])->assertRedirect();
    expect($cycle->fresh()->only(['schedule_start_time', 'schedule_end_time']))
        ->toBe(['schedule_start_time' => '2026-12-01 09:15:00', 'schedule_end_time' => '2027-01-15 18:45:00']);

    // La pantalla los vuelve a mostrar partidos en los desplegables
    $this->get(route('admin.senalizacion.cycles.edit', [$f['package']->cd, 'cd' => $cycle->cd]))
        ->assertOk()
        ->assertSee('2026年12月01日09時15分 から 2027年01月15日18時45分 まで')
        ->assertSee('<option value="45" selected>45</option>', false);
});

it('da de alta y borra una talla de imagen y otra de vídeo (en movie_contents_category)', function () {
    $this->post(route('admin.senalizacion.content-categories.store', 'image'), ['insert' => 1, 'flg' => 1, 'name' => 'フルHD', 'width' => 1920, 'height' => 1080])
        ->assertRedirect(route('admin.senalizacion.content-categories.index', 'image'));
    $category = SignageWebContentCategory::sole();
    expect($category->only(['name', 'width', 'height', 'flg']))->toBe(['name' => 'フルHD', 'width' => 1920, 'height' => 1080, 'flg' => 1]);

    $this->post(route('admin.senalizacion.content-categories.store', 'video'), ['insert' => 1, 'width' => 1080, 'height' => 1920]);
    expect(SignageWebMovieContentCategory::sole()->flg)->toBe(0)
        ->and(SignageWebContentCategory::count())->toBe(1);

    $this->post(route('admin.senalizacion.content-categories.destroy', ['image', $category->cd]))->assertRedirect();
    expect(SignageWebContentCategory::count())->toBe(0)
        ->and(SignageWebMovieContentCategory::count())->toBe(1);
});

it('sube una imagen como <cd>.<ext> en signage/upload/<w>x<h>/, la cambia y la borra', function () {
    Storage::fake('public');
    // Imagen pequeña: una de 1920x1080 agota los 128 MB de PHP en la suite completa
    $category = SignageWebContentCategory::create(['name' => '小', 'width' => 192, 'height' => 108, 'flg' => 1]);

    $this->post(route('admin.senalizacion.contents.store', ['image', $category->cd]), [
        'insert' => 1, 'contents_category_cd' => $category->cd, 'name' => '春',
        'upfile' => UploadedFile::fake()->image('foto.png', 192, 108),
    ])->assertRedirect(route('admin.senalizacion.contents.index', ['image', $category->cd]));

    $content = SignageWebContent::sole();
    expect($content->only(['name', 'flg', 'img_path', 'contents_category_cd']))
        ->toBe(['name' => '春', 'flg' => 1, 'img_path' => $content->cd.'.png', 'contents_category_cd' => $category->cd]);
    Storage::disk('public')->assertExists("signage/upload/192x108/{$content->cd}.png");

    // La lista lee el archivo (tamaño correcto: sin rojo) y la URL pública lo sirve
    $this->get(route('admin.senalizacion.contents.index', ['image', $category->cd]))
        ->assertOk()->assertSee('192 x 108')->assertDontSee('color:red', false);
    $this->get("/signage/upload/192x108/{$content->cd}.png")->assertOk();

    // Cambio desde la ventana de detalle con otro tipo: borra el archivo anterior
    $this->post(route('admin.senalizacion.contents.detail.update', ['image', $content->cd]), [
        'update' => 1, 'cd' => $content->cd, 'name' => '夏', 'upfile' => UploadedFile::fake()->image('foto.jpg', 100, 100),
    ])->assertRedirect(route('admin.senalizacion.contents.detail', ['image', $content->cd]));
    expect($content->fresh()->only(['name', 'img_path']))->toBe(['name' => '夏', 'img_path' => $content->cd.'.jpg']);
    Storage::disk('public')->assertMissing("signage/upload/192x108/{$content->cd}.png");
    $this->get(route('admin.senalizacion.contents.index', ['image', $category->cd]))->assertSee('100 x 100')->assertSee('color:red', false);

    $this->post(route('admin.senalizacion.contents.destroy', ['image', $content->cd]))
        ->assertRedirect(route('admin.senalizacion.contents.index', ['image', $category->cd]));
    expect(SignageWebContent::count())->toBe(0);
    Storage::disk('public')->assertMissing("signage/upload/192x108/{$content->cd}.jpg");
});

it('rechaza un archivo que no es imagen sin crear la fila', function () {
    Storage::fake('public');
    $category = SignageWebContentCategory::create(['width' => 1920, 'height' => 1080, 'flg' => 1]);

    $this->followingRedirects()->post(route('admin.senalizacion.contents.store', ['image', $category->cd]), [
        'insert' => 1, 'name' => 'x', 'upfile' => UploadedFile::fake()->create('x.php', 1, 'text/x-php'),
    ])->assertOk()->assertSee('ファイル形式が不正です');

    expect(SignageWebContent::count())->toBe(0);
});

it('sube un vídeo a signage/upload/movie/<w>x<h>/', function () {
    Storage::fake('public');
    $category = SignageWebMovieContentCategory::create(['width' => 1080, 'height' => 1920, 'flg' => 1]);

    $this->post(route('admin.senalizacion.contents.store', ['video', $category->cd]), [
        'insert' => 1, 'name' => 'CM', 'upfile' => UploadedFile::fake()->create('cm.mp4', 10, 'video/mp4'),
    ])->assertRedirect();

    $movie = SignageWebMovieContent::sole();
    expect($movie->movie_path)->toBe($movie->cd.'.mp4');
    Storage::disk('public')->assertExists("signage/upload/movie/1080x1920/{$movie->cd}.mp4");
});

it('sube archivos de サイネージ管理 con su nombre y rechaza swf', function () {
    Storage::fake('public');

    $this->post(route('admin.senalizacion.files.store'), ['insert' => 1, 'upfile' => UploadedFile::fake()->image('fondo.png')])
        ->assertRedirect(route('admin.senalizacion.files.index'));
    $file = SignageWebFile::sole();
    expect($file->only(['color', 'flg']))->toBe(['color' => 'fondo.png', 'flg' => 1]);
    Storage::disk('public')->assertExists('signage/upload/fondo.png');

    $this->followingRedirects()->post(route('admin.senalizacion.files.store'), ['insert' => 1, 'upfile' => UploadedFile::fake()->create('a.swf', 1)])
        ->assertSee('ファイル形式が不正です');
    expect(SignageWebFile::count())->toBe(1);

    $this->post(route('admin.senalizacion.files.destroy', $file->cd))->assertRedirect();
    expect(SignageWebFile::count())->toBe(0);
    Storage::disk('public')->assertMissing('signage/upload/fondo.png');
});
