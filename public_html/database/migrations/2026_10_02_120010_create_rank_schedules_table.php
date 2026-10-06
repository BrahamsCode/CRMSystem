<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asignación automática de rangos (顧客ランク振り分け設定).
        // Una fila por tipo de rango y tienda.
        Schema::create('rank_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->smallInteger('type')->comment('1: por importe de compra, 2: por número de visitas');
            $table->smallInteger('enabled_flg')->default(0)->comment('0: desactivada, 1: activada');
            $table->integer('period_days')->nullable()->comment('Días hacia atrás a evaluar');
            $table->smallInteger('mode')->default(1)->comment('1: meses y días concretos, 2: días de la semana');

            // Solo se guardan y se leen enteros; no se consultan por contenido,
            // así que json basta (no hace falta jsonb).
            $table->json('months')->nullable()->comment('Meses seleccionados: [1..12]');
            $table->json('days')->nullable()->comment('Días del mes: [1..31] y "fin"');
            $table->json('weekdays')->nullable()->comment('Días de la semana: [1..7]');

            $table->smallInteger('run_hour')->default(0);
            $table->smallInteger('run_minute')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_schedules');
    }
};
