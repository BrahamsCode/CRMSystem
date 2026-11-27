<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ガチャリン管理 各種設定. Reemplaza contents_master (category = 'tb_c6_kujibiki').
 * La probabilidad de ハズレ no se guarda: es el complemento de los premios.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fila única
        Schema::create('lottery_settings', function (Blueprint $table) {
            $table->id();
            $table->text('name')->nullable()->comment('kujibiki_name');
            $table->text('image_path')->nullable()->comment('kujibiki_img');
            $table->smallInteger('enabled_flg')->default(0)->comment('kujibiki_disp_flg: 0: no, 1: sí');
            $table->integer('max_tries')->nullable()->comment('kujibiki_max');
            $table->smallInteger('limit_flg')->default(0)->comment('kujibiki_limit_flg: 0: sin límite, 1: con límite');
            $table->integer('prize_count')->nullable()->comment('prize_count');
            $table->smallInteger('enabled_on_enter_flg')->default(0)->comment('kujibiki_disp_flg_enter: 0: no, 1: sí');
            $table->integer('max_tries_on_enter')->nullable()->comment('kujibiki_max_enter');
            $table->smallInteger('limit_on_enter_flg')->default(0)->comment('kujibiki_limit_flg_enter: 0: no, 1: sí');
            $table->decimal('rate_on_enter', 5, 2)->nullable()->comment('kujibiki_rate_enter (%)');
            $table->smallInteger('limit_on_form_flg')->default(0)->comment('kujibiki_limit_flg_form: 0: no, 1: sí');
            $table->decimal('rate_on_form', 5, 2)->nullable()->comment('kujibiki_rate_form (%)');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lottery_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_setting_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('rank')->comment('1: ダイヤ, 2: ゴールド, 3: シルバー, 4: ブロンズ (enum LotteryRank)');
            $table->integer('quantity')->default(0)->comment('kujibiki_<rango>_num');
            $table->decimal('rate', 5, 2)->default(0)->comment('kujibiki_<rango>_rate (%)');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['lottery_setting_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_prizes');
        Schema::dropIfExists('lottery_settings');
    }
};
