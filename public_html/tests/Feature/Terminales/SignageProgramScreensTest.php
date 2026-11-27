<?php

use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use App\Models\Terminales\SignageSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

/* 放映プログラム (movie_program_*.php): pantallas, alta/edición con XML, borrado lógico */

beforeEach(function () {
    s4Login(); // login real de S1: master de la tienda 1
    Storage::fake('public');

    foreach ([801 => 'オープニング', 802 => '新メニュー', 803 => 'スタッフ紹介'] as $cd => $title) {
        SignageMaterial::create([
            'cd' => $cd, 'tb_shop_cd' => 1, 'title' => $title, 'comment' => "c{$cd}", 'play_frame_sec' => '10',
            'filetype' => SignageMaterial::FILETYPE_IMAGE, 'pic_flg' => '1', 'pic_extension' => '.jpg',
            'categorize_value_0' => $cd === 803 ? '人' : 'お知らせ', 'disp_flg' => '1', 'rank' => $cd - 800,
        ]);
    }
    foreach ([71 => 'クーポンA', 72 => 'クーポンB'] as $cd => $title) {
        DB::table('tb_mycoupon_master')->insert([
            'cd' => $cd, 'tb_shop_cd' => 1, 'title' => $title, 'comment' => '', 'coupon_kubun' => '8',
            'disp_flg' => '1', 'update_date' => (string) (1700000000 + $cd),
        ]);
    }
    SignageSystem::create(['cd' => 61, 'tb_shop_cd' => 1, 'name' => 'ルーレット', 'non_coupon_flg' => '0', 'disp_flg' => '1', 'rank' => 1]);
});

function s4ProgramPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => '平日プログラム', 'telop_comment' => 'いらっしゃいませ', 'vertical_setting' => 'landscape',
        'disp_conf' => '1', 'act_flg' => '1', 'feli_act_flg' => '1', 'mypage_act_flg' => '',
        'feli_tb_mycoupon_master_cds' => '71', 'mypage_tb_mycoupon_master_cds' => '',
        // Casillas posicionales: la fila 0 (クーポンB, más reciente) sin marcar, la fila 1 (クーポンA) marcada
        'tb_mycoupon_master_cds1' => '71', 'tb_mycoupon_master_max' => '2', 'system_tb_mycoupon_master_max' => '2',
        'tb_dispaly_systems_cds' => '61', 'non_coupon_flg' => '', 'comment' => 'メモ',
        'tb_screensaver_cd' => ['802', '801'], 'act' => 'up',
    ];
}

it('muestra el listado y el formulario de alta con cupones, sistemas y materiales', function () {
    SignageProgram::create(['cd' => 900, 'tb_shop_cd' => 1, 'name' => '既存', 'disp_conf' => '2', 'disp_flg' => '1', 'rank' => 1]);

    $this->get(route('admin.terminales.signage-programs.index'))
        ->assertOk()
        ->assertSee('既存')
        ->assertSee('システム内蔵「出力画面」');

    $this->get(route('admin.terminales.signage-programs.create'))
        ->assertOk()
        ->assertSee('■放映プログラム管理 新規登録', false)
        ->assertSee('name="tb_mycoupon_master_cds0" id="tb_mycoupon_master_cds0" value="72"', false)
        ->assertSee('non_coupon_flg="0" value="61"', false)
        ->assertSee('【803】スタッフ紹介', false)
        ->assertSee('この内容で登録する', false);
});

it('filtra los 選択可能ファイル con el buscador (POST a la misma pantalla)', function () {
    $this->post(route('admin.terminales.signage-programs.create'), ['search_categorize_value_0' => '人', 'act' => 'search'])
        ->assertOk()
        ->assertSee('【803】スタッフ紹介', false)
        ->assertDontSee('【801】オープニング', false);
});

