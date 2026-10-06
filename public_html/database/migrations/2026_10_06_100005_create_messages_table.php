<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Envíos: newsletter, decomail (HTML) y push (お知らせ配信 / プッシュ通知).
        // También los que genera una regla automática, con message_rule_id.
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('Tienda que publica; null = todas');
            $table->foreignId('footer_shop_id')->nullable()->constrained('shops')->nullOnDelete()->comment('Tienda del pie del mensaje');
            $table->foreignId('message_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete()->comment('Cupón adjunto');
            $table->foreignId('survey_id')->nullable()->comment('Encuesta enlazada');

            $table->smallInteger('channel')->comment('1: email de texto, 2: email HTML, 3: notificación push');
            $table->text('subject');
            $table->text('body');
            $table->jsonb('filters')->default('{}')->comment('Filtros de CustomerSearch que eligen a los destinatarios');
            $table->text('note')->nullable();

            $table->smallInteger('delivery_status')->default(1)
                ->comment('1: borrador, 2: programado, 3: enviando, 4: enviado, 5: cancelado');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('recipient_count')->default(0);

            $table->text('uid')->unique();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['delivery_status', 'scheduled_at']);
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
