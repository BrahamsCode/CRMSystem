<?php

use App\Models\ContentsMaster;
use App\Models\Mypage\Recommendation;
use App\Models\Mypage\Recruit;
use App\Models\Promotions\KujibikiLog;
use App\Models\Senalizacion\SignageWebContent;
use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use App\Models\Terminales\FelicaCard;
use App\Models\Terminales\PaperCard;
use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use Database\Seeders\Demo\ContenidoDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../Senalizacion/helpers.php';

/* Datos de demostración de S4 (CONVENCIONES §4): conteos y segunda ejecución sin duplicados */

beforeEach(function () {
    Storage::fake('public');
    snSignageDatabase();
    seedNucleo();
});

function s4DemoCounts(): array
{
    return [
        'recommend' => Recommendation::query()->withDeleted()->count(),
        'recruit' => Recruit::query()->withDeleted()->count(),
        'lottery' => ContentsMaster::query()->where('category', 'tb_c6_kujibiki')->count(),
        'draws' => KujibikiLog::query()->count(),
        'materials' => SignageMaterial::query()->withDeleted()->count(),
        'programs' => SignageProgram::query()->withDeleted()->count(),
        'displays' => SignageDisplay::query()->withDeleted()->count(),
        'paper' => PaperCard::query()->count(),
        'felica' => FelicaCard::query()->count(),
        'web_packages' => SignageWebPackage::query()->count(),
        'web_terminals' => SignageWebTerminal::query()->count(),
        'mypage_config' => ContentsMaster::query()->where('category', 'tb_c6_user_hp')->count(),
    ];
}

it('siembra contenido y terminales sin socios y completa lo que depende de ellos después, sin duplicar', function () {
    $this->seed(ContenidoDemoSeeder::class);
    $first = s4DemoCounts();

    expect($first['recommend'])->toBe(15)
        ->and($first['recruit'])->toBe(14)
        ->and($first['lottery'])->toBeGreaterThan(0)
        ->and($first['draws'])->toBe(0)          // sin socios
        ->and($first['felica'])->toBe(14)        // casos límite sin socio
        ->and($first['materials'])->toBe(14)
        ->and($first['programs'])->toBe(12)
        ->and($first['displays'])->toBe(12)
        ->and($first['paper'])->toBe(12)         // 13 menos la de baja (PaperCard::query() excluye disp_flg 9)
        ->and($first['web_packages'])->toBe(2)
        ->and($first['web_terminals'])->toBe(3)
        ->and(SignageWebContent::query()->count())->toBe(3);

    // Imágenes locales y XML del reproductor
    $program = SignageProgram::query()->orderBy('cd')->first();
    $material = SignageMaterial::query()->orderBy('cd')->first();
    Storage::disk('public')->assertExists([
        'hp/uploads/c6/aplicover/0.jpg', 'hp/uploads/c6/aplibanner/2.jpg', 'hp/uploads/c6/apli_home_image/0_s.jpg',
        "hp/uploads/c7/screen_saver/{$material->cd}/0.jpg",
        "hp/uploads/c7/display/{$program->cd}.xml", 'hp/uploads/c7/display/floormap.xml',
    ]);
    expect(Storage::disk('public')->get("hp/uploads/c7/display/{$program->cd}.xml"))->toContain('title="ランチメニュー"');

    // Con socios: tiradas e ICカード en la segunda ejecución; el resto no se duplica
    foreach ([9001, 9002, 9003, 9004] as $cd) {
        DB::table('tb_customer')->insert(['cd' => $cd, 'tb_shop_cd' => 1, 'touroku_kubun' => '2', 'disp_flg' => '1', 'felica_no' => '']);
    }
    $this->seed(ContenidoDemoSeeder::class);
    $second = s4DemoCounts();

    expect($second['draws'])->toBeGreaterThan(0)
        ->and($second['felica'])->toBe(19)
        ->and(KujibikiLog::query()->where('time_limit', '<', now()->getTimestamp())->exists())->toBeTrue()
        ->and(FelicaCard::query()->whereNotNull('tb_customer_cd')->count())->toBe(3)
        ->and(DB::table('tb_customer')->where('felica_no', 'like', 'DEMOIDM%')->count())->toBe(3)
        ->and(array_diff_key($second, ['draws' => 1, 'felica' => 1]))->toBe(array_diff_key($first, ['draws' => 1, 'felica' => 1]));

    // Tercera: nada nuevo
    $this->seed(ContenidoDemoSeeder::class);
    expect(s4DemoCounts())->toBe($second);
});

