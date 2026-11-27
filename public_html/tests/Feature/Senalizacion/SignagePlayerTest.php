<?php

use App\Models\Senalizacion\SignageWebCycle;
use App\Models\Senalizacion\SignageWebPackage;
use App\Models\Senalizacion\SignageWebTerminal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

beforeEach(fn () => snSignageDatabase());

afterEach(fn () => Carbon::setTestNow());

/** Ciclo con franja (tipo 2 o 3) del paquete del fixture; devuelve su cd */
function snScheduledCycle(array $f, int $type, string $start, string $end): int
{
    return SignageWebCycle::create([
        'packages_cd' => $f['package']->cd, 'flg' => 1, 'status' => 1, 'flame_cd' => 1,
        'schedule_repeat_type' => $type, 'schedule_start_time' => $start, 'schedule_end_time' => $end,
    ])->cd;
}

/** cd de los ciclos que devuelve view/ajax.php ahora (por su update_date, que se fija al cd) */
function snVisibleCycles(array $f): array
{
    SignageWebCycle::query()->toBase()->update(['update_date' => DB::connection('signage')->raw("'2026-01-01 00:00:00'::timestamp + (cd || ' minutes')::interval")]);

    return collect(json_decode(test()->get('/signage/view/ajax.php?packages_cd='.$f['terminal']->cd)->getContent(), true))
        ->map(fn ($row) => (int) Carbon::parse($row['update_date'])->diffInMinutes(Carbon::parse('2026-01-01 00:00:00'), true))
        ->all();
}

it('pinta la pantalla del terminal con el diseño del paquete, sus imágenes y el sondeo', function () {
    Storage::fake('public');
    $f = snSignageFixture();
    UploadedFile::fake()->image('x.jpg', 16, 9)->storeAs('signage/upload/1920x1080', '1.jpg', 'public');

    foreach (['/signage/view?cd=', '/signage/view/index.php?cd='] as $url) {
        $this->get($url.$f['terminal']->cd)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('<title>デジタルサイネージ</title>', false)
            ->assertSee('href="/signage/view/css/common.css"', false)
            ->assertSee('src="/signage/view/js/jquery.bxslider.js"', false)
            ->assertSee("$('.bxslider1_1').bxSlider", false)
            ->assertSee("mode: 'fade'", false)
            ->assertSee('src="/signage/upload/1920x1080/1.jpg?q=', false)
            ->assertSee('alt="春のバナー"', false)
            ->assertSee('/signage/view/ajax.php?packages_cd='.$f['terminal']->cd, false);
    }

    $this->get('/signage/upload/1920x1080/1.jpg')->assertOk();
    $this->get('/signage/upload/../.env')->assertNotFound();
});

it('lee el efecto guardado con la clave \'action\' del formulario del legacy', function () {
    $f = snSignageFixture(['action_ary' => serialize([["'action'" => '2']])]);

    $this->get('/signage/view?cd='.$f['terminal']->cd)->assertOk()->assertSee("mode: 'horizontal'", false);
});

it('responde con los mensajes del legacy si falta el terminal, el paquete o el diseño', function () {
    $f = snSignageFixture();

    $this->get('/signage/view?cd=999')->assertOk()->assertContent('データの取得に失敗しました');

    $f['terminal']->update(['flg' => 0]);
    $this->get('/signage/view?cd='.$f['terminal']->cd)->assertContent('データの取得に失敗しました');

    $f['terminal']->update(['flg' => 1]);
    $f['package']->update(['flg' => 0]);
    $this->get('/signage/view?cd='.$f['terminal']->cd)->assertContent('設定されているパッケージがありません。');

    // h_1_5 tiene marcos en el CMS pero no pantalla en view/
    $f['package']->update(['flg' => 1, 'display_type' => 'h_1_5']);
    $this->get('/signage/view?cd='.$f['terminal']->cd)->assertContent('現在、準備中です。');
});

it('devuelve en view/ajax.php el JSON de update_date de los ciclos visibles del terminal', function () {
    $f = snSignageFixture(cycles: 2);
    SignageWebCycle::whereKey($f['cycles'][0]->cd)->toBase()->update(['update_date' => '2026-11-27 10:00:00']);
    SignageWebCycle::whereKey($f['cycles'][1]->cd)->toBase()->update(['update_date' => '2026-11-27 11:30:00']);
    // Otro paquete y un ciclo parado: no salen
    SignageWebCycle::create(['packages_cd' => $f['package']->cd, 'flg' => 1, 'status' => 0, 'schedule_repeat_type' => 1]);
    SignageWebCycle::create(['packages_cd' => $f['package']->cd + 1, 'flg' => 1, 'status' => 1, 'schedule_repeat_type' => 1]);

    $this->get('/signage/view/ajax.php?packages_cd='.$f['terminal']->cd)
        ->assertOk()
        ->assertContent('[{"update_date":"2026-11-27 10:00:00"},{"update_date":"2026-11-27 11:30:00"}]');

    $this->get('/signage/view/ajax.php?packages_cd=999')->assertContent('[]');
});

