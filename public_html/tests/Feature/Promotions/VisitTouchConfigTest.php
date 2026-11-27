<?php

use App\Models\Promotions\TouchMode;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';
require_once __DIR__.'/visit_helpers.php';

/* F7 来店履歴管理: カードリーダ設定 (touch_config + touch_config_register) sobre tb_touch_mode */

beforeEach(function () {
    s4Login();
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
});

it('crea la fila de cada tienda con 30分 al abrir la pantalla (alta) y muestra 全店舗', function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_touch_mode', 'kazu' => 7]);

    $this->get(route('admin.promocion.visit.touch-config'))
        ->assertOk()
        ->assertSee('<title>カードリーダ設定 - ', false)
        ->assertSee('カード重複読込時間間隔の設定')
        ->assertSee('<select id="zen_kankaku">', false)
        ->assertSeeInOrder(['本店', 'name="kankaku[1]"', '<option value="1800" selected>30分</option>', '支店', 'name="kankaku[2]"'], false)
        ->assertSee('<option value="86400" >24時間</option>', false);

    $rows = TouchMode::query()->orderBy('tb_shop_cd')->get();
    expect($rows->pluck('tb_shop_cd')->all())->toBe([1, 2])
        ->and($rows->pluck('cd')->all())->toBe([7, 8])
        ->and($rows[0]->mode)->toBe('1')
        ->and($rows[0]->interval_time)->toBe('1800')
        ->and($rows[0]->update_date)->not->toBeEmpty();

    // Volver a abrir no duplica
    $this->get(route('admin.promocion.visit.touch-config'))->assertOk();
    expect(TouchMode::count())->toBe(2);
});

it('更新: guarda el intervalo de cada tienda y vuelve a la pantalla (edición)', function () {
    DB::table('tb_touch_mode')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'mode' => '1', 'interval_time' => '1800', 'update_date' => '1'],
        ['cd' => 2, 'tb_shop_cd' => 2, 'mode' => '1', 'interval_time' => '', 'update_date' => '1'],
    ]);

    $this->post(route('admin.promocion.visit.touch-config.update'), ['kankaku' => [1 => 7200, 2 => 0], 'act' => 'up', 'send' => '更新'])
        ->assertOk()
        ->assertSee('登録中です。')
        ->assertSee('<meta http-equiv="Refresh" content="1; URL='.route('admin.promocion.visit.touch-config').'">', false);

    expect(DB::table('tb_touch_mode')->where('tb_shop_cd', 1)->value('interval_time'))->toBe('7200')
        ->and(DB::table('tb_touch_mode')->where('tb_shop_cd', 2)->value('interval_time'))->toBe('0')
        ->and(DB::table('tb_touch_mode')->where('tb_shop_cd', 1)->value('update_date'))->not->toBe('1');

    $this->get(route('admin.promocion.visit.touch-config'))
        ->assertSee('<option value="7200" selected>2時間</option>', false)
        ->assertSee('<option value="0" selected>0分</option>', false);

    // Valor fuera de las opciones: no se guarda
    $this->post(route('admin.promocion.visit.touch-config.update'), ['kankaku' => [1 => 'abc'], 'send' => '更新'])->assertSessionHasErrors('kankaku.1');
    expect(DB::table('tb_touch_mode')->where('tb_shop_cd', 1)->value('interval_time'))->toBe('7200');
});

it('un staff que no es master solo ve y cambia su tienda', function () {
    DB::table('tb_touch_mode')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'mode' => '1', 'interval_time' => '1800', 'update_date' => '1'],
        ['cd' => 2, 'tb_shop_cd' => 2, 'mode' => '1', 'interval_time' => '1800', 'update_date' => '1'],
    ]);
    loginShopStaff();

    $this->get(route('admin.promocion.visit.touch-config'))
        ->assertOk()
        ->assertSee('name="kankaku[2]"', false)
        ->assertDontSee('name="kankaku[1]"', false)
        ->assertDontSee('<select id="zen_kankaku">', false);

    $this->post(route('admin.promocion.visit.touch-config.update'), ['kankaku' => [1 => 3600, 2 => 3600], 'send' => '更新'])->assertOk();
    expect(DB::table('tb_touch_mode')->where('tb_shop_cd', 1)->value('interval_time'))->toBe('1800')
        ->and(DB::table('tb_touch_mode')->where('tb_shop_cd', 2)->value('interval_time'))->toBe('3600');

    $this->get('/system/category3/visit/touch_config.php')->assertRedirect(route('admin.promocion.visit.touch-config'));
});
