<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Envíos automáticos: seguimiento (フォロー), programados (自動) y
        // recordatorios de reserva (リマインダー). En el legacy son tres
        // pantallas y tablas; aquí es una sola con `trigger`.
        Schema::create('message_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('null = todas las tiendas');

            $table->text('name');
            $table->smallInteger('trigger')->comment(
                '1: días tras la última visita, 2: días tras el alta, 3: días antes del cumpleaños, '
                . '4: al cumplirse el ciclo de visita, 5: aniversario de boda, 6: meses y días concretos, '
                . '7: días de la semana, 8: días antes de una reserva'
            );
            $table->integer('days')->nullable()->comment('Desfase en días para los disparadores 1, 2, 3, 4, 5 y 8');
            $table->smallInteger('timing')->default(1)->comment('Disparador 5: 1 antes del aniversario, 2 después');
            $table->json('months')->nullable()->comment('Disparador 6: [1..12]');
            $table->json('month_days')->nullable()->comment('Disparador 6: [1..31] y "end" (fin de mes)');
            $table->json('weekdays')->nullable()->comment('Disparador 7: [0..6], 0 = domingo');
            $table->smallInteger('run_hour')->default(10)->comment('Hora de envío, 0..23');
            $table->smallInteger('run_minute')->default(0)->comment('Minuto de envío, 0..59');

            $table->smallInteger('channel')->default(1)->comment('1: email de texto, 2: email HTML, 3: notificación push');
            $table->text('subject')->nullable();
            $table->text('body');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete()->comment('Cupón que se entrega con el mensaje');
            $table->jsonb('filters')->default('{}')->comment('Filtros extra de CustomerSearch sobre los clientes del disparador');
            $table->smallInteger('mobile_only_flg')->default(0)->comment('Disparador 8: 1 = solo reservas hechas desde el móvil');

            $table->timestamp('last_run_at')->nullable();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_rules');
    }
};
