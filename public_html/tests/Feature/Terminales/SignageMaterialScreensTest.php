<?php

use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/fixtures/S4TestHelpers.php';

/* 放映素材 (screen_saver_*.php): pantallas, alta/edición con subida, borrado lógico y ▲▼ */

beforeEach(function () {
    s4UseTestLayout();
    // Solo se salta auth:admin (aún no hay login); sesión, errores y CSRF de prueba siguen activos
    $this->withoutMiddleware(Authenticate::class);
    $this->withSession(['shop_cd' => 1]);
    Storage::fake('public');
});

function s4Material(int $cd, array $attributes = []): SignageMaterial
{
    return SignageMaterial::create($attributes + [
        'cd' => $cd, 'tb_shop_cd' => 1, 'title' => "素材{$cd}", 'comment' => "内容{$cd}",
        'filetype' => SignageMaterial::FILETYPE_IMAGE, 'pic_flg' => '1<>0<>0<>0<>0',
        'pic_extension' => '.jpg<><><><>', 'disp_flg' => '1', 'rank' => 1,
    ]);
}

function s4MaterialPayload(array $overrides = []): array
{
    return $overrides + [
        'title' => '春のキャンペーン', 'comment' => '店頭用', 'filetype' => SignageMaterial::FILETYPE_IMAGE,
        'categorize_value_0' => '', 'categorize_value_0_new' => '季節',
        'pic_flg' => [0 => '0', 1 => '0', 2 => '0', 3 => '0', 4 => '0', 10 => '0', 20 => '0'],
        'pic_extension' => [0 => '', 1 => '', 2 => '', 3 => '', 4 => '', 10 => '', 20 => ''],
        'pic_max' => '5', 'play_frame_sec' => '', 'hb_which' => '1', 'act' => 'up',
    ];
}

it('lista los materiales del primer display con paginación por kensu', function () {
    SignageDisplay::create(['cd' => 10, 'tb_shop_cd' => 1, 'name' => '入口', 'disp_flg' => '1', 'rank' => 1]);
    SignageDisplay::create(['cd' => 11, 'tb_shop_cd' => 1, 'name' => 'レジ', 'disp_flg' => '1', 'rank' => 2]);
    foreach (range(1, 12) as $i) {
        s4Material(100 + $i, ['tb_display_cd' => 10, 'rank' => $i]);
    }
    s4Material(200, ['tb_display_cd' => 11, 'title' => '別の display']);

    $this->get(route('admin.terminales.signage-materials.index'))
        ->assertOk()
        ->assertSee('<option value="10" selected>入口</option>', false)
        ->assertSee('12件', false)
        ->assertSee('素材101')
        ->assertDontSee('別の display')
        ->assertSee('<li class="now"><b>1</b></li>', false);

    $this->post(route('admin.terminales.signage-materials.index'), ['display_cd' => 11, 'kensu' => 20])
        ->assertOk()
        ->assertSee('別の display')
        ->assertDontSee('素材101');
});

it('muestra el formulario de alta y guarda un material de imagen con sus miniaturas', function () {
    SignageDisplay::create(['cd' => 10, 'tb_shop_cd' => 1, 'name' => '入口', 'disp_flg' => '1', 'rank' => 1]);

    $this->get(route('admin.terminales.signage-materials.create', ['tb_display_cd' => 10]))
        ->assertOk()
        ->assertSee('■放映ファイル管理 新規登録', false)
        ->assertSee('name="tb_display_cd" id="tb_display_cd" value="10"', false)
        ->assertSee('先頭に追加', false);

    $response = $this->post(route('admin.terminales.signage-materials.store'), s4MaterialPayload([
        'tb_display_cd' => '10',
        'photo0' => UploadedFile::fake()->image('cartel.jpg', 640, 480),
    ]));

    $response->assertOk()->assertSee('登録中です。')->assertSee('Refresh', false);

    $material = SignageMaterial::query()->where('title', '春のキャンペーン')->firstOrFail();
    expect($material->tb_shop_cd)->toBe(1)
        ->and($material->tb_display_cd)->toBe(10)
        ->and($material->categorize_value_0)->toBe('季節')
        ->and($material->pic_flg)->toBe('1<>0<>0<>0<>0')
        ->and($material->pic_extension)->toBe('.jpg<><><><>')
        ->and($material->touroku_date)->toMatch('/^\d+$/');

    $dir = "hp/uploads/c7/screen_saver/{$material->cd}";
    Storage::disk('public')->assertExists(["{$dir}/0_moto.jpg", "{$dir}/0.jpg", "{$dir}/0s.jpg"]);
    expect(getimagesizefromstring(Storage::disk('public')->get("{$dir}/0s.jpg")))->toMatchArray([0 => 150, 1 => 150])
        ->and(getimagesizefromstring(Storage::disk('public')->get("{$dir}/0.jpg"))[0])->toBe(1280);
});

