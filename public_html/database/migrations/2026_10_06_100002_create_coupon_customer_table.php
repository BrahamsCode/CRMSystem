<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cupones entregados a cada cliente. Tabla intermedia: los dos nombres en
        // singular y en orden alfabético, según el estándar.
        Schema::create('coupon_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // Envío con el que llegó, si vino adjunto a un mensaje
            $table->unsignedBigInteger('message_id')->nullable();

            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable()->comment('null = sin vencimiento');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_shop_id')->nullable()->constrained('shops')->nullOnDelete();

            $table->text('uid')->unique()->comment('Lo que ve el cliente en el enlace del cupón');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'used_at']);
            $table->index(['coupon_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_customer');
    }
};
