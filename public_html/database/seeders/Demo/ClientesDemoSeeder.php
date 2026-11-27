<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

/**
 * Datos de demostración de S2 (clientes, ventas, reservas). Lo llama DemoSeeder (S0); aquí solo se llaman
 * en orden las partes, y las que aún no existan se saltan (como DemoSeeder). Cada parte es idempotente.
 *
 * Los socios demo se reconocen por `email1 LIKE 'demo%@example.com'` (demo1…demo300).
 */
class ClientesDemoSeeder extends Seeder
{
    /** Orden: catálogo → socios y visitas → ventas de esas visitas → reservas */
    public const SEEDERS = [
        Clientes\CatalogoDemoSeeder::class,  // D2: menús, descuentos, productos, 施術カテゴリー
        Clientes\SociosDemoSeeder::class,    // D1: socios, visitas, puntos, sellos, カルテ, álbum, campos propios
        Clientes\VentasDemoSeeder::class,    // D2: ventas ligadas a las visitas y gastos
        Clientes\ReservasDemoSeeder::class,  // D3: configuración de reservas y reservas
    ];

    public function run(): void
    {
        foreach (self::SEEDERS as $seeder) {
            if (! class_exists($seeder)) {
                $this->command?->warn("Saltado (aún no existe): {$seeder}");

                continue;
            }
            $this->call($seeder);
        }
    }
}
