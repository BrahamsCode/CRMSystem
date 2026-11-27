<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 求人情報 (tb_recruit) y 求人職種 (tb_recruit_category). Los lee la señalización
 * (floormap.xml). El importador conserva `id` = `cd` del legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete()->comment('null = 全店舗');
            $table->text('name')->comment('職種 (shokushu)');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_category_id')->nullable()->constrained()->nullOnDelete()->comment('募集職種 (boshu_shokushu)');
            $table->text('headline')->nullable()->comment('募集見出し (boshu_headline)');
            $table->text('description')->nullable()->comment('仕事内容 (shigoto_naiyou)');
            $table->text('employment_type')->nullable()->comment('雇用形態 (koyou_keitai), texto libre');
            $table->text('requirements')->nullable()->comment('応募資格 (oubo_shikaku)');
            $table->text('work_location')->nullable()->comment('勤務地 (kinmu_chi)');
            $table->text('work_hours')->nullable()->comment('勤務時間 (kinmu_jikan)');
            $table->text('salary')->nullable()->comment('給与 (kyuyo), texto libre');
            $table->text('holidays')->nullable()->comment('休日・休暇 (kyuka)');
            $table->text('benefits')->nullable()->comment('待遇・福利厚生 (taigu)');
            $table->text('image1_path')->nullable()->comment('pic_flg1');
            $table->text('image2_path')->nullable()->comment('pic_flg2');
            $table->text('company_name')->nullable()->comment('kaisha_name');
            $table->text('street_address')->nullable()->comment('add');
            $table->text('tel')->nullable();
            $table->text('fax')->nullable();
            $table->text('business_description')->nullable()->comment('事業内容 (jigyou_naiyou)');
            $table->text('how_to_apply')->nullable()->comment('応募方法 (oubo_houhou)');
            $table->text('interview_location')->nullable()->comment('面接地 (mensetsuchi)');
            $table->text('contact_person')->nullable()->comment('担当 (tantou)');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('job_categories');
    }
};
