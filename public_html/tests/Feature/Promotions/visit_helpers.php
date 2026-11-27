<?php

use App\Services\Promotions\VisitStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';

/**
 * Datos de 来店履歴 para las pruebas de F7 (S4). Hora congelada: 2026-11-27 12:00 (Asia/Tokyo).
 * Tiendas 1 本店 y 2 支店; clientes 101 山田太郎 y 102 佐藤花子.
 * Día de negocio 2026-11-27 (05:00 → 04:59:59) en 本店: 4 visitas vigentes (2 de cada cliente) + 1 borrada.
 */
function seedVisits(): void
{
    test()->travelTo(CarbonImmutable::create(2026, 11, 27, 12, 0, 0, 'Asia/Tokyo'));

    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '支店', 'disp_flg' => '1']);
    DB::table('tb_customer')->insert([
        ['cd' => 101, 'id' => 'K101', 'kanri_id' => 'M-101', 'name' => '山田太郎', 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1],
        ['cd' => 102, 'id' => 'K102', 'kanri_id' => 'M-102', 'name' => '佐藤花子', 'disp_flg' => '1', 'touroku_kubun' => '2', 'tb_shop_cd' => 1],
    ]);

    $at = fn (int $m, int $d, int $h, int $i = 0) => (string) VisitStatsService::at(2026, $m, $d, $h, $i);
    $visit = fn (int $cd, int $user, int $shop, string $time, ?string $disp = null) => [
        'cd' => $cd, 'user_cd' => $user, 'user_id' => 'K'.$user, 'shop_cd' => $shop, 'raiten_time' => $time,
        'customer_age' => $user === 101 ? 35 : 22, 'customer_sex' => $user === 101 ? '男性' : '女性',
        'customer_job' => $user === 101 ? '会社員' : '学生', 'disp_flg' => $disp,
    ];
    DB::table('tb_raiten_rireki')->insert([
        $visit(1, 101, 1, $at(11, 27, 9, 10)),
        $visit(2, 101, 1, $at(11, 27, 9, 40)),
        $visit(3, 102, 1, $at(11, 27, 13)),
        $visit(4, 102, 1, $at(11, 28, 3)),        // madrugada: día de negocio 11/27
        $visit(5, 101, 2, $at(11, 27, 10)),       // otra tienda
        $visit(6, 101, 1, $at(11, 27, 11), '9'),  // borrada
        $visit(7, 102, 1, $at(10, 5, 10)),        // mes anterior
    ]);
}

/** Entra como un staff de la tienda 2 que no es master */
function loginShopStaff(): void
{
    DB::table('tb_staff')->insert([
        'cd' => 2, 'office_cd' => 1, 'shop_cd' => 2, 'login_id' => 'tenpo', 'login_pass' => 'secreto2', 'name' => '店長', 'disp_flg' => '1',
    ]);
    test()->get('/system/logout.php');
    loginAs('tenpo', 'secreto2');
}
