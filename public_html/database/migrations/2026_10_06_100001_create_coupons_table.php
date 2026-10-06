<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cupones (マイクーポン). Tabla del estándar de la empresa —name, type y
        // value con la misma definición— más las columnas que necesita el
        // módulo de promociones.
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            // --- Columnas del estándar ---
            $table->text('name')->comment('Título del cupón');
            $table->smallInteger('type')->comment('1: porcentaje, 2: valor fijo');
            $table->integer('value')->comment('Descuento: % o importe en la unidad mínima de la moneda; mayor que 0');

            // --- Promociones ---
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('null = todas las tiendas');
            $table->smallInteger('usage_type')
                ->comment('1: newsletter, 2: canje por sellos, 3: premio de encuesta, 4: alta de socio, 5: referir a un amigo, 6: canje por puntos');
            $table->text('description')->nullable()->comment('Contenido que ve el cliente');
            $table->integer('cost')->default(0)->comment('Sellos o puntos que consume el canje (usage_type 2 y 6)');

            $table->smallInteger('validity_type')->default(2)
                ->comment('1: 1 semana desde la entrega, 2: 1 mes, 3: 3 meses, 4: N días, 5: hasta una fecha');
            $table->integer('validity_days')->nullable()->comment('validity_type 4');
            $table->date('valid_until')->nullable()->comment('validity_type 5');

            $table->smallInteger('reissue_flg')->default(0)->comment('0: una vez por cliente, 1: se puede volver a entregar');
            $table->smallInteger('lottery_rank')->nullable()
                ->comment('Premio del sorteo (ガチャ): 1 diamante, 2 oro, 3 plata, 4 bronce, 5 sin premio');
            $table->json('notes')->nullable()->comment('Hasta 5 avisos o condiciones de uso');
            $table->smallInteger('display_flg')->default(1)->comment('0: oculto en Mi página, 1: visible');
            $table->integer('sort')->default(0);

            $table->text('uid')->unique();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'usage_type']);
        });

        DB::statement('ALTER TABLE coupons ADD CONSTRAINT coupons_value_positive CHECK (value > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
