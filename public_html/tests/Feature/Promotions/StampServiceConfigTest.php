<?php

use App\Services\Promotions\StampService;
use Illuminate\Support\Facades\DB;

/*
 * StampService::config() lee la fila que guarda スタンプ仕様管理 (tb_shop_cd = 1), aunque su cd no sea 1
 * (pedido de S0/S2: la pantalla F5 guarda por tb_shop_cd como stamp_config_register.php).
 */
it('lee 上限, 登録ボーナス e intervalo de la fila tb_shop_cd = 1', function () {
    DB::table('admin_master')->insert(['cd' => 1, 'stamp_shikkou_kikan' => '30']);
    DB::table('tb_stamp_config')->insert([
        ['cd' => 1, 'tb_shop_cd' => 2, 'limit_flg' => '60', 'stamp_max' => 5, 'touroku_bonus' => 0],
        ['cd' => 7, 'tb_shop_cd' => 1, 'limit_flg' => '3600', 'stamp_max' => 20, 'touroku_bonus' => 2],
    ]);

    expect(app(StampService::class)->config())->toMatchArray(['stamp_max' => 20, 'touroku_bonus' => 2, 'limit_flg' => 3600]);
});

it('sin fila de tb_shop_cd = 1 usa la de cd = 1 como cStamp', function () {
    DB::table('admin_master')->insert(['cd' => 1]);
    DB::table('tb_stamp_config')->insert(['cd' => 1, 'tb_shop_cd' => null, 'limit_flg' => '0', 'stamp_max' => 9, 'touroku_bonus' => 1]);

    expect(app(StampService::class)->config())->toMatchArray(['stamp_max' => 9, 'touroku_bonus' => 1]);
});
