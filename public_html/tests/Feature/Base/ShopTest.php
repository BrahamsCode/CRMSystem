<?php

use App\Mail\Base\ShopCreatedMail;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

require_once __DIR__ . '/../Nucleo/helpers.php';

// 店舗情報管理 (system/category0/shop/*.php)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
});

function shopForm(array $overrides = []): array
{
    return $overrides + [
        'kaishamei_kana' => 'ウメダテン', 'kaishamei' => '梅田店', 'shop_category_cd' => '0', 'area_cd' => '',
        'is_closed' => '0', 'zip' => '5300001', 'pref' => '大阪府', 'add_kana' => '', 'add' => '大阪市北区梅田',
        'add_number' => '1-1', 'add_build_kana' => '', 'add_build' => '', 'tel' => '06-1234-5678', 'fax' => '',
        'shop_url' => 'http://example.com/', 'email' => 'umeda@example.com', 'tantou_kana' => 'タナカ', 'tantou' => '田中',
        'kyuyo_marume' => '15', 'start_work_time1' => '9', 'start_work_time2' => '30', 'break_time' => '60',
        'shiire_id' => '', 'shiire_pass' => '', 'latitude' => '', 'longitude' => '',
    ];
}

it('lista y busca tiendas, con el árbol de áreas', function () {
    DB::table('tb_area')->insert(['cd' => 5, 'name' => '関西', 'flg' => '1', 'disp_flg' => '1', 'row_lis' => 1]);
    DB::table('tb_shop')->insert([
        ['cd' => 2, 'kaishamei' => '梅田店', 'kaishamei_kana' => 'ウメダ', 'disp_flg' => '1', 'area_cd' => 5, 'email' => 'u@example.com', 'pos_domain' => 'x.pos-s.net'],
        ['cd' => 3, 'kaishamei' => '閉鎖店', 'kaishamei_kana' => 'ヘイサ', 'disp_flg' => '9', 'area_cd' => 0, 'email' => 'h@example.com', 'pos_domain' => null],
    ]);

    $this->get('/system/category0/shop/shop_list.php')
        ->assertOk()
        ->assertSee('<title>店舗一覧 -総合業務管理システム-</title>', false)
        ->assertSee('■店舗検索')
        ->assertSee('<span class="attention">2件</span>', false)
        ->assertSee('梅田店')
        ->assertSee('POS</span>', false)
        ->assertDontSee('閉鎖店')
        ->assertSee('data-area-code="5"', false)
        ->assertSee('id="area_check_btn_0_2"', false)
        ->assertDontSee('検索対象');

    $this->get('/system/category0/shop/shop_list.php?kaishamei=梅田')->assertSee('1件')->assertSee('梅田店');
    $this->get('/system/category0/shop/shop_list.php?mail=u@example')->assertSee('1件');
    $this->get('/system/category0/shop/shop_list.php?tb_shop_cds[]=1')->assertSee('1件')->assertSee('本店');
});

it('muestra 検索対象 y los borrados solo con IP de desarrollo', function () {
    config(['crm.stage' => 'docker']);
    DB::table('tb_shop')->insert(['cd' => 3, 'kaishamei' => '閉鎖店', 'disp_flg' => '9']);

    $this->get('/system/category0/shop/shop_list.php?disp_flg=9')->assertSee('検索対象')->assertSee('閉鎖店')->assertSee('1件');
    $this->get('/system/category0/shop/shop_list.php?disp_flg=all')->assertSee('2件');
});

it('muestra el detalle de una tienda', function () {
    DB::table('tb_shop')->where('cd', 1)->update(['tel' => '0612345678', 'start_work_time' => '9<>30', 'break_time' => '60']);

    $this->get('/system/category0/shop/shop_detail.php?shop_cd=1')
        ->assertOk()
        ->assertSee('■店舗・営業所詳細')
        ->assertSee('店舗・営業所 情報')
        ->assertSee('0612345678')
        ->assertSee('9時 30分')
        ->assertSee('value="修正する"', false)
        ->assertDontSee('value="削除する"', false);
});

it('valida en 確認 y vuelve a mostrar el formulario marcado', function () {
    config(['crm.stage' => 'docker']);

    $this->post('/system/category0/shop/shop_new_confirm.php', shopForm(['kaishamei' => '', 'zip' => '123', 'email' => 'mal', 'kaishamei_kana' => 'abc']))
        ->assertOk()
        ->assertSee('<td colspan="3" class="Ng"><input type="text" name="kaishamei"', false)
        ->assertSee('※必須入力項目です。')
        ->assertSee('※は半角数字7文字で入力して下さい。')
        ->assertSee('※メールアドレスが不適切な形式です。')
        ->assertSee('※全角カタカナで入力して下さい。')
        ->assertSee('value="この内容で確認する"', false);

    $this->post('/system/category0/shop/shop_new_confirm.php', shopForm(['kaishamei' => '本店']))
        ->assertSee('※既に同じ会社名/屋号が登録されています。');
});

