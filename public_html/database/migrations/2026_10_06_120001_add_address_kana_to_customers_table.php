<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lecturas de la dirección, como en el alta del legacy:
 * 市区町村(カナ) (add_kana) y マンション・ビル名(カナ) (add_build_kana).
 * Se necesitan para migrar sus datos sin perderlos y para las etiquetas de los envíos postales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('city_kana')->nullable()->after('city')->comment('Lectura de 市区町村 y 町域: オオサカシキタク ウメダ');
            $table->text('building_kana')->nullable()->after('building')->comment('Lectura de マンション・ビル名');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['city_kana', 'building_kana']);
        });
    }
};
