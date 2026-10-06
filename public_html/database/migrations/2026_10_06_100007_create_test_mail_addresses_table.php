<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Direcciones para los envíos de prueba (テストメールアドレス管理)
        Schema::create('test_mail_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->text('name')->nullable();
            $table->text('mail');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'mail']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_mail_addresses');
    }
};