it('aplica la franja 1 常に表示 siempre', function () {
    $f = snSignageFixture();
    Carbon::setTestNow('2026-11-27 03:00:00');

    expect(snVisibleCycles($f))->toBe([$f['cycles'][0]->cd]);
});

it('aplica la franja 2 毎日特定の時間帯のみ por hora y minuto, cualquier día (corrige hh24:mm)', function () {
    $f = snSignageFixture();
    $f['cycles'][0]->delete();
    // Guardado otro día y otro mes: solo cuenta la hora
    $cd = snScheduledCycle($f, 2, '2026-03-02 09:15:00', '2026-03-02 18:30:00');

    $cases = ['2026-11-27 09:14:59' => [], '2026-11-27 09:15:00' => [$cd], '2026-11-27 13:00:00' => [$cd],
        '2026-11-27 18:29:00' => [$cd], '2026-11-27 18:30:00' => [], '2026-11-28 23:00:00' => []];
    foreach ($cases as $now => $expected) {
        Carbon::setTestNow($now);
        expect(snVisibleCycles($f))->toBe($expected, $now);
    }
});

it('aplica la franja 3 特定の期間のみ entre las dos fechas', function () {
    $f = snSignageFixture();
    $f['cycles'][0]->delete();
    $cd = snScheduledCycle($f, 3, '2026-12-01 09:00:00', '2026-12-24 21:00:00');

    $cases = ['2026-11-30 23:59:00' => [], '2026-12-01 09:00:01' => [$cd], '2026-12-24 20:59:59' => [$cd], '2026-12-24 21:00:01' => []];
    foreach ($cases as $now => $expected) {
        Carbon::setTestNow($now);
        expect(snVisibleCycles($f))->toBe($expected, $now);
    }

    // La pantalla del terminal usa la misma regla: fuera de la franja no hay sliders
    Carbon::setTestNow('2026-11-30 12:00:00');
    $this->get('/signage/view?cd='.$f['terminal']->cd)->assertOk()->assertDontSee('cycle_group1', false);
    Carbon::setTestNow('2026-12-10 12:00:00');
    $this->get('/signage/view?cd='.$f['terminal']->cd)->assertOk()->assertSee('cycle_group1', false);
});

it('muestra la vista previa del paquete (view/packages.php) dentro del admin', function () {
    $f = snSignageFixture();
    s4Login();

    $this->get(route('admin.senalizacion.packages.preview', ['cd' => $f['package']->cd]))
        ->assertOk()
        ->assertSee("$('.bxslider1_1').bxSlider", false);

    $this->get(route('admin.senalizacion.packages.preview', ['cd' => 999]))->assertSee('設定されているパッケージがありません。');
});

it('pinta las 13 pantallas de diseño del reproductor sin errores', function (string $type) {
    $f = snSignageFixture();
    SignageWebPackage::whereKey($f['package']->cd)->update(['display_type' => $type]);
    SignageWebTerminal::whereKey($f['terminal']->cd)->update(['display_type' => $type]);

    $this->get('/signage/view?cd='.$f['terminal']->cd)
        ->assertOk()
        ->assertSee('<title>デジタルサイネージ</title>', false)
        ->assertSee('cycle_group1', false)
        // QA :8081: el sondeo iba con type "GETT" (errata del legacy) y Laravel respondía 405
        ->assertSee('type: "GET"', false)
        ->assertDontSee('type: "GETT"', false);
})->with(['h_1_1', 'h_1_2', 'h_1_3', 'h_1_4', 'h_2_1', 'h_2_2', 'h_3_1', 'h_4_1', 'h_4_2', 'v_1_1', 'v_1_2', 'v_2_1', 'v_2_2']);

it('pinta los 16 diseños de marco en la pantalla de ciclos', function (string $type) {
    $f = snSignageFixture();
    SignageWebPackage::whereKey($f['package']->cd)->update(['display_type' => $type]);
    s4Login();

    $this->get(route('admin.senalizacion.cycles.edit', [$f['package']->cd, 'cd' => $f['cycles'][0]->cd]))
        ->assertOk()
        ->assertSee('name="flame_cd"', false)
        ->assertDontSee('現在、準備中です', false)
        ->assertSee('function flameCdReload()', false);
})->with(['h_1_1', 'h_1_2', 'h_1_3', 'h_1_4', 'h_1_5', 'h_2_1', 'h_2_2', 'h_3_1', 'h_4_1', 'h_4_2', 'h_5_1', 'h_6_1', 'v_1_1', 'v_1_2', 'v_2_1', 'v_2_2']);