it('da de alta un programa al final, con cupones posicionales, y escribe su XML', function () {
    SignageProgram::create(['cd' => 900, 'tb_shop_cd' => 1, 'name' => '既存', 'disp_conf' => '1', 'disp_flg' => '1', 'rank' => 1]);

    $this->post(route('admin.terminales.signage-programs.store'), s4ProgramPayload(['hb_which' => '0']))
        ->assertOk()
        ->assertSee('登録中です。');

    $program = SignageProgram::query()->where('name', '平日プログラム')->firstOrFail();
    expect($program->tb_shop_cd)->toBe(1)
        ->and($program->rank)->toBe(2)
        ->and($program->tb_screensaver_cds)->toBe('802<>801')
        ->and($program->tb_mycoupon_master_cds)->toBe('<>71')
        ->and($program->system_tb_mycoupon_master_cds)->toBe('<>')
        ->and($program->feli_tb_mycoupon_master_cds)->toBe('71')
        ->and($program->terminal_type)->toBe('');

    $xml = Storage::disk('public')->get("hp/uploads/c7/display/{$program->cd}.xml");
    expect($xml)->toContain('<p cd="802"')
        ->and(strpos($xml, 'cd="802"'))->toBeLessThan(strpos($xml, 'cd="801"'));
    Storage::disk('public')->assertExists('hp/uploads/c7/display/floormap.xml');
});

it('rechaza un programa sin nombre con el mensaje del legacy', function () {
    $this->from(route('admin.terminales.signage-programs.create'))
        ->post(route('admin.terminales.signage-programs.store'), s4ProgramPayload(['name' => '']))
        ->assertSessionHasErrors(['name' => '必須項目です。']);

    // Al volver se conservan los archivos elegidos (el legacy los perdía)
    $this->from(route('admin.terminales.signage-programs.create'))
        ->followingRedirects()
        ->post(route('admin.terminales.signage-programs.store'), s4ProgramPayload(['name' => '']))
        ->assertSee('<p class="m0">※必須項目です。</p>', false)
        ->assertSee('<div id="listItem-802"', false);
});

it('muestra el detalle con archivos, cupones y vista previa, y edita', function () {
    $program = SignageProgram::create([
        'cd' => 910, 'tb_shop_cd' => 1, 'name' => '週末', 'disp_conf' => '1', 'disp_flg' => '1', 'rank' => 1,
        'tb_screensaver_cds' => '803<>801', 'tb_mycoupon_master_cds' => '72<>', 'act_flg' => '1',
        'feli_act_flg' => '2', 'feli_tb_mycoupon_master_cds' => '71', 'vertical_setting' => 'portrait',
    ]);
    SignageDisplay::create(['cd' => 20, 'tb_shop_cd' => 1, 'name' => '入口', 'tb_c7_movie_program_cds' => '910', 'disp_flg' => '1']);

    $this->get(route('admin.terminales.signage-programs.show', 910))
        ->assertOk()
        ->assertSee('スタッフ紹介')
        ->assertSee('動作⇒新規会員登録時', false)
        ->assertSee('出力クーポン⇒クーポンA', false)
        ->assertSee('クーポンB')
        ->assertSee('display_cd=20', false)
        ->assertSee('width: 550, height: 900', false);

    $this->post(route('admin.terminales.signage-programs.edit', 910), ['cd' => 910])
        ->assertOk()
        ->assertSee('この内容を登録し、選択中のディスプレイに反映する', false)
        ->assertSee('name="tb_mycoupon_master_cds0" id="tb_mycoupon_master_cds0" value="72" checked', false)
        ->assertSee('<div id="listItem-803"', false);

    $this->post(route('admin.terminales.signage-programs.update', 910), s4ProgramPayload(['name' => '週末2', 'tb_screensaver_cd' => ['801']]))
        ->assertOk()
        ->assertSee('放映プログラム管理 修正');

    expect($program->fresh()->name)->toBe('週末2')
        ->and($program->fresh()->rank)->toBe(1)
        ->and($program->fresh()->tb_screensaver_cds)->toBe('801');
    expect(Storage::disk('public')->get('hp/uploads/c7/display/910.xml'))->toContain('cd="801"')->not->toContain('cd="803"');
});

it('borra lógicamente el programa y vuelve al listado', function () {
    SignageProgram::create(['cd' => 920, 'tb_shop_cd' => 1, 'name' => '削除用', 'disp_conf' => '1', 'disp_flg' => '1']);

    $this->post(route('admin.terminales.signage-programs.destroy', 920), ['delete_flg' => 'del', 'cd' => 920, 'page' => 2])
        ->assertOk()
        ->assertSee('削除中です。')
        ->assertSee(route('admin.terminales.signage-programs.index').'?', false);

    expect(SignageProgram::withDeleted()->find(920)->disp_flg)->toBe('9');
    $this->get(route('admin.terminales.signage-programs.index'))->assertDontSee('削除用');
});