it('siembra casos límite: bajas, japonés al máximo, varias tiendas, paginación y periodos de fin de mes/año', function () {
    DB::table('tb_shop')->insert([
        ['cd' => 9101, 'kaishamei' => '難波店', 'disp_flg' => '1'],
        ['cd' => 9102, 'kaishamei' => '京都店', 'disp_flg' => '1'],
    ]);
    $this->seed(ContenidoDemoSeeder::class);

    // Varias tiendas, bajas y máximos
    expect(SignageProgram::query()->distinct()->pluck('tb_shop_cd')->count())->toBe(3)
        ->and(SignageProgram::query()->withDeleted()->where('disp_flg', '9')->count())->toBe(1)
        ->and(Recruit::query()->withDeleted()->where('disp_flg', '9')->count())->toBe(1)
        ->and(SignageMaterial::query()->get()->max(fn ($m) => mb_strlen($m->title)))->toBe(100)
        ->and(FelicaCard::query()->where('flg', '9')->count())->toBe(1)
        ->and(PaperCard::query()->where('hyouji_flg', PaperCard::STOPPED)->count())->toBe(2)
        // QA :8081: 全店舗 es tb_shop_cd NULL como recommend.php (0 salía como una tienda inexistente)
        ->and(Recommendation::query()->withDeleted()->where('tb_shop_cd', 0)->count())->toBe(0)
        ->and(Recommendation::query()->withDeleted()->whereNull('tb_shop_cd')->count())->toBeGreaterThan(0);

    // Periodos de signage/: fin de mes, fin de año, solapados, 29-02 y caducado
    $ends = SignageWebCycle::query()->where('schedule_repeat_type', 3)->pluck('schedule_end_time')
        ->map(fn ($d) => substr((string) $d, 5, 5))->all();
    expect($ends)->toContain(now()->endOfMonth()->format('m-d'), '12-31', '02-29');

    // Los 放映素材 de la demo salen en el listado (filtra por el primer display) y las listas paginan
    s4Login();
    $this->get('/admin/terminales/signage-materials')->assertOk()->assertSee('ランチメニュー')->assertSee('次へ');
    $this->get('/admin/terminales/signage-programs')->assertOk()->assertSee('デモ 限界 プログラム')->assertSee('次へ');
    $this->get('/admin/mypage/recruits?kensu=50')->assertOk()->assertSee('任意項目なしの求人')->assertDontSee('削除済みの求人');
    $this->get('/admin/terminales/paper-cards')->assertOk()->assertSee('demo-edge-10')->assertDontSee('demo-edge-06');
});

it('el reproductor y las pantallas muestran los datos sembrados', function () {
    $this->seed(ContenidoDemoSeeder::class);
    $display = SignageDisplay::query()->orderBy('cd')->first();

    $this->get("/digitalsignage/index2.php?shop_cd={$display->tb_shop_cd}&display_cd={$display->cd}")
        ->assertOk()->assertSee('本日もご来店ありがとうございます。');

    s4Login();
    $this->get('/admin/mypage/recruits?kensu=50')->assertOk()->assertSee('未経験歓迎！ホールスタッフ募集');
    $this->get('/admin/terminales/paper-cards?page=2')->assertOk()->assertSee('demo-coupon');
    $this->get(route('admin.senalizacion.index'))->assertOk()->assertSee('端末番号 : '.SignageWebTerminal::query()->min('cd'));
    $this->get(route('admin.senalizacion.terminals.index'))->assertOk()->assertSee('入口')->assertSee('デモ ロビー');
});
