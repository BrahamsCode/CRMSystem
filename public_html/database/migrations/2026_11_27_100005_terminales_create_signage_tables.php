<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 電子看板管理 (category7/digital_signage): sistemas, programas, displays y
 * materiales. Las listas `<>` del legacy pasan a tablas pivote con `position`.
 * El importador conserva `id` = `cd` del legacy en displays, programas y
 * materiales: el reproductor pide displays y programas por número
 * (?display_cd=, hp/uploads/c7/display/<cd>.xml) y el XML lleva el cd de cada
 * material (<p cd=…>). Sistemas y pivotes van por legacy_id_map (S1).
 */
return new class extends Migration
{
    public function up(): void
    {
        // tb_dispaly_systems (sic): sistemas integrados que puede mostrar la pantalla
        Schema::create('signage_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->text('name');
            $table->smallInteger('system_type')->nullable()->comment('system_type del legacy');
            $table->smallInteger('system_flg')->nullable()->comment('system_flg del legacy');
            $table->smallInteger('non_coupon_flg')->default(0)->comment('0: con cupón, 1: sin cupón');
            $table->integer('sort')->default(0);
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // tb_c7_movie_program: 放映プログラム
        Schema::create('signage_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->text('name');
            $table->text('comment')->nullable();
            $table->text('terminal_code')->nullable()->comment('terminal_id del legacy');
            $table->jsonb('terminal_types')->nullable()->comment('terminal_type (lista <>)');
            $table->smallInteger('content_mode')->default(1)->comment('disp_conf: 1: 放映ファイル選択, 2: システム内蔵');
            $table->smallInteger('action_flg')->default(0)->comment('act_flg: 0: no, 1: sí');
            $table->smallInteger('member_flg')->default(0)->comment('kaiin_flg: 0: no, 1: sí');
            $table->smallInteger('felica_action_flg')->default(0)->comment('feli_act_flg: 0: no, 1: sí');
            $table->smallInteger('mypage_action_flg')->default(0)->comment('mypage_act_flg: 0: no, 1: sí');
            $table->text('ticker_text')->nullable()->comment('テロップ (telop_comment)');
            $table->text('vertical_setting')->nullable();
            $table->integer('sort')->default(0)->comment('rank');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // tb_display: ディスプレイ (pantalla física que reproduce un programa)
        Schema::create('signage_displays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('signage_program_id')->nullable()->constrained()->nullOnDelete()->comment('tb_c7_movie_program_cds (el formulario elige uno)');
            $table->text('name');
            $table->text('comment')->nullable();
            $table->text('terminal_code')->nullable()->comment('terminal_id del legacy');
            $table->jsonb('terminal_types')->nullable()->comment('terminal_type (lista <>): orientación del reproductor');
            $table->smallInteger('content_mode')->default(1)->comment('disp_conf: 1: 放映ファイル選択, 2: システム内蔵');
            $table->smallInteger('action_flg')->default(0)->comment('act_flg: 0: no, 1: sí');
            $table->smallInteger('member_flg')->default(0)->comment('kaiin_flg: 0: no, 1: sí');
            $table->integer('sort')->default(0)->comment('rank');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // tb_screensaver: 放映素材 (imagen o vídeo con duración)
        Schema::create('signage_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('signage_display_id')->nullable()->constrained()->nullOnDelete()->comment('tb_display_cd');
            $table->text('title');
            $table->text('comment')->nullable();
            $table->smallInteger('file_type')->comment('filetype: 1: 画像ファイル, 2: FLVファイル, 3: SWFファイル');
            $table->jsonb('images')->nullable()->comment('Huecos de imagen [{"flg":"1","ext":"jpg","path":"…"}] (pic_flg / pic_extension <>)');
            $table->integer('duration_seconds')->nullable()->comment('play_frame_sec');
            $table->smallInteger('material_type')->nullable()->comment('type_flg del legacy');
            $table->text('data')->nullable()->comment('data del legacy');
            $table->smallInteger('select_flg')->nullable()->comment('select_flg del legacy');
            $table->text('category_value')->nullable()->comment('categorize_value_0');
            $table->integer('sort')->default(0)->comment('rank (先頭に追加 = 0, 最後に追加 = max + 1)');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // tb_screensaver_cds del programa
        Schema::create('signage_material_signage_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signage_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signage_program_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0)->comment('Orden de reproducción');
            $table->timestamps();

            $table->index(['signage_program_id', 'position']);
        });

        // tb_dispaly_systems_cds del programa
        Schema::create('signage_program_signage_system', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signage_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signage_system_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        // tb_dispaly_systems_cds del display
        Schema::create('signage_display_signage_system', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signage_display_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signage_system_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        // tb_mycoupon_master_cds, feli_…, mypage_…, system_… del programa
        Schema::create('coupon_signage_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signage_program_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('usage')->default(1)->comment('1: general, 2: FeliCa, 3: マイページ, 4: システム');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['signage_program_id', 'usage']);
        });

        // tb_mycoupon_master_cds del display
        Schema::create('coupon_signage_display', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signage_display_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_signage_display');
        Schema::dropIfExists('coupon_signage_program');
        Schema::dropIfExists('signage_display_signage_system');
        Schema::dropIfExists('signage_program_signage_system');
        Schema::dropIfExists('signage_material_signage_program');
        Schema::dropIfExists('signage_materials');
        Schema::dropIfExists('signage_displays');
        Schema::dropIfExists('signage_programs');
        Schema::dropIfExists('signage_systems');
    }
};
