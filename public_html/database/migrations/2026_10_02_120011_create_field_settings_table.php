<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuración de campos de registro, búsqueda y CSV (登録・検索項目設定).
        // Una fila por cada campo estándar de customers.
        Schema::create('field_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->text('field_name')->comment('Nombre de la columna de customers');
            $table->smallInteger('mobile_display_flg')->default(0)->comment('0: oculto, 1: visible en el registro móvil');
            $table->smallInteger('mobile_required_flg')->default(0)->comment('0: opcional, 1: obligatorio en el registro móvil');
            $table->smallInteger('search_flg')->default(0)->comment('0: no, 1: aparece como filtro de búsqueda');
            $table->smallInteger('csv_flg')->default(0)->comment('0: no, 1: se incluye al exportar CSV');
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_settings');
    }
};
