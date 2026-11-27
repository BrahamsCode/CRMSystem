<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * マイコレ: imágenes de la app (portada, banner, imagen HOME y miniaturas) y la
 * configuración de la app que se editaba en マイページデザイン管理.
 *
 * Reemplaza `contents_master` (category = 'tb_c6_user_hp') con los campos
 * aplicover, aplibanner, mainimage, apli_home_thumbnail (archivos numerados en
 * hp/uploads/c6/…), el `mypage_config` serializado y apli_customer_group_disp /
 * apli_home_comment. Ver Doc/diseno/contenido-terminales.md §2.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_images', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('section')->comment('1: portada (aplicover), 2: banner (aplibanner), 3: imagen HOME (mainimage), 4: miniatura (apli_home_thumbnail)');
            $table->integer('position')->comment('Hueco dentro de la sección (nº de archivo en el legacy)');
            $table->text('path')->nullable()->comment('Ruta en el disco public de Storage');
            $table->text('link_url')->nullable()->comment('Enlace del hueco (text_data separado por ||)');
            $table->smallInteger('banner_mode')->nullable()->comment('Solo banner: 1: デフォルト選択, 2: 自由選択');
            $table->smallInteger('all_shops_flg')->default(1)->comment('0: solo las tiendas de app_image_shop, 1: 全店舗');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['section', 'position']);
        });

        // Tiendas donde se muestra el hueco cuando no es 全店舗 (mypage_config[sección][hueco][shop_cd])
        Schema::create('app_image_shop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_image_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['app_image_id', 'shop_id']);
        });

        // Configuración de la app de la mypage (fila única)
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('customer_group_display_flg')->default(0)->comment('アプリで顧客グループを表示 (apli_customer_group_disp): 0: no, 1: sí');
            $table->text('home_comment')->nullable()->comment('Comentario de HOME (apli_home_comment)');
            $table->smallInteger('home_comment_flg')->default(0)->comment('mypage_config[comment_flg]: 0: no se muestra, 1: se muestra');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('app_image_shop');
        Schema::dropIfExists('app_images');
    }
};
