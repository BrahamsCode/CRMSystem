<?php

use App\Services\Promotions\PointService;
use Illuminate\Support\Facades\DB;

/*
 * Point::exchangeSalesItem() y setPoint() con personal 0 (pedido de S5 para su API).
 */
beforeEach(function () {
    DB::table('hatuban')->insert(['koumoku' => 'tb_point_log', 'kazu' => 1]);
    DB::table('tb_customer')->insert(['cd' => 5, 'id' => 'A005', 'name' => '客', 'disp_flg' => '1']);
    DB::table('tb_customer_point')->insert(['cd' => 5, 'tb_customer_cd' => 5, 'service_point' => 100]);
    DB::table('sales_items')->insert([
        ['cd' => 1, 'name' => 'コーヒー', 'is_exchangeable_of_point' => 1, 'number_of_points_exchanged' => 30, 'available_shop_cds' => '{1,2}', 'disp_flg' => 1],
        ['cd' => 2, 'name' => '非交換', 'is_exchangeable_of_point' => 0, 'number_of_points_exchanged' => 10, 'available_shop_cds' => '{1}', 'disp_flg' => 1],
        ['cd' => 3, 'name' => '高い', 'is_exchangeable_of_point' => 1, 'number_of_points_exchanged' => 500, 'available_shop_cds' => '{1}', 'disp_flg' => 1],
    ]);
});

it('canjea un producto restando sus puntos con action_flg del legacy', function () {
    $result = app(PointService::class)->exchangeSalesItem(5, 1, 2);

    expect($result)->toBe(['status' => 'ok', 'res' => '30ポイントを削減しました。'])
        ->and(DB::table('tb_customer_point')->where('tb_customer_cd', 5)->value('service_point'))->toBe(70);
    $log = DB::table('tb_point_log')->first();
    expect($log)->action_flg->toBe('exchangeSalesItem:1:コーヒー')->tb_staff_cd->toBeNull()->shouhi_point->toBe(30)
        ->shori_mae_point->toBe(100)->tb_shop_cd->toBe(2)->tb_terminal_cd->toBe(-1);
});

it('responde los errores del legacy', function () {
    $points = app(PointService::class);

    expect($points->exchangeSalesItem('x', 1, 1))->toBe(['status' => 'ng', 'res' => '不正なリクエスト。'])
        ->and($points->exchangeSalesItem(9, 1, 1))->toBe(['status' => 'ng', 'res' => '顧客情報がありません。'])
        ->and($points->exchangeSalesItem(5, 2, 1))->toBe(['status' => 'ng', 'res' => '交換可能商品ではありません。'])
        ->and($points->exchangeSalesItem(5, 99, 1))->toBe(['status' => 'ng', 'res' => '交換可能商品ではありません。'])
        ->and($points->exchangeSalesItem(5, 1, 3))->toBe(['status' => 'ng', 'res' => '使用可能な店舗ではありません。'])
        ->and($points->exchangeSalesItem(5, 3, 1))->toBe(['status' => 'ng', 'res' => 'ポイントが不足です。'])
        ->and($points->setPoint(200, PointService::SUBTRACT, 1, null, 5))->toBe(['status' => 'ng', 'message' => '200ポイントを削減することは出来ません']);
    expect(DB::table('tb_point_log')->count())->toBe(0);
});
