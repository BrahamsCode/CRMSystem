<?php

use Illuminate\Support\Facades\DB;

/*
 * appview/coupon_control.php → coupon_use.php: uso de un cupón con el CouponService de S3 (en crm2-php8 daba un
 * fatal y no lo usaba) e impresión pendiente. Datos de las fixtures de appview.
 */

const COUPON_FIXTURES = __DIR__.'/../../Fixtures/integraciones';
const COUPON_CUSTOMER = '1609897927001809158';

beforeEach(function () {
    foreach (['legacy-base.sql', 'legacy-seed.sql', 'appview-seed.sql'] as $file) {
        DB::unprepared(file_get_contents(COUPON_FIXTURES.'/'.$file));
    }
});

it('usar: mueve el cupón a tb_mycoupon_used con la tienda y la hora y coupon_use lo muestra con el socio de la sesión', function () {
    $this->freezeTime();
    $response = $this->post('/appview/coupon_control.php', [
        'act' => 'use', 'coupon_cd' => '10', 'my_coupon_cd' => '100', 'customer_cd' => COUPON_CUSTOMER, 'tb_shop_cd' => '1',
    ]);

    $response->assertStatus(302)->assertHeader('Location', 'coupon_use.php?PHPSESSID='.session()->getId());
    expect(DB::table('tb_mycoupon')->where('cd', 100)->exists())->toBeFalse();
    $used = DB::table('tb_mycoupon_used')->where('cd', 100)->first();
    expect($used->tb_shop_cd)->toBe(1)->and($used->used_date)->toBe((string) now()->getTimestamp());

    $body = $this->get('/appview/coupon_use.php')->assertOk()->getContent();
    expect($body)->toContain('使用日時：'.now()->format('Y/m/d H:i:s'))
        ->toContain('山田 太郎さんのﾏｲｸｰﾎﾟﾝです。')
        ->toContain('発行NO.100</font>')
        ->toContain('<a href="./coupon.php?customer_cd='.COUPON_CUSTOMER.'">一覧に戻る</a>');
});

it('imprimir: deja el cupón pendiente en la tienda elegida (print_flg) y lleva a coupon_print.php', function () {
    $this->post('/appview/coupon_control.php', [
        'act' => 'print', 'coupon_cd' => '11', 'my_coupon_cd' => '101', 'customer_cd' => COUPON_CUSTOMER, 'tb_shop_cd' => '2',
    ])->assertStatus(302)->assertHeader('Location', 'coupon_print.php?PHPSESSID='.session()->getId());

    $coupon = DB::table('tb_mycoupon')->where('cd', 101)->first();
    expect($coupon->print_flg)->toBe('2')->and($coupon->used_date)->not->toBeNull();
});

it('el listado borra los cupones caducados (hasta las 05:00 del día siguiente) de todos los socios', function () {
    DB::table('tb_mycoupon')->insert(['cd' => 103, 'tb_mycoupon_master_cd' => 10, 'tb_shop_cd' => 1, 'tb_customer_cd' => 1610793770001489815,
        'time_limit' => (string) (time() - 6 * 3600)]);
    DB::table('tb_mycoupon')->insert(['cd' => 104, 'tb_mycoupon_master_cd' => 10, 'tb_shop_cd' => 1, 'tb_customer_cd' => 1610793770001489815,
        'time_limit' => (string) (time() - 4 * 3600)]);

    $this->get('/appview/coupon.php?customer_cd='.COUPON_CUSTOMER)->assertOk();

    expect(DB::table('tb_mycoupon')->whereIn('cd', [102, 103, 104])->pluck('cd')->all())->toBe([104]);
});
