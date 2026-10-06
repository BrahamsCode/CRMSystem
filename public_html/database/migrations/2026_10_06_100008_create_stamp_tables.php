<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuración de la tarjeta de sellos, una fila por tienda (スタンプ仕様管理)
        Schema::create('stamp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained()->cascadeOnDelete();

            $table->integer('card_size')->nullable()->default(10)->comment('Sellos por tarjeta; null = sin límite');
            $table->integer('signup_bonus')->default(0)->comment('Sellos de regalo al darse de alta');
            $table->smallInteger('visit_stamp_flg')->default(1)->comment('0: no, 1: un sello por cada visita');
            $table->integer('interval_seconds')->default(3600)->comment('Tiempo mínimo entre dos sellos del mismo cliente');
            $table->smallInteger('display_mode')->default(1)
                ->comment('Cupones de canje en Mi página: 1 por tienda, 2 todos juntos, 3 solo la tienda de registro');
            $table->json('design')->nullable()->comment('Diseño de la tarjeta: {"icon": "star", "color": "#c8343a"}');

            $table->smallInteger('expiry_flg')->default(0)->comment('0: los sellos no vencen, 1: vencen');
            $table->integer('expiry_days')->nullable()->comment('Días de validez de cada sello');
            $table->smallInteger('expiry_hour')->default(3)->comment('Hora del proceso de vencimiento');
            $table->smallInteger('expiry_minute')->default(0);
            $table->smallInteger('notice_flg')->default(0)->comment('0: no, 1: avisar antes de que venzan');
            $table->integer('notice_days')->nullable();
            $table->smallInteger('notice_hour')->default(10);
            $table->smallInteger('notice_minute')->default(0);
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // Reglas de la tarjeta: al llegar a N sellos se entrega un cupón y se
        // avisa al cliente (スタンプ発行ルール + クーポンお知らせ機能)
        Schema::create('stamp_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->integer('stamp_count')->comment('Sellos acumulados que activan la regla');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('notify_flg')->default(1)->comment('0: no, 1: avisar al cliente por email o push');
            $table->text('message')->nullable()->comment('Texto del aviso; vacío = texto por defecto');
            $table->smallInteger('reset_flg')->default(0)->comment('0: la tarjeta sigue, 1: se reinicia al llegar a esta regla');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'stamp_count']);
        });

        // Movimientos de sellos: el saldo es la suma de quantity
        Schema::create('customer_stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_customer_id')->nullable()->constrained('coupon_customer')->nullOnDelete();

            $table->integer('quantity')->comment('Positivo: entrega, negativo: canje o vencimiento');
            $table->smallInteger('type')->comment('1: visita, 2: regalo de alta, 3: canje por cupón, 4: ajuste manual, 5: vencimiento, 6: reinicio de tarjeta');
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
        Schema::dropIfExists('customer_stamps');
        Schema::dropIfExists('stamp_rules');
        Schema::dropIfExists('stamp_settings');
    }
};
