<?php

use App\Models\Staff;
use App\Services\Customers\CustomerSearchForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Unit\Customers\LegacyFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    LegacyFixtures::shop(1, '本店');
    LegacyFixtures::shop(2, '支店');
    DB::table('admin_master')->insert(['cd' => 1, 'application_use_flg' => '1']);
    DB::table('tb_koumoku')->insert(['cd' => 1, 'tb_name' => 'tb_customer', 'tb_koumoku' => 'name', 'tb_koumoku_name' => '名前', 'mail_mag_select' => '1', 'rank' => 1]);
    DB::table('tb_search_cate')->insert(['cd' => 5, 'name' => 'ペット', 'category' => 'category1', 'serach_flg' => '1', 'hyouji_flg' => '1', 'disp_flg' => '1', 'rank' => 1]);
    DB::table('tb_search_form')->insert(['cd' => 7, 'tb_search_cate_cd' => 5, 'name' => '犬種', 'form_type' => 'テキスト', 'serach_flg' => '1', 'disp_flg' => '1', 'rank' => 1]);
    DB::table('tb_raiten_rireki')->insert(['cd' => 1, 'user_cd' => 1, 'shop_cd' => 1, 'raiten_time' => (string) \Illuminate\Support\Carbon::parse('2019-05-01', 'Asia/Tokyo')->getTimestamp()]);
});

it('prepara los datos de los parciales del buscador', function () {
    $data = app(CustomerSearchForm::class)->viewData(null, 'category1', ['name' => '山田']);

    expect($data['isMaster'])->toBeTrue()
        ->and($data['shops']->pluck('kaishamei')->all())->toBe(['本店', '支店'])
        ->and($data['searchItems']->first()['tb_koumoku'])->toBe('name')
        ->and($data['customSearchCategories']->first()['forms']->first()['name'])->toBe('犬種')
        ->and($data['applicationUseFlg'])->toBeTrue()
        ->and($data['minYear'])->toBe(2019)
        ->and($data['cellTag'])->toBe('th')
        ->and($data['filters'])->toBe(['name' => '山田']);
});

it('limita la tienda para el personal que no es master', function () {
    LegacyFixtures::staff(10, 2);
    $staff = Staff::find(10);

    $data = app(CustomerSearchForm::class)->viewData($staff, 'category3');

    expect($data['isMaster'])->toBeFalse()
        ->and($data['shopCd'])->toBe(2)
        ->and($data['shopName'])->toBe('支店')
        ->and($data['customSearchCategories'])->toHaveCount(0);
});

it('los parciales del buscador se pintan con esos datos', function () {
    View::share('errors', new \Illuminate\Support\ViewErrorBag);
    $data = app(CustomerSearchForm::class)->viewData();

    foreach (['search_set1', 'search_custom_set1', 'search_rireki_set1', 'search_kaikei_set1', 'search_method_set1'] as $partial) {
        expect(view('admin.clientes.partials.' . $partial, $data)->render())->toBeString();
    }
});
