<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plantillas de mensajes (テンプレート管理)
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('null = todas las tiendas');

            $table->smallInteger('category')
                ->comment('1: newsletter, 2: cumpleaños, 3: tras la visita, 4: tras el alta, 5: recordatorio de reserva, 6: push');
            $table->text('name');
            $table->text('subject')->nullable();
            $table->text('body');
            $table->smallInteger('html_flg')->default(0)->comment('0: texto, 1: HTML (デコメール)');
            $table->integer('sort')->default(0);
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
