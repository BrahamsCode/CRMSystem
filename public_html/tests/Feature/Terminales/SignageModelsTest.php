<?php

use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use App\Models\Terminales\SignageSystem;
use Illuminate\Support\Facades\DB;

/* Modelos de 電子看板 sobre tb_screensaver, tb_c7_movie_program, tb_display y tb_dispaly_systems */

function hatubanFor(string $table, int $next): void
{
    DB::table('hatuban')->updateOrInsert(['koumoku' => $table], ['kazu' => $next]);
}

it('numera con hatuban, guarda las fechas en epoch y borra con disp_flg = 9', function () {
    hatubanFor('tb_screensaver', 500);

    $material = SignageMaterial::create([
        'title' => '本日のおすすめ', 'filetype' => SignageMaterial::FILETYPE_IMAGE,
        'play_frame_sec' => '10', 'pic_flg' => '1<>', 'pic_extension' => 'jpg<>', 'disp_flg' => '1',
    ]);

    expect($material->cd)->toBe(500)
        ->and($material->touroku_date)->toMatch('/^\d+$/')
        ->and($material->imageSlots())->toBe([['flg' => '1', 'ext' => 'jpg'], ['flg' => '', 'ext' => '']]);

    $material->delete();

    expect(SignageMaterial::query()->find(500))->toBeNull()
        ->and(SignageMaterial::withDeleted()->find(500)->disp_flg)->toBe('9');
});

it('da los materiales del programa en el orden de tb_screensaver_cds, sin los borrados', function () {
    foreach ([601 => '1', 602 => '1', 603 => '9'] as $cd => $flag) {
        SignageMaterial::create(['cd' => $cd, 'title' => "M{$cd}", 'filetype' => SignageMaterial::FILETYPE_IMAGE, 'disp_flg' => $flag]);
    }
    $program = SignageProgram::create(['cd' => 650, 'name' => 'P', 'disp_conf' => SignageProgram::CONF_FILES, 'disp_flg' => '1'])
        ->setMaterialCds([602, 601, 603]);
    $program->save();

    expect($program->fresh()->tb_screensaver_cds)->toBe('602<>601<>603')
        ->and($program->materials()->pluck('cd')->all())->toBe([602, 601])
        ->and($program->usesFiles())->toBeTrue();
});

it('relaciona display, programa, materiales y sistemas', function () {
    $program = SignageProgram::create(['cd' => 700, 'name' => 'P', 'disp_conf' => '1', 'disp_flg' => '1']);
    $display = SignageDisplay::create([
        'cd' => 710, 'name' => '入口', 'tb_c7_movie_program_cds' => '700', 'terminal_type' => '1<>2',
        'tb_mycoupon_master_cds' => '5<><>7', 'tb_dispaly_systems_cds' => '720', 'disp_flg' => '1',
    ]);
    SignageMaterial::create(['cd' => 730, 'title' => 'M', 'filetype' => SignageMaterial::FILETYPE_IMAGE, 'tb_display_cd' => 710, 'rank' => 3, 'disp_flg' => '1']);
    SignageSystem::create(['cd' => 720, 'name' => 'ルーレット', 'disp_flg' => '1']);

    expect($display->program()->cd)->toBe(700)
        ->and($display->materials()->pluck('cd')->all())->toBe([730])
        ->and($display->terminalTypes())->toBe(['1', '2'])
        ->and($display->couponCds())->toBe(['5', '', '7'])
        ->and($display->systemCds())->toBe([720])
        ->and($program->displays()->pluck('cd')->all())->toBe([710])
        ->and(SignageMaterial::rankForNew(710, false))->toBe(4)
        ->and(SignageMaterial::rankForNew(710, true))->toBe(0)
        ->and(SignageSystem::query()->find(720)->touroku_date)->not->toBeNull();
});
