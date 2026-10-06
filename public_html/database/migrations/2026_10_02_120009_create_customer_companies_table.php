<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Empresa del cliente, una fila por cliente.
        //
        // En el legacy las dos secciones son excluyentes según 個人・法人:
        // - Persona: solo 勤務先 (lugar de trabajo): nombre, kana, rubro, teléfono y fax.
        // - Empresa: 法人情報: fundación, capital, rubro, departamento, representante
        //   y persona de contacto.
        // Por eso caben en la misma tabla: «name» es el lugar de trabajo o la razón social.
        Schema::create('customer_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();

            $table->text('name')->nullable()->comment('勤務先 名前 / razón social');
            $table->text('name_kana')->nullable();
            $table->smallInteger('industry')->nullable()->comment('業種 — App\Enums\Industry');
            $table->text('tel1')->nullable()->comment('Teléfono, solo dígitos');
            $table->text('tel3')->nullable()->comment('Fax, solo dígitos (igual que customers.tel3)');

            // --- Solo empresas ---
            $table->text('department')->nullable()->comment('部署');
            $table->date('founded_on')->nullable()->comment('設立年月日');
            // bigint: el capital en la unidad mínima no cabe en integer para monedas con céntimos
            $table->bigInteger('capital')->nullable()->comment('資本金, unidad mínima de la moneda');

            $table->text('representative_last_name')->nullable()->comment('代表者');
            $table->text('representative_first_name')->nullable();
            $table->text('representative_last_name_kana')->nullable();
            $table->text('representative_first_name_kana')->nullable();
            $table->date('representative_birth_date')->nullable();
            $table->smallInteger('representative_sex')->default(0)->comment('ISO 5218 — 0: no conocido, 1: masculino, 2: femenino');

            $table->text('contact_last_name')->nullable()->comment('担当者');
            $table->text('contact_first_name')->nullable();
            $table->text('contact_last_name_kana')->nullable();
            $table->text('contact_first_name_kana')->nullable();
            $table->text('contact_tel1')->nullable()->comment('Teléfono, solo dígitos');
            $table->text('contact_tel3')->nullable()->comment('Fax, solo dígitos');
            $table->text('contact_mail')->nullable();

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_companies');
    }
};
