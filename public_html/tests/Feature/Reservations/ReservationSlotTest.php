<?php

require_once __DIR__ . '/helpers.php';

use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// 受付日設定 (legacy: yoyaku_uketsuke_day.php, _register.php, _delete.php,
// yoyaku_multi_uketsuke_day.php, _register.php y ajax_uketsuke_status.php)

beforeEach(function () {
    reservasLogin();

    // 時間選択 y スタッフ別予約表 son de otra pantalla aún sin publicar: rutas de prueba para poder redirigir
    foreach (['admin.reservas.calendar-select' => '__test/calendar-select', 'admin.reservas.timetable.staff' => '__test/timetable-staff'] as $name => $uri) {
        if (! Route::has($name)) {
            Route::get($uri, fn () => '')->name($name);
        }
    }
    app('router')->getRoutes()->refreshNameLookups();

    // 予約設定 de la tienda 1: de 09:00 a 26:00 (franjas que pasan de medianoche)
    ReservationConfig::create(['tb_shop_cd' => 1, 'start_time' => '09:00', 'end_time' => '26:00']);
});

function slotInput(array $overrides = []): array
{
    return array_merge([
        'act' => 'new', 'staff' => 10, 'shop' => 1, 'yoyaku_date' => '20261126',
        'start_hour' => '10', 'start_min' => '00', 'end_hour' => '25', 'end_min' => '30',
        'people' => '3',
        'startBreakTime' => ['12:00', '', '15:10'], 'endBreakTime' => ['13:00', '', '15:40'],
    ], $overrides);
}

it('carga el alta de un día con el horario de la tienda', function () {
    $this->get(route('admin.reservas.slots.edit', ['staff' => 10, 'yoyaku_date' => '202611261000']))
        ->assertOk()
        ->assertSee('■受付日設定')
        ->assertSee('受付日登録情報')
        ->assertSee('山田')
        ->assertSee('2026年11月26日(木)')
        ->assertSee('この内容で登録する')
        ->assertSee('<option value="09" selected>09</option>', false)
        ->assertSee('<option value="26" selected>26</option>', false)
        ->assertDontSee('予約受付を削除する');
});

it('da de alta la franja de un día y la edita (horas en segundos, descansos HH:MM)', function () {
    $this->post(route('admin.reservas.slots.store'), slotInput())->assertRedirect();

    $slot = ReservationSlot::firstOrFail();
    expect($slot->tb_staff_cd)->toBe(10)
        ->and($slot->moushikomi_date)->toBe('2026/11/26')
        ->and($slot->start_time)->toBe(36000)
        ->and($slot->end_time)->toBe(25 * 3600 + 1800)
        ->and($slot->people)->toBe(3)
        ->and($slot->start_break_time)->toBe('12:00<>15:10')
        ->and($slot->end_break_time)->toBe('13:00<>15:40')
        ->and($slot->hyouji_flg)->toBe('1')
        ->and($slot->disp_flg)->toBe('1');
    expect(DB::table('log')->where('type2', 'tb_yoyaku_uketsuke_day')->where('type3', $slot->cd)->exists())->toBeTrue();

    // Redirección del legacy: ?cd=&lastact=reg, con 設定更新しました。 y el formulario de edición
    $this->get(route('admin.reservas.slots.edit', ['cd' => $slot->cd, 'lastact' => 'reg']))
        ->assertOk()
        ->assertSee('設定更新しました。')
        ->assertSee('この内容で修正する')
        ->assertSee('<option value="25" selected>25</option>', false)
        ->assertSee('予約受付を削除する');

    // Un segundo alta del mismo día actualiza la misma franja (legacy 2018.09.03)
    $this->post(route('admin.reservas.slots.store'), slotInput(['people' => '5', 'startBreakTime' => [], 'endBreakTime' => []]))->assertRedirect();
    expect(ReservationSlot::count())->toBe(1)
        ->and($slot->fresh()->people)->toBe(5)
        ->and($slot->fresh()->start_break_time)->toBe('');

    $this->post(route('admin.reservas.slots.store'), slotInput(['act' => 'up', 'cd' => $slot->cd, 'start_hour' => '11', 'start_min' => '30']))
        ->assertRedirect(route('admin.reservas.slots.edit', ['cd' => $slot->cd, 'lastact' => 'reg']));
    expect($slot->fresh()->start_time)->toBe(11 * 3600 + 1800);
});

it('vuelve a 時間選択 si viene de allí (kijun_time + yoyaku_date)', function () {
    $this->get(route('admin.reservas.slots.edit', ['staff' => 10, 'yoyaku_date' => '202611261000', 'kijun_time' => '1000', 'menu' => [5 => 7]]))
        ->assertOk()
        ->assertSee('&lt;&lt;時間設定に戻る', false)
        ->assertSee('menu[5]=7', false);

    $this->post(route('admin.reservas.slots.store'), slotInput(['kijun_time' => '1000', 'getData' => 'staff=10&kijun_time=1000']))
        ->assertRedirect(route('admin.reservas.calendar-select') . '?staff=10&kijun_time=1000&lastact=reg');
});

it('valida con los textos del legacy', function () {
    $this->post(route('admin.reservas.slots.store'), slotInput(['people' => '', 'end_hour' => '09']))
        ->assertSessionHasErrors(['people' => '「最大受付人数」が未入力です。']);
    $this->post(route('admin.reservas.slots.store'), slotInput(['start_hour' => '12', 'end_hour' => '11']))
        ->assertSessionHasErrors(['end_hour' => '開始時間と終了時間を確認してください。']);
    $this->post(route('admin.reservas.slots.store'), slotInput(['start_hour' => '08']))
        ->assertSessionHasErrors(['start_hour' => '出勤時間は09:00から26:00までの間にしてください。']);
    $this->post(route('admin.reservas.slots.store'), slotInput(['startBreakTime' => ['14:00'], 'endBreakTime' => ['13:00']]))
        ->assertSessionHasErrors(['startBreakTime' => '休憩・外出時間は異常です。(14:00 ～ 13:00)']);

    expect(ReservationSlot::count())->toBe(0);
});

