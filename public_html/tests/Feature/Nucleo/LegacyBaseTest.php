<?php

use App\Models\AdminMaster;
use App\Models\ContentsMaster;
use App\Models\Shop;
use App\Models\Staff;
use App\Models\Terminal;
use App\Services\Nucleo\Hatuban;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Base legacy del núcleo: hatuban, borrado lógico disp_flg = '9' y fechas epoch, sobre el esquema del legacy

it('numera con hatuban como cHatuban (kazu es el próximo número libre)', function () {
    $hatuban = app(Hatuban::class);

    expect($hatuban->peek('tb_prueba'))->toBe(0)
        ->and($hatuban->next('tb_prueba'))->toBe(1)
        ->and($hatuban->peek('tb_prueba'))->toBe(2)
        ->and($hatuban->next('tb_prueba'))->toBe(2)
        ->and(DB::table('hatuban')->where('koumoku', 'tb_prueba')->count())->toBe(1);

    DB::table('hatuban')->insert(['koumoku' => 'tb_otro', 'kazu' => 42]);
    expect($hatuban->next('tb_otro'))->toBe(42)
        ->and($hatuban->peek('tb_otro'))->toBe(43);
});

it('crea una tienda con cd de hatuban y fechas epoch en texto', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_shop', 'kazu' => 7]);
    Carbon::setTestNow('2026-11-26 10:00:00');

    $shop = Shop::create(['kaishamei' => '本店', 'disp_flg' => '1']);

    $fila = DB::table('tb_shop')->where('cd', 7)->first();
    expect($shop->cd)->toBe(7)
        ->and($fila->kaishamei)->toBe('本店')
        ->and($fila->touroku_date)->toBe((string) now()->timestamp)
        ->and($fila->update_date)->toBe((string) now()->timestamp)
        ->and($shop->name)->toBe('本店')
        ->and($shop->legacyDate('touroku_date')->equalTo(now()))->toBeTrue();

    Carbon::setTestNow('2026-11-27 10:00:00');
    $shop->update(['tel' => '06-0000-0000']);
    expect(DB::table('tb_shop')->where('cd', 7)->value('update_date'))->toBe((string) now()->timestamp)
        ->and(DB::table('tb_shop')->where('cd', 7)->value('touroku_date'))->not->toBe((string) now()->timestamp);
});

it('tolera fechas vacías del legacy al leer', function () {
    DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => 'A', 'disp_flg' => '1', 'update_date' => '']);

    expect(Shop::find(1)->legacyDate('update_date'))->toBeNull()
        ->and(Shop::find(1)->update_date)->toBe('');
});

it('borra de forma lógica con disp_flg = 9 y lo oculta', function () {
    DB::table('tb_shop')->insert([
        ['cd' => 1, 'kaishamei' => 'A', 'disp_flg' => '1'],
        ['cd' => 2, 'kaishamei' => 'B', 'disp_flg' => '1'],
    ]);

    Shop::find(2)->delete();

    expect(DB::table('tb_shop')->where('cd', 2)->value('disp_flg'))->toBe('9')
        ->and(Shop::pluck('cd')->all())->toBe([1])
        ->and(Shop::withDeleted()->count())->toBe(2)
        ->and(Shop::onlyDeleted()->pluck('cd')->all())->toBe([2]);

    Shop::onlyDeleted()->find(2)->restore();
    expect(Shop::count())->toBe(2);

    Carbon::setTestNow('2026-11-28 09:00:00');
    Shop::where('cd', 1)->update(['tel' => '1']);
    expect(DB::table('tb_shop')->where('cd', 1)->value('update_date'))->toBe((string) now()->timestamp);

    Shop::find(1)->forceDelete();
    expect(DB::table('tb_shop')->where('cd', 1)->exists())->toBeFalse();
});

it('trata disp_flg NULL como visible, igual que el legacy', function () {
    DB::table('tb_shop')->insert([
        ['cd' => 1, 'kaishamei' => 'A', 'disp_flg' => null],
        ['cd' => 2, 'kaishamei' => 'B', 'disp_flg' => '9'],
        ['cd' => 3, 'kaishamei' => 'C', 'disp_flg' => '1'],
    ]);

    expect(Shop::orderBy('cd')->pluck('cd')->all())->toBe([1, 3])
        ->and(Shop::onlyDeleted()->pluck('cd')->all())->toBe([2]);
});

it('relaciona tienda, personal y terminales por sus columnas del legacy', function () {
    DB::table('tb_shop')->insert(['cd' => 3, 'kaishamei' => '梅田店', 'disp_flg' => '1']);
    DB::table('tb_staff')->insert([
        ['cd' => 1, 'shop_cd' => 3, 'login_id' => 'master', 'login_pass' => 'x', 'disp_flg' => '1', 'name' => '管理者'],
        ['cd' => 2, 'shop_cd' => 3, 'login_id' => 'tanaka', 'login_pass' => 'y', 'disp_flg' => '9', 'name' => '田中'],
    ]);
    $terminal = Terminal::create(['tb_shop_cd' => 3, 'name' => 'タブレット1', 'rank' => 1]);

    $shop = Shop::find(3);
    expect($shop->staff->pluck('login_id')->all())->toBe(['master'])
        ->and($shop->terminals->first()->is($terminal))->toBeTrue()
        ->and($terminal->cd)->toBe(1)
        ->and(Staff::find(1)->isMaster())->toBeTrue()
        ->and(Staff::find(1)->shop->kaishamei)->toBe('梅田店')
        ->and(Staff::withDeleted()->active()->count())->toBe(1)
        ->and(Staff::find(1)->toArray())->not->toHaveKey('login_pass');

    $terminal->delete();
    expect(DB::table('tb_terminal')->count())->toBe(0);
});

it('lee la configuración global de admin_master (fila cd = 1)', function () {
    expect(AdminMaster::current()->exists)->toBeFalse();

    DB::table('admin_master')->insert(['cd' => 1, 'system_mail' => 'info@example.com', 'seller_id' => 1]);

    expect(AdminMaster::current()->system_mail)->toBe('info@example.com')
        ->and(AdminMaster::current()->seller_id)->toBe(1);
});

it('lee y escribe pares categoría/campo de contents_master', function () {
    expect(ContentsMaster::text('mycolle', 'app_layout_mode', '1'))->toBe('1');

    ContentsMaster::put('mycolle', 'app_layout_mode', '2');
    ContentsMaster::put('mycolle', 'app_layout_mode', '3');

    $filas = DB::table('contents_master')->where('category', 'mycolle')->get();
    expect($filas)->toHaveCount(1)
        ->and($filas[0]->text_data)->toBe('3')
        ->and((int) $filas[0]->touroku_date)->toBeGreaterThan(0)
        ->and(ContentsMaster::text('mycolle', 'app_layout_mode'))->toBe('3');

    DB::table('contents_master')->where('category', 'mycolle')->update(['disp_flg' => '9']);
    expect(ContentsMaster::text('mycolle', 'app_layout_mode'))->toBeNull();
});

it('Hatuban::fixCounters fija el próximo número de next()', function () {
    \App\Services\Nucleo\Hatuban::fixCounters(['tb_qa' => 50]);
    expect(app(\App\Services\Nucleo\Hatuban::class)->next('tb_qa'))->toBe(50)
        ->and(app(\App\Services\Nucleo\Hatuban::class)->peek('tb_qa'))->toBe(51);
});
