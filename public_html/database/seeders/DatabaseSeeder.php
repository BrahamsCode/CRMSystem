<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sin WithoutModelEvents a propósito: los hooks `creating` de los modelos son los
 * que generan `uid` y el Nº de socio, y ese trait los silenciaría.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,           // acceso a /admin/login
            ShopSeeder::class,            // tiendas
            CatalogSeeder::class,         // grupos, motivos y rangos
            CustomCategorySeeder::class,  // información adicional
            FieldSettingSeeder::class,    // configuración de campos y CSV
            CustomerSeeder::class,        // clientes de prueba
        ]);
    }
}
