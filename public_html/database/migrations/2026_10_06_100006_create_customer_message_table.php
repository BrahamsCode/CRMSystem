<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Destinatarios de cada envío y lo que hicieron con él. De aquí salen la
        // tasa de clics y los desgloses por sexo, edad y ocupación.
        Schema::create('customer_message', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();

            $table->text('recipient')->nullable()->comment('Email o token de push al que se envió');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('opened_at')->nullable()->comment('Primera apertura (píxel en HTML, toque en push)');
            $table->timestamp('clicked_at')->nullable()->comment('Primer clic en un enlace');
            $table->integer('click_count')->default(0);

            $table->text('uid')->unique()->comment('Token de los enlaces de seguimiento');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['message_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_message');
    }
};
