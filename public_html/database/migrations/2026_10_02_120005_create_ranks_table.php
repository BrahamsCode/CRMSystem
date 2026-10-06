<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->smallInteger('type')->comment('1: por importe de compra, 2: por número de visitas');
            $table->text('name');

            // Según el type: importe mínimo/máximo, o número de visitas mínimo/máximo
            $table->integer('min_value')->default(0);
            $table->integer('max_value')->nullable()->comment('null = sin tope');

            // Prioridad: si dos rangos se solapan, gana el de menor sort
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'type']);
        });

        DB::statement('
            ALTER TABLE ranks ADD CONSTRAINT ranks_value_range_check
                CHECK (max_value IS NULL OR max_value >= min_value)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('ranks');
    }
};
