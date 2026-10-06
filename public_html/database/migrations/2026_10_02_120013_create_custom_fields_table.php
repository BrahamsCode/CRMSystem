<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos de cada categoría de información adicional.
        // Ej. la categoría «Mascota» tiene los campos Nombre, Tipo y Peso.
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_category_id')->constrained()->cascadeOnDelete();

            $table->text('name');
            $table->text('slug')->comment('Clave dentro del json de valores');

            // El legacy ofrece: texto, email, alfanumérico, numérico, textarea,
            // checkbox, select, radio, año, año-mes, año-mes-día, mes-día,
            // fecha de referencia, tabla y menú.
            // Confirmado contra el legacy (search_form_new.php): son 15, no 14.
            $table->smallInteger('type')->comment('Tipo de campo del formulario');

            $table->text('unit')->nullable()->comment('Ej. Kg');
            $table->text('note')->nullable()->comment('Texto de ayuda');
            $table->smallInteger('size')->nullable()->comment('Ancho del control — App\Enums\FieldSize');

            $table->smallInteger('required_flg')->default(0)->comment('0: opcional, 1: obligatorio');
            $table->smallInteger('search_flg')->default(0)->comment('0: no, 1: usable como filtro de búsqueda');
            $table->smallInteger('mail_magazine_flg')->default(0)
                ->comment('0: no, 1: usable como variable en el newsletter');
            $table->smallInteger('display_scope')->default(1)
                ->comment('1: admin y móvil, 2: solo admin, 3: solo móvil');

            $table->json('config')->nullable()->comment('Configuración extra según el tipo');
            $table->integer('sort')->default(0);

            $table->smallInteger('status')->default(1)->comment('0: inactivo, 1: activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['custom_category_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
