<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Datos de demostración para enseñar el CRM (la BD del legacy viene casi vacía: crm2019.backup
 * solo trae estructura y códigos postales).
 *
 * Uso (BD de la web, desde CRMSystem/):
 *   bash scripts/en-docker.sh php artisan db:seed --class=DemoSeeder
 *
 * Cada módulo pone su seeder en database/seeders/Demo/<Modulo>DemoSeeder.php; aquí solo se
 * llaman en orden de dependencias (los que aún no existen se saltan). Reglas comunes en
 * CONVENCIONES.md §4 «Datos de demostración».
 */
class DemoSeeder extends Seeder
{
    /** Orden: núcleo (tiendas, staff) → clientes (clientes, visitas, ventas, reservas) → el resto. */
    private const SEEDERS = [
        Demo\NucleoDemoSeeder::class,       // S1: tiendas, grupos, staff, permisos
        Demo\ClientesDemoSeeder::class,     // S2: clientes, visitas, puntos, sellos, productos, ventas, reservas
        Demo\PromocionDemoSeeder::class,    // S3: correos enviados, push, cupones, encuestas, DM, 新着情報
        Demo\ContenidoDemoSeeder::class,    // S4: マイコレ, オススメ, 求人, ガチャリン, señalización, ペーパーサイネージ
        Demo\OperacionesDemoSeeder::class,  // S5: turnos, 営業日, 勤怠, terminales
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder no se ejecuta en producción.');
        }

        mt_srand(20261127);
        fake()->seed(20261127);

        foreach (self::SEEDERS as $seeder) {
            if (! class_exists($seeder)) {
                $this->command?->warn("Saltado (aún no existe): {$seeder}");

                continue;
            }
            $this->call($seeder);
        }
    }
}
