<?php

use App\Models\Customers\Customer;
use App\Models\Customers\CustomField;
use App\Models\Customers\CustomerSub;
use App\Services\Customers\CustomerFelicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Unit\Customers\LegacyFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(fn () => LegacyFixtures::shop(1));

it('numera con hatuban, pone touroku_date en epoch y borra con disp_flg 9', function () {
    $a = Customer::factory()->create();
    $b = Customer::factory()->create();

    expect($b->cd)->toBe($a->cd + 1)
        ->and(is_numeric($a->touroku_date))->toBeTrue();

    $a->delete();
    expect(DB::table('tb_customer')->where('cd', $a->cd)->value('disp_flg'))->toBe('9')
        ->and(Customer::find($a->cd))->toBeNull()
        ->and(Customer::withDeleted()->find($a->cd))->not->toBeNull();
});

it('parte el nombre por el primer espacio y calcula la edad desde kojin_birth', function () {
    $c = Customer::factory()->make(['name' => '山田　太郎', 'kojin_birth' => '2000/02/29']);

    expect($c->nameParts())->toBe(['山田', '太郎'])
        ->and($c->ageAt(\Illuminate\Support\Carbon::parse('2026-02-28', 'Asia/Tokyo')))->toBe(25)
        ->and(Customer::factory()->make(['kojin_birth' => '//'])->ageAt())->toBeNull();
});

it('CustomerSub separa valores, ventas y gastos y lee las casillas a<>b<>', function () {
    $c = Customer::factory()->create();
    CustomerSub::create(['tb_customer_cd' => $c->cd, 'tb_search_cate_cd' => 3, 'text_data' => 'A<>B<>', 'disp_flg' => '1']);
    CustomerSub::create(['tb_customer_cd' => $c->cd, 'tb_search_cate_cd' => -1, 'disp_flg' => '1']);
    CustomerSub::create(['tb_customer_cd' => $c->cd, 'tb_search_cate_cd' => -2, 'disp_flg' => '1']);

    expect(CustomerSub::customValues()->count())->toBe(1)
        ->and(CustomerSub::sales()->count())->toBe(1)
        ->and(CustomerSub::expenses()->count())->toBe(1)
        ->and(CustomerSub::customValues()->first()->checkedValues())->toBe(['A', 'B']);
});

it('CustomField devuelve las opciones de data/titles', function () {
    $field = new CustomField(['data' => '1<>2<>', 'titles' => '犬<>猫<>']);

    expect($field->options())->toBe(['1' => '犬', '2' => '猫']);
});

it('CustomerFelicaService asigna y libera la tarjeta como el legacy', function () {
    $c = Customer::factory()->create(['felica_no' => '']);
    DB::table('tb_user_card')->insert(['cd' => 1, 'felica_no' => 'IDM01', 'staff_cd' => '', 'card_flg' => '0']);
    DB::table('tb_customer_card')->insert(['cd' => 1, 'tid' => 'IDM01', 'tb_customer_cd' => $c->cd]);
    $service = app(CustomerFelicaService::class);

    $service->attach($c, 'IDM01');
    expect(DB::table('tb_customer')->where('cd', $c->cd)->value('felica_no'))->toBe('IDM01')
        ->and(DB::table('tb_user_card')->where('cd', 1)->value('staff_cd'))->toBe((string) $c->cd)
        ->and($service->findByIdm('IDM01')?->cd)->toBe($c->cd);

    $otro = Customer::factory()->create(['felica_no' => '']);
    expect(fn () => $service->attach($otro, 'IDM01'))->toThrow(DomainException::class);

    $service->detach($c);
    expect(DB::table('tb_customer')->where('cd', $c->cd)->value('felica_no'))->toBe('')
        ->and(DB::table('tb_user_card')->count())->toBe(0)
        ->and(DB::table('tb_customer_card')->where('cd', 1)->value('tb_customer_cd'))->toBeNull();
});
