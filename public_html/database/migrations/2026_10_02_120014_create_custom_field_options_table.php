<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opciones de los campos de tipo select, radio y checkbox.
        // Ej. el campo «Tipo» de Mascota: Perro, Gato, Conejo, Hámster, Otros.
        Schema::create('custom_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();

            $table->text('label')->comment('Texto que se muestra');
            $table->text('value')->comment('Valor que se guarda');
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['custom_field_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_options');
    }
};
