<?php

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Services\Customers\VisitLoyalty;
use App\Services\Customers\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Unit\Customers\LegacyFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    LegacyFixtures::shop(1);
    $this->service = app(VisitService::class);
});

it('registra la visita en tb_raiten_rireki como pushRaitenRireki', function () {
    $customer = Customer::factory()->create(['kojin_birth' => '1990/06/15', 'kojin_sex' => '女性', 'job' => '会社員']);
    $now = Carbon::parse('2026-12-01 14:00', 'Asia/Tokyo');

    $visit = $this->service->register($customer, 1, now: $now);

    expect($visit)->toBeInstanceOf(Visit::class);
    $row = DB::table('tb_raiten_rireki')->where('cd', $visit->cd)->first();
    expect($row->user_cd)->toBe($customer->cd)
        ->and($row->user_id)->toBe($customer->id)
        ->and((int) $row->shop_cd)->toBe(1)
        ->and($row->raiten_time)->toBe((string) $now->getTimestamp())
        ->and($row->raiten_hiduke)->toBe('2026/12/01')
        ->and((int) $row->customer_age)->toBe(36)
        ->and($row->customer_sex)->toBe('女性')
        ->and($row->customer_job)->toBe('会社員')
        ->and($row->disp_flg)->toBeNull();

    expect(DB::table('tb_customer')->where('cd', $customer->cd)->value('last_raiten_date'))->toBe('2026/12/01');
    expect(DB::table('hatuban')->where('koumoku', 'raiten_rireki')->value('kazu'))->toBe($visit->cd + 1);
});

it('antes de las 05:00 cuenta como el día de negocio anterior', function () {
    $customer = Customer::factory()->create();

    $visit = $this->service->register($customer, 1, now: Carbon::parse('2026-12-02 04:30', 'Asia/Tokyo'));

    expect($visit->raiten_hiduke)->toBe('2026/12/01');
});

it('no registra dentro del intervalo mínimo de la tienda (tb_touch_mode)', function () {
    LegacyFixtures::visitInterval(1, 3600);
    $customer = Customer::factory()->create();
    $t = Carbon::parse('2026-12-01 10:00', 'Asia/Tokyo');

    expect($this->service->register($customer, 1, now: $t))->not->toBeNull()
        ->and($this->service->register($customer, 1, now: $t->copy()->addMinutes(30)))->toBeNull()
        ->and($this->service->register($customer, 1, now: $t->copy()->addMinutes(61)))->not->toBeNull();
});

it('recalcula shuki_heikin con las fechas distintas de visita', function () {
    $customer = Customer::factory()->create();
    foreach (['2026-11-01 10:00', '2026-11-11 10:00', '2026-11-21 10:00'] as $when) {
        $this->service->register($customer, 1, now: Carbon::parse($when, 'Asia/Tokyo'));
    }

    expect((int) DB::table('tb_customer')->where('cd', $customer->cd)->value('shuki_heikin'))->toBe(10);
});

it('marca manual_flg en las altas manuales y no hace nada con stampFlag 2', function () {
    $customer = Customer::factory()->create();

    $manual = $this->service->register($customer, 1, VisitService::ORIGIN_MANUAL, now: Carbon::parse('2026-12-01 10:00', 'Asia/Tokyo'));
    expect($manual->manual_flg)->toBe('1')
        ->and($this->service->register($customer, 1, stampFlag: '2'))->toBeNull();
});

it('las visitas con disp_flg NULL se ven y las de 9 no', function () {
    $customer = Customer::factory()->create();
    $visit = $this->service->register($customer, 1, now: Carbon::parse('2026-12-01 10:00', 'Asia/Tokyo'));

    expect($customer->visits()->count())->toBe(1);
    $visit->delete();
    expect($customer->visits()->count())->toBe(0)
        ->and(Visit::withDeleted()->count())->toBe(1);
});

it('delega sellos y puntos en VisitLoyalty', function () {
    $loyalty = new class implements VisitLoyalty
    {
        public array $calls = [];

        public function stampsForVisit(int $shopCd): int
        {
            return 2;
        }

        public function afterVisit($customer, int $shopCd, $visit, ?int $terminalCd, ?int $staffCd, string $stampFlag): void
        {
            $this->calls[] = [$customer->cd, $shopCd, $visit?->cd, $terminalCd, $stampFlag];
        }
    };
    app()->instance(VisitLoyalty::class, $loyalty);
    $customer = Customer::factory()->create();

    $visit = app(VisitService::class)->register($customer, 1, terminalCd: 7, now: Carbon::parse('2026-12-01 10:00', 'Asia/Tokyo'));

    expect($visit->stamp)->toBe(2)
        ->and($loyalty->calls)->toBe([[$customer->cd, 1, $visit->cd, 7, '']]);
});
