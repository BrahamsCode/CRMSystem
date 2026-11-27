<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * La BD es la del legacy (database/schema/pgsql-schema.sql, generado desde crm2019.backup).
 * Los datos base del legacy (códigos postales, admin_master, hatuban…) se cargan con
 * `bash scripts/bd-legacy.sh <bd>`. Los datos de prueba de cada módulo van en Demo/<Modulo>DemoSeeder
 * y se cargan con `php artisan db:seed` (o `--class=DemoSeeder`).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Datos de prueba de todos los módulos (Demo/<Modulo>DemoSeeder, en orden de dependencias)
        $this->call(DemoSeeder::class);
    }
}
