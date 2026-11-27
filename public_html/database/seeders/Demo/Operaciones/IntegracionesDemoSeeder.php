<?php

namespace Database\Seeders\Demo\Operaciones;

use App\Models\ContentsMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lo que necesitan las API de S5 aparte de los socios, visitas, puntos y productos (S2/S3) y de la
 * configuración de マイページ (S4): el passcode de la tienda móvil de cada tienda (`POST /api/mobile_shop/passcode`),
 * como lo guarda 機能設定 (contents_master mobileshop). Uno empieza por cero: el legacy compara con `==`,
 * así que '04321' también acepta '4321'.
 *
 * Las claves (CRM_API_KEY de tool/api, MOBILESHOP_API_USER/PASS y las claves JWT) van en el .env, no aquí.
 */
class IntegracionesDemoSeeder extends Seeder
{
    public const PASSCODES = ['4321', '04321', '1234'];

    public function run(): void
    {
        $shops = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('cd');

        foreach ($shops as $i => $shopCd) {
            $exists = DB::table('contents_master')->where('category', 'mobileshop')
                ->where('field', 'mobileshop_auth_passcode')->where('int_data', $shopCd)->exists();
            if ($exists) {
                continue;
            }
            ContentsMaster::create([
                'category' => 'mobileshop', 'field' => 'mobileshop_auth_passcode',
                'text_data' => self::PASSCODES[min($i, count(self::PASSCODES) - 1)],
                'disp_flg' => '1', 'hyouji_flg' => '1', 'int_data' => (int) $shopCd,
            ]);
        }
    }
}
