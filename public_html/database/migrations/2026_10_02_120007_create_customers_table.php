<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete()
                ->comment('Tienda de registro');

            // --- Identificación ---
            $table->text('code')->unique()->comment('Nº de socio, visible para el usuario final');
            $table->text('management_no')->nullable()->comment('Nº de gestión');
            $table->smallInteger('type')->default(1)->comment('1: persona, 2: empresa');

            // --- Datos de persona ---
            $table->text('last_name')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name_kana')->nullable();
            $table->text('first_name_kana')->nullable();
            $table->date('birth_date')->nullable();
            // ISO 5218 completo: el legacy permite dejarlo vacío (0) y una empresa no tiene sexo (9)
            $table->smallInteger('sex')->default(0)->comment('ISO 5218 — 0: no conocido, 1: masculino, 2: femenino, 9: no aplica');
            $table->text('blood_type')->nullable();
            $table->smallInteger('occupation')->nullable()->comment('Catálogo App\Enums\Occupation');

            // --- Dirección ---
            // Las cuatro columnas del estándar más el edificio, que el legacy guarda
            // aparte (マンション・ビル名) y que las etiquetas de correo imprimen en su
            // propia línea. Las lecturas en kana de ciudad y edificio no se migran: no
            // se buscan ni se ordenan por ellas.
            $table->text('zip')->nullable()->comment('Solo dígitos, como el legacy: 5300001');
            $table->text('pref')->nullable();
            $table->text('city')->nullable()->comment('市区町村');
            $table->text('street_address')->nullable()->comment('丁目・番地');
            $table->text('building')->nullable()->comment('マンション・ビル名');

            // --- Contacto (teléfonos solo dígitos, como el legacy) ---
            $table->text('tel1')->nullable()->comment('Teléfono');
            $table->text('tel2')->nullable()->comment('Teléfono móvil (solo personas)');
            $table->text('tel3')->nullable()->comment('Fax');
            $table->text('mail1')->nullable();
            $table->text('mail2')->nullable();
            $table->text('mail3')->nullable()->comment('Email personal (solo personas)');

            // Lugar de trabajo (personas) y datos de empresa (empresas): tabla
            // customer_companies, una fila por cliente.

            // --- Información familiar ---
            $table->smallInteger('spouse_flg')->nullable()->comment('0: sin cónyuge, 1: con cónyuge');
            $table->date('wedding_date')->nullable()->comment('Aniversario de boda');

            // --- Promoción ---
            $table->smallInteger('mail_magazine')->default(1)
                ->comment('1: enviar, 2: no enviar, 3: no entregable (legacy mailmaga_flg: 1, 0, 9)');
            $table->integer('bounce_count')->default(0)->comment('Correos no entregados');
            $table->smallInteger('reservation_reminder_flg')->default(1)
                ->comment('0: no enviar, 1: enviar recordatorio de reservas');
            $table->smallInteger('address_type')->nullable()->comment('App\Enums\AddressType — desglose por operador en la búsqueda');
            $table->text('note')->nullable()->comment('Notas');

            // --- Acceso a Mi página ---
            // El usuario de acceso es el propio `code` (Nº de socio): en los mockups
            // «ID de acceso» muestra ese mismo valor en solo lectura.
            $table->text('password')->nullable();
            // La ficha muestra «Dispositivo: iPhone · Inicio de sesión rápido: no
            // configurado» (en el POS legacy: crm_mobile_type y crm_easy_login).
            $table->text('device_type')->nullable()->comment('Dispositivo desde el que entra a Mi página');
            $table->text('easy_login')->nullable()->comment('Inicio de sesión rápido');
            $table->timestamp('last_login_at')->nullable();

            // --- Estadísticas, recalculadas a partir de visits ---
            $table->integer('visit_count')->default(0);
            $table->integer('total_amount')->default(0)->comment('Unidad mínima de la moneda');
            $table->integer('average_amount')->default(0)->comment('Unidad mínima de la moneda');
            $table->date('last_visit_date')->nullable();
            $table->integer('average_visit_cycle')->nullable()->comment('Ciclo medio entre visitas, en días');
            $table->date('next_visit_date')->nullable()->comment('Próxima visita prevista');

            // --- Clasificación ---
            $table->foreignId('terminal_id')->nullable()->constrained()->nullOnDelete()
                ->comment('ID de terminal');
            // «Registro de terminal» no se guarda: el legacy solo muestra 端末登録有/無,
            // que se deduce de si terminal_id tiene valor.
            $table->foreignId('customer_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('amount_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->foreignId('prev_amount_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->foreignId('visit_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->foreignId('prev_visit_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
            $table->foreignId('visit_motive_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Motivo de primera visita');
            $table->foreignId('referrer_id')->nullable()->constrained('customers')->nullOnDelete()
                ->comment('Cliente que lo refirió');

            // Falta employee_id (personal asignado): depende de que se defina la
            // tabla employees en el estándar de la empresa.

            // --- Campos personalizados de tipo normal (un registro por cliente) ---
            // jsonb y no json: la búsqueda por campos personalizados necesita un
            // índice GIN, y PostgreSQL solo lo admite sobre jsonb.
            $table->jsonb('custom_data')->default('{}');

            $table->text('uid')->unique();
            $table->smallInteger('status')->default(1)->comment('0: dado de baja, 1: registrado');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'status']);
            $table->index('mail1');
            $table->index('tel1');
            $table->index('last_visit_date');
        });

        // La pantalla «Buscar clientes» filtra por los campos personalizados
        // (sampleform, カルテ, Familia, Mascota), así que custom_data va indexado.
        DB::statement('CREATE INDEX customers_custom_data_gin ON customers USING gin (custom_data)');
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