it('lista las reservas de la franja y no deja borrarla mientras las tenga', function () {
    $slot = ReservationSlot::create(['tb_staff_cd' => 10, 'moushikomi_date' => '2026/11/26', 'start_time' => 36000, 'end_time' => 72000, 'people' => 2, 'disp_flg' => '1']);
    DB::table('tb_customer')->insert(['cd' => 50, 'name' => '佐藤花子', 'disp_flg' => '1']);
    Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $slot->cd, 'tb_customer_cd' => 50, 'tel' => '090-0000-0000', 'start_time' => 39600, 'end_time' => 43200, 'people' => 1, 'disp_flg' => '1']);
    Reservation::create(['tb_yoyaku_uketsuke_day_cd' => $slot->cd, 'name' => '非会員太郎', 'start_time' => 86400 + 1800, 'end_time' => 86400 + 3600, 'people' => 2, 'disp_flg' => '1']);

    $this->get(route('admin.reservas.slots.edit', ['cd' => $slot->cd]))
        ->assertOk()
        ->assertSee('■予約者一覧')
        ->assertSeeInOrder(['佐藤花子', '090-0000-0000', '11:00', '12:00', '1人', '非会員太郎', '00:30', '01:00', '2人'])
        ->assertDontSee('予約受付を削除する');

    $this->delete(route('admin.reservas.slots.destroy', $slot->cd))->assertStatus(409);
    expect(ReservationSlot::find($slot->cd))->not->toBeNull();
});

it('borra una franja sin reservas (DELETE físico como el legacy)', function () {
    $slot = ReservationSlot::create(['tb_staff_cd' => 10, 'moushikomi_date' => '2026/11/26', 'start_time' => 36000, 'end_time' => 72000, 'people' => 2, 'disp_flg' => '1']);

    $this->delete(route('admin.reservas.slots.destroy', $slot->cd), ['act' => 'delete', 'cd' => $slot->cd])
        ->assertOk()
        ->assertSee('予約受付削除しました。');

    expect(ReservationSlot::withDeleted()->find($slot->cd))->toBeNull();
});

it('carga el alta de varios días y la guarda (crea los nuevos y sobrescribe los existentes)', function () {
    $existing = ReservationSlot::create(['tb_staff_cd' => 10, 'moushikomi_date' => '2026/11/27', 'start_time' => 36000, 'end_time' => 72000, 'people' => 2, 'disp_flg' => '1']);
    $touroku = $existing->fresh()->touroku_date;

    $this->get(route('admin.reservas.slots.bulk', ['shop' => 1, 'staff' => 10, 'd' => '20261101', 'days' => ['2026/11/26', '2026/11/27']]))
        ->assertOk()
        ->assertSee('■受付日設定')
        ->assertSee('2026/11/26(木)', false)
        ->assertSee('2026/11/27(金)', false)
        ->assertSee('name="days[]" value="2026/11/27"', false);

    $this->post(route('admin.reservas.slots.bulk.store'), [
        'act' => 'reg', 'shop' => 1, 'staff' => 10, 'd' => '20261101', 'bd' => '20261001',
        'days' => ['2026/11/26', '2026/11/27'],
        'start_hour' => '09', 'start_min' => '30', 'end_hour' => '18', 'end_min' => '00', 'people' => '4',
    ])->assertRedirect(route('admin.reservas.timetable.staff', ['shop' => 1, 'staff' => 10, 'd' => '20261101', 'bd' => '20261001']));

    $slots = ReservationSlot::query()->orderBy('moushikomi_date')->get();
    expect($slots)->toHaveCount(2)
        ->and($slots->pluck('moushikomi_date')->all())->toBe(['2026/11/26', '2026/11/27'])
        ->and($slots->pluck('start_time')->unique()->all())->toBe([34200])
        ->and($slots->pluck('people')->unique()->all())->toBe([4])
        ->and($slots[1]->cd)->toBe($existing->cd)
        ->and($slots[1]->touroku_date)->toBe($touroku);
});

it('cambia status_flg como ajax_uketsuke_status.php (cuerpo vacío si va bien)', function () {
    $slot = ReservationSlot::create(['tb_staff_cd' => 10, 'moushikomi_date' => '2026/11/26', 'start_time' => 36000, 'end_time' => 72000, 'people' => 2, 'disp_flg' => '1']);
    $sessionId = Str::random(40);

    $this->withCookie(config('session.cookie'), $sessionId)
        ->get(route('admin.reservas.slots.status', ['td' => '20261126', 'sess' => $sessionId, 'staffCd' => '10', 'statusFlg' => '2']))
        ->assertOk()
        ->assertContent('');

    expect($slot->fresh()->status_flg)->toBe('2');
    expect(DB::table('log')->where('type2', 'tb_yoyaku_uketsuke_day')->where('type3', 0)->exists())->toBeTrue();

    $this->get(route('admin.reservas.slots.status', ['td' => '20261126', 'sess' => 'otra', 'staffCd' => '10', 'statusFlg' => '1']))
        ->assertOk()
        ->assertContent('不正アクセス！！');
    $this->get(route('admin.reservas.slots.status', ['td' => 'x', 'sess' => $sessionId, 'staffCd' => '10']))
        ->assertContent('不正アクセス(td)');

    expect($slot->fresh()->status_flg)->toBe('2');
});
