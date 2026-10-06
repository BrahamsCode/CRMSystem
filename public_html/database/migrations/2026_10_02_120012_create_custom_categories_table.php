<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorías de información adicional (顧客追加情報設定).
        // Ejemplos reales del legacy: Mascota, カルテ, 家族情報, sampleform, Ropa.
        Schema::create('custom_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->text('name');
            $table->text('slug')->comment('Clave dentro de customers.custom_data');
            $table->text('note')->nullable()->comment('Comentario');

            $table->smallInteger('columns')->default(1)->comment('Columnas al mostrar: 1, 2 o 3');
            $table->smallInteger('type')->default(1)
                ->comment('1: normal (un registro por cliente), 2: múltiple (varios registros)');
            $table->smallInteger('search_flg')->default(1)->comment('0: no, 1: aparece como filtro en Buscar clientes');
            $table->smallInteger('display_flg')->default(1)->comment('0: oculta, 1: visible en ficha y registro');
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'slug']);
        });

        DB::statement('
            ALTER TABLE custom_categories ADD CONSTRAINT custom_categories_columns_check
                CHECK (columns BETWEEN 1 AND 3)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_categories');
    }
};
