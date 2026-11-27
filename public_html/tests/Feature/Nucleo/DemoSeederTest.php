<?php

use App\Models\Staff;
use App\Services\Nucleo\Hatuban;
use Database\Seeders\Demo\NucleoDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

require_once __DIR__ . '/helpers.php';

// Datos de demostración del núcleo (CONVENCIONES §4): tiendas, áreas, grupos, staff, permisos y menús

/** Contenidos de permiso visibles mínimos (en el backup son 54) */
function seedKengenContents(): void
{
    foreach ([[1, 'ID'], [1001, '会員登録'], [1002, 'csv出力'], [1024, 'スタッフ管理']] as [$cd, $name]) {
        DB::table('tb_kengen_contents')->insert(['cd' => $cd, 'name' => $name, 'hyouji_flg' => '1']);
    }
    DB::table('tb_kengen_contents')->insert(['cd' => 1026, 'name' => '予約', 'hyouji_flg' => '0']);
    Hatuban::fixCounters(['tb_shop' => 2]); // como el backup: la tienda 1 ya numerada
}

function demoCounts(): array
{
    return collect(['tb_shop', 'tb_staff', 'tb_staff_group', 'tb_kengen', 'tb_area', 'tb_shop_category', 'tb_base_system', 'tb_base_system_child'])
        ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
}

it('siembra tiendas, áreas, grupos, staff y permisos sin duplicar en una segunda ejecución', function () {
    seedNucleo();
    seedKengenContents();

    $this->seed(NucleoDemoSeeder::class);
    $first = demoCounts();

    expect(DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('kaishamei')->all())->toBe(['本店', '梅田店', '難波店', '京都店'])
        ->and(DB::table('tb_staff')->where('login_id', '<>', 'master')->count())->toBe(10)
        ->and(DB::table('tb_staff_group')->pluck('name')->all())->toBe(['店長', 'スタッフ'])
        ->and($first['tb_kengen'])->toBe(2 * 4) // un grupo × cada contenido visible
        ->and(DB::table('tb_area')->orderBy('cd')->pluck('name')->all())->toBe(['関西', '大阪', '京都'])
        ->and(DB::table('tb_shop_category')->pluck('title')->all())->toBe(['直営店', 'フランチャイズ']);

    // Cada tienda de demo cuelga de su área y su grupo; 京都店 de 京都
    $kyoto = DB::table('tb_shop')->where('kaishamei', '京都店')->first();
    expect(DB::table('tb_area')->where('cd', $kyoto->area_cd)->value('name'))->toBe('京都')
        ->and($kyoto->zip)->toBe('6008216')
        ->and($kyoto->touroku_date)->not->toBeEmpty();

    // Un 店長 por tienda (también la del backup) y personal normal en las de demo
    $tencho = DB::table('tb_staff')->join('tb_staff_group', 'tb_staff_group.cd', '=', 'tb_staff.tb_staff_group_cd')
        ->where('tb_staff_group.name', '店長')->pluck('tb_staff.shop_cd')->sort()->values()->all();
    expect($tencho)->toBe([1, 2, 3, 4])
        ->and(DB::table('tb_shop')->distinct()->count('cd'))->toBe(4);

    $this->seed(NucleoDemoSeeder::class);
    expect(demoCounts())->toBe($first);
});

it('deja la clave de master y da permisos coherentes a 店長 y スタッフ', function () {
    seedNucleo();
    seedKengenContents();
    $this->seed(NucleoDemoSeeder::class);

    expect(Staff::where('login_id', 'master')->value('login_pass'))->toBe('secreto1');

    $tencho = Staff::where('login_id', 'tencho_umeda')->first();
    $staff = Staff::where('login_id', 'umeda01')->first();
    expect(Gate::forUser($tencho)->allows('kengen.1024.2'))->toBeTrue()
        ->and(Gate::forUser($tencho)->allows('kengen.1001.4'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('kengen.1024.1'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('kengen.1024.2'))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('kengen.1001.3'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('kengen.1001.4'))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('kengen.1002.1'))->toBeFalse();

    loginAs('tencho_umeda', NucleoDemoSeeder::DEMO_PASSWORD)->assertRedirect('/system/select.php');
});

it('activa los 6 módulos y sus menús para master', function () {
    seedNucleo();
    seedKengenContents();
    $this->seed(NucleoDemoSeeder::class);
    loginAs('master', 'secreto1');

    $html = $this->get('/system/select.php')->assertOk()->getContent();
    foreach ([1, 3, 5, 6, 7, 9] as $cd) {
        expect($html)->toContain('id="functionSelect_' . $cd . '"');
    }

    // Bloques que dependen de una opción activa (tb_base_system_child)
    $this->get('/system/category1/')->assertOk()->assertSee('入退室一覧')->assertSee('日報・週報・月報');
    $this->get('/system/category5/')->assertOk()->assertSee('日別シフト管理')->assertSee('営業日設定');
    $this->get('/system/category6/')->assertOk()->assertSee('マイコレ管理');
    $this->get('/system/category3/')->assertOk()->assertSee('ポイントルール一覧')->assertSee('ポイント集計')->assertSee('マイコレ登録用QR');
    $this->get('/system/category7/')->assertOk()->assertSee('放映プログラム管理')->assertSee('/admin/terminales/felica-terminals', false)
        ->assertDontSee('beacon_list.php', false);
});