it('da de alta una tienda: 確認 → 登録 (hatuban) → 完了, y avisa al soporte', function () {
    config(['crm.stage' => 'docker']);
    Mail::fake();
    DB::table('hatuban')->insert(['koumoku' => 'tb_shop', 'kazu' => 10]);

    $this->post('/system/category0/shop/shop_new_confirm.php', shopForm())
        ->assertOk()
        ->assertSee('value="この内容で登録する"', false)
        ->assertSee('<input type="hidden" name="tel" value="0612345678">', false);

    $this->post('/system/category0/shop/shop_new_register.php', shopForm(['tel' => '0612345678', 'act' => 'new', "kaishamei" => " 梅田'店 "]))
        ->assertOk()
        ->assertSee('URL=shop_new_complete.php?shop_cd=10', false)
        ->assertSee('登録中です。');

    $shop = Shop::find(10);
    expect($shop->kaishamei)->toBe('梅田’店')
        ->and($shop->disp_flg)->toBe('1')
        ->and($shop->start_work_time)->toBe('9<>30')
        ->and($shop->tel)->toBe('0612345678')
        ->and((int) $shop->touroku_date)->toBeGreaterThan(0);
    Mail::assertQueued(ShopCreatedMail::class);

    $this->get('/system/category0/shop/shop_new_complete.php?shop_cd=10')->assertSee('登録情報は以下の通りです。')->assertSee('梅田’店');
});

it('no deja dar de alta tiendas sin IP de desarrollo ni support_master, pero sí editar', function () {
    $this->get('/system/category0/shop/shop_new.php')->assertForbidden();
    $this->post('/system/category0/shop/shop_new.php', ['shop_cd' => 1])->assertOk()->assertSee('■店舗登録情報 修正')->assertSee('value="本店"', false);

    $this->post('/system/category0/shop/shop_new_register.php', shopForm(['shop_cd' => 1, 'act' => 'up', 'kaishamei' => '本店改']))->assertOk();
    expect(Shop::find(1)->kaishamei)->toBe('本店改')->and((int) Shop::find(1)->update_date)->toBeGreaterThan(0);
});

it('borra una tienda moviendo personal y clientes a la tienda 1, y la recupera', function () {
    config(['crm.stage' => 'docker']);
    DB::table('tb_shop')->insert(['cd' => 2, 'kaishamei' => '梅田店', 'disp_flg' => '1']);
    DB::table('tb_staff')->insert(['cd' => 9, 'shop_cd' => 2, 'login_id' => 's', 'login_pass' => 'p', 'disp_flg' => '1']);
    DB::table('tb_customer')->insert(['cd' => 7, 'tb_shop_cd' => 2]);

    $this->get('/system/category0/shop/shop_detail.php?shop_cd=2')->assertSee('value="削除する"', false);
    $this->post('/system/category0/shop/shop_delete_register.php', ['shop_cd' => 2, 'delete_flg' => 'del'])->assertOk()->assertSee('削除中です。');

    expect(DB::table('tb_shop')->where('cd', 2)->value('disp_flg'))->toBe('9')
        ->and(DB::table('tb_staff')->where('cd', 9)->value('shop_cd'))->toBe(1)
        ->and(DB::table('tb_customer')->where('cd', 7)->value('tb_shop_cd'))->toBe(1);

    $this->post('/system/category0/shop/shop_delete_register.php', ['shop_cd' => 2, 'delete_flg' => 'recover']);
    expect(DB::table('tb_shop')->where('cd', 2)->value('disp_flg'))->toBe('1');
});

it('busca la dirección por código postal en las URL zip.php', function () {
    DB::table('zip')->insert(['zip' => '5300001', 'pref1' => '大阪府', 'city' => 'ｵｵｻｶｼｷﾀｸ', 'town' => 'ｳﾒﾀﾞ', 'city1' => '大阪市北区', 'town1' => '梅田']);

    $this->get('/system/category0/shop/zip.php?zip=5300001')
        ->assertOk()
        ->assertExactJson(['zip_name' => '大阪府', 'town_name_kana' => 'オオサカシキタク ウメダ', 'town_name' => '大阪市北区 梅田']);
    $this->get('/system/category1/customer/zip.php?zip=0000000')->assertExactJson(['zip_name' => '', 'town_name_kana' => '', 'town_name' => '']);
});
