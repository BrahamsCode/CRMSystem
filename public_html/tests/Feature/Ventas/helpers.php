<?php

use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';

/** スタッフ de la tienda 1 con 歩合 (kingaku_buai) */
function ventasStaff(int $cd = 2, array $attributes = []): void
{
    DB::table('tb_staff')->insert($attributes + [
        'cd' => $cd, 'office_cd' => 1, 'shop_cd' => 1, 'login_id' => 'staff'.$cd, 'login_pass' => 'x',
        'name' => "担当{$cd}", 'kingaku_buai' => 10, 'kingaku_kihon' => 0, 'rank' => $cd, 'disp_flg' => '1',
    ]);
}

/** Socio (touroku_kubun 2) de la tienda 1 */
function ventasCustomer(int $cd, array $attributes = []): void
{
    DB::table('tb_customer')->insert($attributes + [
        'cd' => $cd, 'id' => sprintf('M%04d', $cd), 'name' => "顧客{$cd}", 'name_kana' => 'コキャク',
        'tb_shop_cd' => 1, 'touroku_kubun' => '2', 'disp_flg' => '1',
    ]);
}

function ventasMenu(int $cd, string $name, int $kingaku, string $type = '1', int $rank = 1, int $shop = 1): ServiceMenu
{
    return ServiceMenu::query()->create([
        'cd' => $cd, 'tb_shop_cd' => $shop, 'name' => $name, 'kingaku' => $kingaku, 'type' => $type,
        'rank' => $rank, 'disp_flg' => '1', 'hyouji_flg' => '1',
    ]);
}

function ventasDiscount(int $cd, string $name, int $value, string $type, string $buai, int $rank = 1): Discount
{
    return Discount::query()->create([
        'cd' => $cd, 'tb_shop_cd' => 1, 'name' => $name, 'value' => $value, 'type' => $type, 'buai_flg' => $buai,
        'rank' => $rank, 'disp_flg' => '1', 'hyouji_flg' => '1',
    ]);
}

/** POST del 確定 de 売上データ作成 */
function ventasStoreSale(array $data)
{
    return test()->post(route('admin.ventas.sales.store'), $data + [
        'shop' => 1, 'update_date' => '2026/11/20', 'add_record_verify' => (string) random_int(1, PHP_INT_MAX),
    ]);
}
