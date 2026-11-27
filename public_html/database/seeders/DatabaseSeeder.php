<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * La BD es la del legacy (database/schema/pgsql-schema.sql, generado desde crm2019.backup).
 * Los datos base del legacy (códigos postales, admin_master, hatuban…) se cargan con
 * `bash scripts/bd-legacy.sh <bd>`. Cada módulo añade aquí su <Modulo>Seeder de datos de prueba.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Seeders de cada módulo
        ]);
    }
}
