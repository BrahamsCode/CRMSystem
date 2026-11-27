<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * オススメ管理 (tb_hp_recommend). Lo lee la app (appview/Util). El importador
 * conserva `id` = `cd` del legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('null = 全店舗 (tb_shop_cd 0/null)');
            $table->date('published_on')->nullable()->comment('Fecha que se muestra (disp_date)');
            $table->text('title');
            $table->text('body')->nullable()->comment('naiyou');
            $table->text('link_url')->nullable()->comment('Admite tel:');
            $table->smallInteger('link_type')->nullable()->comment('link_type del legacy');
            $table->text('image_path')->nullable()->comment('Ruta en el disco public (img_flg)');
            $table->smallInteger('image_type')->nullable()->comment('img_type del legacy');
            $table->text('image_link_url')->nullable()->comment('Enlace de la imagen (pic_link)');
            $table->smallInteger('push_flg')->default(0)->comment('0: sin push, 1: enviar push al publicar');
            $table->smallInteger('status')->default(1)->comment('0: 非表示 (inactivo), 1: 表示 (activo) — hyouji_flg');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'status', 'published_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
