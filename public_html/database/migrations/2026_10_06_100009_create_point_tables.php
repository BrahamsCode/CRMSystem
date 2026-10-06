<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuración de puntos, una fila por tienda (ポイント仕様管理)
        Schema::create('point_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained()->cascadeOnDelete();

            $table->smallInteger('expiry_flg')->default(0)->comment('0: sin vencimiento, 1: los puntos vencen');
            $table->smallInteger('extend_mode')->default(1)
                ->comment('Qué renueva la validez: 1 cualquier movimiento, 2 solo usar puntos');
            $table->integer('expiry_days')->nullable()->comment('0..999 días');
            $table->smallInteger('expiry_hour')->default(3);
            $table->smallInteger('expiry_minute')->default(0);
            $table->json('notices')->nullable()->comment('Avisos antes de vencer: [{"days": 7, "hour": 10, "minute": 0}], hasta 5');

            $table->integer('use_limit')->nullable()->comment('Máximo de puntos por uso; null = sin límite');
            $table->decimal('cash_rate', 5, 2)->default(1)->comment('% del importe en efectivo que vuelve en puntos');
            $table->decimal('card_rate', 5, 2)->default(1)->comment('% del importe con tarjeta que vuelve en puntos');
            $table->smallInteger('include_used_flg')->default(0)->comment('1: los puntos usados también generan puntos');

            $table->integer('referral_points')->default(0)->comment('Puntos para quien refiere');
            $table->integer('referral_limit')->nullable()->comment('Máximo de referidos que dan puntos; null = sin límite');
            $table->integer('referee_points')->default(0)->comment('Puntos para el referido');
            $table->text('referral_comment')->nullable();

            $table->integer('signup_points')->default(0);
            $table->smallInteger('signup_timing')->default(1)->comment('1: al darse de alta, 2: en la primera visita');
            $table->text('signup_comment')->nullable();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // Reglas de puntos por grupo de cliente (顧客グループごとのポイントルール)
        Schema::create('customer_group_point_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_group_id')->unique()->constrained()->cascadeOnDelete();
            $table->smallInteger('use_flg')->default(1)->comment('0: el grupo no acumula ni usa puntos, 1: sí');
            $table->smallInteger('display_flg')->default(1)->comment('0: no ve sus puntos en Mi página, 1: sí');
            $table->decimal('rate', 5, 2)->nullable()->comment('% de retorno propio del grupo; null = el de la tienda');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // Movimientos de puntos: el saldo es la suma de points
        Schema::create('customer_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_customer_id')->nullable()->constrained('coupon_customer')->nullOnDelete();

            $table->integer('points')->comment('Positivo: acumula, negativo: usa o vence');
            $table->smallInteger('type')->comment('1: compra, 2: alta, 3: referir, 4: ser referido, 5: canje por cupón, 6: uso en caja, 7: ajuste manual, 8: vencimiento');
            $table->timestamp('expires_at')->nullable();
            $table->text('note')->nullable();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_points');
        Schema::dropIfExists('customer_group_point_rules');
        Schema::dropIfExists('point_settings');
    }
};
