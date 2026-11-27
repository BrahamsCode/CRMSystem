<?php

use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

/* ディスプレイ (display_*.php): pantallas, alta/edición, borrado lógico y ▲▼ */

beforeEach(function () {
    s4Login(); // login real de S1: master de la tienda 1
    Storage::fake('public');

    SignageMaterial::create([
        'cd' => 850, 'tb_shop_cd' => 1, 'title' => '素材', 'play_frame_sec' => '5', 'filetype' => SignageMaterial::FILETYPE_IMAGE,
        'pic_flg' => '1', 'pic_extension' => '.jpg', 'disp_flg' => '1',
    ]);
    SignageProgram::create(['cd' => 30, 'tb_shop_cd' => 1, 'name' => '朝', 'disp_conf' => '1', 'tb_screensaver_cds' => '850', 'vertical_setting' => 'portrait', 'disp_flg' => '1', 'rank' => 1]);
    SignageProgram::create(['cd' => 31, 'tb_shop_cd' => 1, 'name' => '夜', 'disp_conf' => '1', 'disp_flg' => '1', 'rank' => 2]);
});

it('lista los displays con el nombre de su programa y muestra el formulario de alta', function () {
    SignageDisplay::create(['cd' => 40, 'tb_shop_cd' => 1, 'name' => '入口', 'comment' => '1F', 'tb_c7_movie_program_cds' => '30', 'disp_flg' => '1', 'rank' => 1]);

    $this->get(route('admin.terminales.signage-displays.index'))
        ->assertOk()
        ->assertSee('入口')
        ->assertSee('朝')
        ->assertSee('1件', false);

    $this->get(route('admin.terminales.signage-displays.create'))
        ->assertOk()
        ->assertSee('■ディスプレイ管理 新規登録', false)
        ->assertSee('<option value="31">夜</option>', false);
});

it('da de alta un display al final y escribe el XML de su programa', function () {
    SignageDisplay::create(['cd' => 40, 'tb_shop_cd' => 1, 'name' => '入口', 'disp_flg' => '1', 'rank' => 1]);

    $this->post(route('admin.terminales.signage-displays.store'), [
        'name' => 'レジ横', 'comment' => '', 'tb_c7_movie_program_cds' => '30', 'hb_which' => '0', 'act' => 'up',
    ])->assertOk()->assertSee('登録中です。');

    $display = SignageDisplay::query()->where('name', 'レジ横')->firstOrFail();
    expect($display->rank)->toBe(2)
        ->and($display->tb_shop_cd)->toBe(1)
        ->and($display->tb_c7_movie_program_cds)->toBe('30');
    expect(Storage::disk('public')->get('hp/uploads/c7/display/30.xml'))->toContain('<p cd="850"');
});

it('rechaza un display sin 端末名', function () {
    $this->from(route('admin.terminales.signage-displays.create'))
        ->post(route('admin.terminales.signage-displays.store'), ['name' => '', 'tb_c7_movie_program_cds' => '30'])
        ->assertSessionHasErrors(['name' => '必須項目です。']);
});

it('muestra el detalle con la vista previa y edita el programa', function () {
    SignageDisplay::create(['cd' => 41, 'tb_shop_cd' => 1, 'name' => '入口', 'tb_c7_movie_program_cds' => '30', 'disp_flg' => '1', 'rank' => 1]);

    $this->get(route('admin.terminales.signage-displays.show', 41))
        ->assertOk()
        ->assertSee('朝')
        ->assertSee('digitalsignage/index2.php?shop_cd=1&display_cd=41', false)
        ->assertSee('width: 550, height: 900', false);

    $this->post(route('admin.terminales.signage-displays.edit', 41), ['cd' => 41])
        ->assertOk()
        ->assertSee('■ディスプレイ管理 修正', false)
        ->assertSee('<option value="30" selected>朝</option>', false);

    $this->post(route('admin.terminales.signage-displays.update', 41), ['name' => '入口2', 'tb_c7_movie_program_cds' => '31'])
        ->assertOk();

    expect(SignageDisplay::query()->find(41)->only(['name', 'tb_c7_movie_program_cds', 'rank']))
        ->toBe(['name' => '入口2', 'tb_c7_movie_program_cds' => '31', 'rank' => 1]);
    Storage::disk('public')->assertExists('hp/uploads/c7/display/31.xml');
});

it('borra lógicamente el display sin tocar el XML del programa con el mismo número', function () {
    SignageDisplay::create(['cd' => 30, 'tb_shop_cd' => 1, 'name' => '削除用', 'disp_flg' => '1', 'rank' => 1]);
    Storage::disk('public')->put('hp/uploads/c7/display/30.xml', '<data/>');

    $this->post(route('admin.terminales.signage-displays.destroy', 30), ['delete_flg' => 'del', 'cd' => 30])
        ->assertOk()
        ->assertSee('削除中です。');

    expect(SignageDisplay::withDeleted()->find(30)->disp_flg)->toBe('9');
    Storage::disk('public')->assertExists('hp/uploads/c7/display/30.xml');
});

it('reordena con ▲▼ sin cambiar update_date', function () {
    foreach ([51 => 1, 52 => 2, 53 => 3] as $cd => $rank) {
        SignageDisplay::create(['cd' => $cd, 'tb_shop_cd' => 1, 'name' => "D{$cd}", 'disp_flg' => '1', 'rank' => $rank, 'update_date' => '1000']);
    }

    $this->get(route('admin.terminales.signage-displays.sort', ['thisCd' => 52, 'thisRow' => 2, 'kind' => 'up']))
        ->assertRedirect(route('admin.terminales.signage-displays.index', ['page' => null, 'kensu' => null]));

    expect(SignageDisplay::query()->orderBy('rank')->pluck('cd')->all())->toBe([52, 51, 53])
        ->and(SignageDisplay::query()->find(52)->update_date)->toBe('1000');

    $this->get(route('admin.terminales.signage-displays.index'))
        ->assertSee('thisCd=52&amp;thisRow=1&amp;kind=down', false);
});
