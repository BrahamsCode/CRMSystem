<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro de visitas (来店処理)
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete()->comment('Tienda visitada');
            $table->foreignId('visit_motive_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('visited_at');
            $table->integer('amount')->default(0)->comment('Importe de consumo, unidad mínima de la moneda');
            $table->text('note')->nullable();

            // Falta employee_id (quién procesó la visita): depende de que se
            // defina la tabla employees en el estándar de la empresa.

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'visited_at']);
            $table->index(['shop_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