it('rechaza un alta sin título con la marca Ng y el mensaje del legacy', function () {
    $this->from(route('admin.terminales.signage-materials.create'))
        ->post(route('admin.terminales.signage-materials.store'), s4MaterialPayload(['title' => '']))
        ->assertRedirect(route('admin.terminales.signage-materials.create'))
        ->assertSessionHasErrors(['title' => '必須項目です。']);

    $this->from(route('admin.terminales.signage-materials.create'))
        ->followingRedirects()
        ->post(route('admin.terminales.signage-materials.store'), s4MaterialPayload(['title' => '']))
        ->assertSee('<td class="Ng">', false)
        ->assertSee('<p class="m0">※必須項目です。</p>', false)
        ->assertSee('value="店頭用"', false);

    expect(SignageMaterial::query()->count())->toBe(0);
});

it('avisa de un archivo con tipo no permitido y guarda el resto', function () {
    $this->post(route('admin.terminales.signage-materials.store'), s4MaterialPayload([
        'filetype' => SignageMaterial::FILETYPE_SWF,
        'photo20' => UploadedFile::fake()->image('no-es-flash.jpg'),
    ]))->assertOk()->assertSee('21枚目の画像 ※この拡張子のファイルはアップロード出来ません。');

    expect(SignageMaterial::query()->firstOrFail()->pic_flg)->toBe('0<><><><>');
});

it('muestra el detalle y edita conservando los huecos sin archivo nuevo', function () {
    s4Material(300, ['categorize_value_0' => 'お知らせ']);

    $this->get(route('admin.terminales.signage-materials.show', 300))
        ->assertOk()
        ->assertSee('hp/uploads/c7/screen_saver/300/0s.jpg', false)
        ->assertSee('お知らせ');

    $this->post(route('admin.terminales.signage-materials.edit', 300), ['cd' => 300])
        ->assertOk()
        ->assertSee('■放映ファイル管理 修正', false)
        ->assertSee('value="素材300"', false)
        ->assertDontSee('先頭に追加', false);

    $this->post(route('admin.terminales.signage-materials.update', 300), s4MaterialPayload([
        'title' => '更新後', 'categorize_value_0' => 'お知らせ', 'categorize_value_0_new' => '',
        'pic_flg' => [0 => '1', 1 => '0', 2 => '0', 3 => '0', 4 => '0'],
        'pic_extension' => [0 => '.jpg', 1 => '', 2 => '', 3 => '', 4 => ''],
    ]))->assertOk()->assertSee('放映ファイル管理 修正');

    $material = SignageMaterial::query()->find(300);
    expect($material->title)->toBe('更新後')
        ->and($material->categorize_value_0)->toBe('お知らせ')
        ->and($material->pic_flg)->toBe('1<>0<>0<>0<>0')
        ->and($material->pic_extension)->toBe('.jpg<><><><>');
});

it('borra lógicamente el material y su carpeta y muestra 削除しました', function () {
    s4Material(400);
    Storage::disk('public')->put('hp/uploads/c7/screen_saver/400/0.jpg', 'x');

    $this->post(route('admin.terminales.signage-materials.destroy', 400), ['delete_flg' => 'del', 'cd' => 400])
        ->assertOk()
        ->assertSee('削除中です。')
        ->assertSee(route('admin.terminales.signage-materials.deleted'), false);

    expect(SignageMaterial::withDeleted()->find(400)->disp_flg)->toBe('9');
    Storage::disk('public')->assertMissing('hp/uploads/c7/screen_saver/400/0.jpg');

    $this->get(route('admin.terminales.signage-materials.deleted'))->assertOk()->assertSee('削除しました');
});

it('sube y baja materiales dentro del mismo display con ▲▼', function () {
    foreach ([501 => 1, 502 => 2, 503 => 3] as $cd => $rank) {
        s4Material($cd, ['tb_display_cd' => 10, 'rank' => $rank]);
    }
    s4Material(509, ['tb_display_cd' => 11, 'rank' => 2]);

    $this->get(route('admin.terminales.signage-materials.sort', [
        'thisCd' => 503, 'thisRow' => 3, 'kind' => 'up', 'display_cd' => 10,
    ]))->assertRedirect();

    $order = fn () => SignageMaterial::query()->where('tb_display_cd', 10)->orderBy('rank')->pluck('cd')->all();
    expect($order())->toBe([501, 503, 502])
        ->and(SignageMaterial::query()->find(509)->rank)->toBe(2);

    $this->get(route('admin.terminales.signage-materials.sort', [
        'thisCd' => 501, 'thisRow' => 1, 'kind' => 'down', 'display_cd' => 10,
    ]));
    expect($order())->toBe([503, 501, 502]);
});
