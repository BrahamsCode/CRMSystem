<?php

use App\Mail\Promotions\StampCouponNoticeMail;
use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Services\Customers\VisitLoyalty;
use App\Services\Promotions\PointService;
use App\Services\Promotions\PromocionVisitLoyalty;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// Sellos y puntos de la visita (cUtility::raitenFunc) y Point::setPoint, sobre las tablas del legacy

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00', 'Asia/Tokyo'));
    DB::table('admin_master')->insert(['cd' => 1, 'application_use_flg' => 1, 'point_shikkou_kikan' => '30', 'stamp_shikkou_kikan' => '10']);
    DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1',
        'login_default_point_settings' => serialize(['useFlg' => '1', 'earnPoints' => 5])]);
    DB::table('tb_stamp_config')->insert(['cd' => 1, 'stamp_max' => 10, 'limit_flg' => '3600']);
    DB::table('tb_customer')->insert(['cd' => 7, 'touroku_kubun' => '2', 'disp_flg' => '1', 'email1' => 'kaiin@example.com', 'stamp_count' => 8, 'last_stamp_time' => 0]);
    $this->customer = fn () => Customer::find(7);
    $this->visit = fn () => new Visit(['cd' => 1]);
    $this->loyalty = app(VisitLoyalty::class);
});

afterEach(fn () => Carbon::setTestNow());

it('S3 enlaza su implementación de VisitLoyalty', function () {
    expect($this->loyalty)->toBeInstanceOf(PromocionVisitLoyalty::class);
});

it('da los sellos de la regla sin pasar del máximo, deja el log y avisa del cupón', function () {
    Mail::fake();
    DB::table('tb_stamp_rules')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'stamp_count' => 3, 'hyouji_flg' => '1']);
    DB::table('tb_mycoupon_master')->insert(['cd' => 4, 'title' => 'ドリンク無料', 'shouhi_point' => 10, 'attention' => '1回限り<>他券併用不可', 'disp_flg' => '1']);
    DB::table('tb_stamp_oshirase_coupon')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'stamp_count' => 10, 'tb_mycoupon_master_cd' => '4', 'naiyou' => 'スタンプが貯まりました', 'disp_flg' => '1', 'hyouji_flg' => '1']);

    expect($this->loyalty->stampsForVisit(1))->toBe(3);

    $this->loyalty->afterVisit(($this->customer)(), 1, ($this->visit)(), 5, null, '');

    $row = DB::table('tb_customer')->where('cd', 7)->first();
    $log = DB::table('tb_stamp_log')->first();
    expect($row->stamp_count)->toBe(10)
        ->and((int) $row->last_stamp_time)->toBe(now()->getTimestamp())
        ->and((int) $row->stamp_yukoukigen)->toBe(Carbon::parse('2026-05-29 23:59:59', 'Asia/Tokyo')->getTimestamp())
        ->and($log->shori_mae_stamp)->toBe(8)
        ->and($log->sounyu_stamp)->toBe(2)
        ->and($log->raiten_shori)->toBe('1')
        ->and($log->tb_terminal_cd)->toBe(5);

    Mail::assertSent(StampCouponNoticeMail::class, fn ($mail) => $mail->hasTo('kaiin@example.com')
        && str_contains($mail->body, '■ドリンク無料') && str_contains($mail->body, '他券併用不可'));
});

it('no da sellos dentro del intervalo, con el flag de visita sin sello ni si raiten_stamp_flg = 2', function () {
    DB::table('tb_customer')->where('cd', 7)->update(['last_stamp_time' => now()->getTimestamp() - 60]);
    $this->loyalty->afterVisit(($this->customer)(), 1, ($this->visit)(), null, null, '');
    expect(DB::table('tb_stamp_log')->count())->toBe(0);

    DB::table('tb_customer')->where('cd', 7)->update(['last_stamp_time' => 0]);
    $this->loyalty->afterVisit(($this->customer)(), 1, ($this->visit)(), null, null, '1');
    expect(DB::table('tb_stamp_log')->count())->toBe(0);

    DB::table('admin_master')->where('cd', 1)->update(['raiten_stamp_flg' => '2']);
    $this->loyalty->afterVisit(($this->customer)(), 1, ($this->visit)(), null, null, '');
    expect(DB::table('tb_stamp_log')->count())->toBe(0);
});

it('da puntos de check-in por la regla × earnPoints solo si hubo visita', function () {
    DB::table('tb_point_rules')->insert(['cd' => 1, 'tb_shop_cd' => 1, 'type_flg' => '1', 'start_time' => 0, 'end_time' => 2359, 'point_count' => 2, 'hyouji_flg' => '1']);

    $this->loyalty->afterVisit(($this->customer)(), 1, null, null, null, '1');
    expect(DB::table('tb_point_log')->count())->toBe(0);

    $this->loyalty->afterVisit(($this->customer)(), 1, ($this->visit)(), null, null, '1');

    $log = DB::table('tb_point_log')->first();
    expect(app(PointService::class)->balance(7))->toBe(10)
        ->and($log->action_flg)->toBe('mycolle:checkIn')
        ->and($log->sounyu_point)->toBe(10)
        ->and($log->shori_mae_point)->toBe(0);
});

it('sin la app activa no da puntos de check-in', function () {
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => 0]);

    $this->loyalty->afterVisit(($this->customer)(), 1, ($this->visit)(), null, null, '1');

    expect(DB::table('tb_point_log')->count())->toBe(0);
});

it('setPoint suma, resta, no deja saldo negativo y renueva la caducidad', function () {
    $service = app(PointService::class);

    expect($service->setPoint(100, PointService::ADD, 1, 3, 7)['status'])->toBe('ok')
        ->and($service->setPoint(30, PointService::SUBTRACT, 1, 3, 7)['status'])->toBe('ok')
        ->and($service->setPoint(500, PointService::SUBTRACT, 1, 3, 7))->toBe(['status' => 'ng', 'message' => '500ポイントを削減することは出来ません'])
        ->and($service->balance(7))->toBe(70);

    $balance = DB::table('tb_customer_point')->where('tb_customer_cd', 7)->first();
    $logs = DB::table('tb_point_log')->orderBy('cd')->get();
    expect($balance->cd)->toBe(7)
        ->and((int) $balance->point_yukoukigen)->toBe(Carbon::parse('2026-06-18 23:59:59', 'Asia/Tokyo')->getTimestamp())
        ->and($logs)->toHaveCount(2)
        ->and($logs[0]->sounyu_point)->toBe(100)
        ->and($logs[1]->shouhi_point)->toBe(30)
        ->and($logs[1]->shori_mae_point)->toBe(100)
        ->and($logs[1]->tb_staff_cd)->toBe(3);
});
