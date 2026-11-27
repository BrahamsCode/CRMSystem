<?php

use App\Services\Operaciones\LegacyHolidays;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

// 営業日設定 (config/shop_work_calendar.php) y la regla de festivos del legacy (check_holiday)

it('check_holiday del legacy: fijos, lunes móviles, equinoccios y 振替休日', function () {
    expect(LegacyHolidays::code(2026, 1, 1))->toBe(1)
        ->and(LegacyHolidays::code(2026, 1, 12))->toBe(2)   // 成人の日: 2.º lunes de enero
        ->and(LegacyHolidays::code(2026, 3, 20))->toBe(4)   // 春分の日
        ->and(LegacyHolidays::code(2026, 9, 23))->toBe(10)  // 秋分の日
        ->and(LegacyHolidays::code(2026, 9, 22))->toBe(16)  // 国民の休日 forzado
        ->and(LegacyHolidays::code(2025, 11, 24))->toBe(16) // lunes tras 勤労感謝の日
        ->and(LegacyHolidays::code(2026, 12, 23))->toBe(14) // 天皇誕生日 del legacy (sin actualizar)
        ->and(LegacyHolidays::code(2026, 8, 11))->toBe(0);  // 山の日 no existe en el legacy
});

it('営業日設定: calendario del mes, 休日 marcados y guardado por tienda', function () {
    seedOperaciones();
    DB::table('tb_base_system_child')->insert(['cd' => 52, 'tb_base_system_cd' => 5, 'system_name' => '初期設定管理', 'system_flg' => '1', 'rank' => 4]);
    DB::table('hatuban')->insert(['koumoku' => 'tb_shop_work', 'kazu' => 30]);
    // Otra tienda con fila del mismo mes: el legacy la confundía con la propia
    DB::table('tb_shop_work')->insert(['cd' => 7, 'shop_cd' => 9, 'syukkinbi' => '2026/2', 'syukkin_time_1' => '0']);
    loginAs('master', 'secreto1');

    $this->get('/admin/operaciones/shop-work?year=2026&mon=2')
        ->assertOk()
        ->assertSee('<title>営業日設定 - shop</title>', false)
        ->assertSee('<span class="text3L bold">2026年2月</span>', false)
        ->assertSee('&lt;&lt;&nbsp;2026年1月へ', false)
        ->assertSee('2026年3月へ&nbsp;&gt;&gt;', false)
        ->assertSee('<td><span class="holiday"><label for="11">11</label></span>', false) // 建国記念日
        ->assertSee('営業日数：28／28')
        ->assertSee('営業日設定'); // menú 初期設定管理

    $this->put('/admin/operaciones/shop-work', ['year' => '2026', 'mon' => '2', '1' => '0', '15' => '0'])
        ->assertRedirect('/admin/operaciones/shop-work?year=2026&mon=2');

    $row = DB::table('tb_shop_work')->where('shop_cd', 1)->where('syukkinbi', '2026/2')->first();
    expect($row->cd)->toBe(30)
        ->and($row->syukkin_time_1)->toBe('0')
        ->and($row->syukkin_time_2)->toBe('1')
        ->and($row->syukkin_time_15)->toBe('0')
        ->and(DB::table('tb_shop_work')->where('cd', 7)->value('syukkin_time_2'))->toBeNull();

    $this->get('/admin/operaciones/shop-work?year=2026&mon=2')
        ->assertSee('<td class="holidaySet">', false)
        ->assertSee('営業日数：26／28');

    // Mes inválido: el actual
    $this->get('/admin/operaciones/shop-work?year=2026&mon=13')->assertOk()
        ->assertSee(now('Asia/Tokyo')->year.'年'.now('Asia/Tokyo')->month.'月</span>', false);
});
