<?php

namespace Tests\Unit\Customers;

use Illuminate\Support\Facades\DB;

/**
 * Filas mínimas de tablas del núcleo (tb_shop, tb_staff, tb_touch_mode) para las pruebas de S2,
 * insertadas con las columnas reales del esquema legacy.
 */
final class LegacyFixtures
{
    public static function shop(int $cd = 1, string $name = 'テスト店舗'): int
    {
        DB::table('tb_shop')->insert(['cd' => $cd, 'kaishamei' => $name, 'disp_flg' => '1']);

        return $cd;
    }

    public static function staff(int $cd, int $shopCd, string $name = '担当者'): int
    {
        DB::table('tb_staff')->insert([
            'cd' => $cd, 'shop_cd' => $shopCd, 'name' => $name, 'login_id' => 'staff' . $cd, 'disp_flg' => '1',
        ]);

        return $cd;
    }

    /** Intervalo mínimo entre visitas de socios de la tienda (tb_touch_mode, mode '1') */
    public static function visitInterval(int $shopCd, int $seconds): void
    {
        DB::table('tb_touch_mode')->insert(['cd' => $shopCd, 'tb_shop_cd' => $shopCd, 'mode' => '1', 'interval_time' => (string) $seconds]);
    }
}
