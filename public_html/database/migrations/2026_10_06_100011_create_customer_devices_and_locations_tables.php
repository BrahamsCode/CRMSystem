<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dispositivos del cliente para las notificaciones push. Sustituye a
        // customers.device_type / easy_login cuando exista la app de Mi página.
        Schema::create('customer_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('platform')->comment('1: iOS, 2: Android, 3: navegador web');
            $table->text('token')->unique()->comment('Token de push del dispositivo');
            $table->timestamp('last_seen_at')->nullable();
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // Ubicaciones que envía la app: filtro «estuvo cerca de una tienda»
        Schema::create('customer_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->timestamp('recorded_at');
            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'recorded_at']);
        });

        // Saldos que muestra la ficha; se recalculan desde customer_stamps y customer_points
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('stamp_balance')->default(0)->after('next_visit_date');
            $table->integer('point_balance')->default(0)->after('stamp_balance');
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['stamp_balance', 'point_balance']));
        Schema::dropIfExists('customer_locations');
        Schema::dropIfExists('customer_devices');
    }
};
