<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->text('name');
            $table->smallInteger('default_flg')->default(0)
                ->comment('0: normal, 1: grupo asignado por defecto a los clientes nuevos');
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();
        });

        // Solo un grupo por defecto por tienda. Es un índice parcial porque
        // la restricción solo aplica a las filas con default_flg = 1.
        DB::statement('
            CREATE UNIQUE INDEX customer_groups_default_unique
                ON customer_groups (shop_id)
                WHERE default_flg = 1 AND deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
