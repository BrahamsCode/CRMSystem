<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();

            $table->text('name');
            $table->text('name_kana')->nullable();

            // Dirección
            $table->text('zip')->nullable();
            $table->text('pref')->nullable();
            $table->text('city')->nullable();
            $table->text('street_address')->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();

            // Contacto
            $table->text('tel')->nullable();
            $table->text('mail')->nullable();

            $table->text('invoice_registration_number')->nullable()->comment('R.U.C.');
            $table->smallInteger('online_flg')->default(0)->comment('0: no disponible en línea, 1: disponible');

            // Intervalo mínimo entre dos lecturas del mismo socio: el legacy lo
            // llama «カード重複読込時間間隔» y lo guarda en segundos (6 h por
            // defecto). Se conserva la unidad para que la migración sea directa.
            $table->integer('visit_interval_seconds')->default(21600)
                ->comment('Segundos mínimos entre dos visitas del mismo cliente');

            $table->text('uid')->unique();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
