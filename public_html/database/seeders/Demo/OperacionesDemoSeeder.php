<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

/**
 * Datos de demostración de 業務支援管理 e integraciones (S5), reglas en CONVENCIONES.md §4. Va después de
 * NucleoDemoSeeder (tiendas, staff y permisos) y de los de clientes; funciona también solo con la tienda
 * y el staff del backup. Cada parte es idempotente:
 * - Personal: 所属, datos de la ficha del staff de S1, tarjetas, campos de búsqueda e インフォメーション.
 * - Asistencia: 営業日, turnos y 勤怠 con casos límite, kintai_tb_config.
 * - Integraciones: passcode de la tienda móvil.
 *
 * CTI, tool/api, mpa_* y appview leen los socios y visitas de S2; los terminales de señalización y
 * las カード de clientes son de S4.
 */
class OperacionesDemoSeeder extends Seeder
{
    /** Orden: personal, asistencia, integraciones */
    public const SEEDERS = [
        Operaciones\PersonalDemoSeeder::class,
        Operaciones\AsistenciaDemoSeeder::class,
        Operaciones\IntegracionesDemoSeeder::class,
    ];

    public function run(): void
    {
        $this->call(self::SEEDERS);
    }
}
