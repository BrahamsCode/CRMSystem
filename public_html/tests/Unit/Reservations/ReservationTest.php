<?php

use App\Models\Customers\Customer;
use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationSlot;
use App\Models\Reservations\SimpleReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Unit\Customers\LegacyFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    LegacyFixtures::shop(1);
    LegacyFixtures::shop(2, '別店舗');
    LegacyFixtures::staff(10, 1);
    LegacyFixtures::staff(20, 2);
});

function reservasSlot(int $staffCd, string $date, int $start = 36000, int $end = 68400): ReservationSlot
{
    return ReservationSlot::create([
        'tb_staff_cd' => $staffCd, 'moushikomi_date' => $date, 'start_time' => $start, 'end_time' => $end,
        'people' => 1, 'status_flg' => '', 'hyouji_flg' => '1', 'disp_flg' => '1',
    ]);
}

function reservasReserva(ReservationSlot $slot, array $attrs = []): Reservation
{
    return Reservation::create($attrs + [
        'tb_yoyaku_uketsuke_day_cd' => $slot->cd, 'start_time' => 39600, 'end_time' => 43200,
        'name' => '予約 太郎', 'mail' => 'guest@example.com', 'people' => 1, 'disp_flg' => '1', 'hyouji_flg' => '1',
    ]);
}

it('remindable() replica reminder_new_confirm.php: día, disp_flg 1, aviso no desactivado, socios y no socios', function () {
    $dia = Carbon::parse('2026-12-01', 'Asia/Tokyo');
    $franja = reservasSlot(10, '2026/12/01');
    $otraTienda = reservasSlot(20, '2026/12/01');
    $otroDia = reservasSlot(10, '2026/12/02');

    $socio = Customer::factory()->create(['stop_yoyaku_reminder_mail_flg' => '']);
    $sinAviso = Customer::factory()->create(['stop_yoyaku_reminder_mail_flg' => '1']);

    $deSocio = reservasReserva($franja, ['tb_customer_cd' => $socio->cd]);
    $deInvitado = reservasReserva($franja);
    $tienda2 = reservasReserva($otraTienda);
    reservasReserva($franja, ['tb_customer_cd' => $sinAviso->cd]);
    reservasReserva($franja, ['disp_flg' => '9']);
    reservasReserva($otroDia);

    expect(Reservation::remindable($dia)->pluck('cd')->sort()->values()->all())
        ->toBe(collect([$deSocio->cd, $deInvitado->cd, $tienda2->cd])->sort()->values()->all())
        ->and(Reservation::remindable($dia, 1)->pluck('cd')->sort()->values()->all())
        ->toBe(collect([$deSocio->cd, $deInvitado->cd])->sort()->values()->all());
});

it('calcula la hora de la reserva desde la franja, también pasada la medianoche', function () {
    $franja = reservasSlot(10, '2026/12/01');
    $r = reservasReserva($franja, ['start_time' => 86400 + 3600, 'end_time' => 86400 + 7200]);

    expect($r->reservedAt()->format('Y-m-d H:i'))->toBe('2026-12-02 01:00')
        ->and($r->endsAt()->format('Y-m-d H:i'))->toBe('2026-12-02 02:00');
});

it('nombre y correo de contacto: del socio si lo es, si no de la reserva', function () {
    $franja = reservasSlot(10, '2026/12/01');
    $socio = Customer::factory()->create(['name' => '山田 花子', 'email1' => 'hanako@example.com']);

    $deSocio = reservasReserva($franja, ['tb_customer_cd' => $socio->cd, 'name' => '別名', 'mail' => 'otro@example.com']);
    $invitado = reservasReserva($franja);

    expect($deSocio->displayName())->toBe('山田 花子')
        ->and($deSocio->contactMail())->toBe('hanako@example.com')
        ->and($invitado->displayName())->toBe('予約 太郎')
        ->and($invitado->contactMail())->toBe('guest@example.com');
});

it('upcomingCustomerCds() da los socios con reserva desde hoy (follow)', function () {
    $hoy = Carbon::parse('2026-12-01', 'Asia/Tokyo');
    $socio = Customer::factory()->create();
    $pasado = Customer::factory()->create();
    reservasReserva(reservasSlot(10, '2026/12/03'), ['tb_customer_cd' => $socio->cd]);
    reservasReserva(reservasSlot(10, '2026/11/20'), ['tb_customer_cd' => $pasado->cd]);

    expect(Reservation::upcomingCustomerCds($hoy)->pluck('tb_customer_cd')->map(fn ($v) => (int) $v)->all())->toBe([$socio->cd]);
});

it('numera tb_yoyaku con hatuban y cancela con disp_flg 9', function () {
    $r = reservasReserva(reservasSlot(10, '2026/12/01'));
    expect(DB::table('hatuban')->where('koumoku', 'tb_yoyaku')->value('kazu'))->toBe($r->cd + 1);

    $r->delete();
    expect(DB::table('tb_yoyaku')->where('cd', $r->cd)->value('disp_flg'))->toBe('9');
});

it('SimpleReservation usa tb_reserve con la columna update y reserve_time en epoch', function () {
    $t = Carbon::parse('2026-12-01 15:00', 'Asia/Tokyo');
    $r = SimpleReservation::create(['reserve_time' => (string) $t->getTimestamp(), 'shop_cd' => '1', 'reserve_flg' => SimpleReservation::FLG_CONFIRMED]);

    expect($r->reservedAt()->format('Y-m-d H:i'))->toBe('2026-12-01 15:00')
        ->and(is_numeric(DB::table('tb_reserve')->where('cd', $r->cd)->value('update')))->toBeTrue();
});
