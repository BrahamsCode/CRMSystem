<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Valores de las categorías de tipo múltiple (custom_categories.type = 2),
        // donde un cliente puede tener varios registros: por ejemplo, varias mascotas.
        //
        // Las categorías de tipo normal NO usan esta tabla: su valor vive en
        // customers.custom_data.
        Schema::create('custom_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_category_id')->constrained()->cascadeOnDelete();

            // jsonb por el índice GIN, igual que customers.custom_data
            $table->jsonb('values')->default('{}');
            $table->integer('row_no')->default(1)->comment('Nº de registro dentro de la categoría');

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['customer_id', 'custom_category_id', 'row_no']);
        });

        DB::statement('CREATE INDEX custom_values_values_gin ON custom_values USING gin (values)');
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_values');
    }
};
