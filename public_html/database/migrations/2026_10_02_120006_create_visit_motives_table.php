<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Motivos de primera visita (初回来店動機項目設定)
        Schema::create('visit_motives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->text('name');
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_motives');
    }
};
