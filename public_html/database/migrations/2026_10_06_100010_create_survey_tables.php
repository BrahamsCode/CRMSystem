<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encuestas (アンケート). Las preguntas usan los mismos tipos de campo que
        // la información adicional (App\Enums\CustomFieldType).
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('null = todas las tiendas');
            $table->text('name');
            $table->text('description')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete()->comment('Premio por responder');
            $table->text('thanks_message')->nullable();

            $table->text('uid')->unique();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->text('name');
            $table->smallInteger('type')->comment('App\\Enums\\CustomFieldType');
            $table->json('options')->nullable()->comment('Opciones para selección, botones y casillas');
            $table->smallInteger('required_flg')->default(0)->comment('0: opcional, 1: obligatoria');
            $table->integer('sort')->default(0);
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete()->comment('Envío desde el que respondió');
            // jsonb: los resultados se agregan por pregunta
            $table->jsonb('answers')->default('{}')->comment('{"<id de pregunta>": valor}');
            $table->timestamp('answered_at');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['survey_id', 'customer_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('survey_id')->references('id')->on('surveys')->nullOnDelete();
        });
        Schema::table('coupon_customer', function (Blueprint $table) {
            $table->foreign('message_id')->references('id')->on('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupon_customer', fn (Blueprint $table) => $table->dropForeign(['message_id']));
        Schema::table('messages', fn (Blueprint $table) => $table->dropForeign(['survey_id']));
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('surveys');
    }
};
