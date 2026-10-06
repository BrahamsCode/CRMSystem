<?php

namespace Database\Seeders;

use App\Models\FieldSetting;
use App\Models\Shop;
use Illuminate\Database\Seeder;

/**
 * Siembra una fila de configuración por cada campo estándar del catálogo
 * (config/crm.php) y por cada tienda, usando los valores por defecto del código.
 *
 * Las posiciones marcadas con «x» (no aplica) o «R» (obligatorio fijo) no son
 * configurables, así que se guardan como 0 y la pantalla las pinta aparte.
 */
class FieldSettingSeeder extends Seeder
{
    public function run(): void
    {
        $columnas = ['mobile_display_flg', 'mobile_required_flg', 'search_flg', 'csv_flg'];
        $orden = 0;

        foreach (Shop::all() as $shop) {
            foreach (config('crm.customer_fields') as $filas) {
                foreach ($filas as [$id, $etiqueta, $codigo]) {
                    $valores = [];

                    foreach (str_split($codigo) as $i => $k) {
                        $valores[$columnas[$i]] = $k === '1' ? 1 : 0;
                    }

                    FieldSetting::firstOrCreate(
                        ['shop_id' => $shop->id, 'field_name' => $id],
                        $valores + ['sort' => ++$orden],
                    );
                }
            }

            foreach (config('crm.family_fields') as [$id, $etiqueta, $codigo]) {
                [$req, $busqueda, $csv] = str_split($codigo);

                FieldSetting::firstOrCreate(
                    ['shop_id' => $shop->id, 'field_name' => $id],
                    [
                        'mobile_display_flg' => 1,
                        'mobile_required_flg' => (int) $req,
                        'search_flg' => (int) $busqueda,
                        'csv_flg' => (int) $csv,
                        'sort' => ++$orden,
                    ],
                );
            }
        }

        $this->command?->info('FieldSettingSeeder: ' . FieldSetting::count() . ' campos configurados.');
    }
}
