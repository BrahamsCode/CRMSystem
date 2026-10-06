<?php

namespace Database\Seeders;

use App\Enums\CustomCategoryType;
use App\Enums\CustomFieldType;
use App\Enums\DisplayScope;
use App\Models\CustomCategory;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Las cinco categorías de información adicional que tiene el legacy, con los
 * campos de «Mascota» completos (es la única configurada de punta a punta).
 */
class CustomCategorySeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('name', 'shop')->firstOrFail();

        $categorias = [
            [
                'name' => 'sampleform', 'slug' => 'sampleform', 'columns' => 2,
                'type' => CustomCategoryType::Normal, 'sort' => 1,
                'fields' => [
                    ['name' => 'remarks', 'slug' => 'remarks', 'type' => CustomFieldType::Textarea],
                    ['name' => 'remarks2', 'slug' => 'remarks2', 'type' => CustomFieldType::Textarea],
                ],
            ],
            [
                'name' => 'カルテ', 'slug' => 'karte', 'columns' => 2,
                'type' => CustomCategoryType::Normal, 'sort' => 2,
                'fields' => [
                    ['name' => 'メッセージ内容', 'slug' => 'message', 'type' => CustomFieldType::Text],
                    ['name' => 'スタッフ名', 'slug' => 'staff_name', 'type' => CustomFieldType::Text],
                ],
            ],
            [
                'name' => '家族情報', 'slug' => 'family', 'columns' => 2,
                'type' => CustomCategoryType::Normal, 'sort' => 3,
                'fields' => [
                    ['name' => '配偶者', 'slug' => 'spouse', 'type' => CustomFieldType::Select,
                        'display_scope' => DisplayScope::AdminOnly, 'required_flg' => 1],
                    ['name' => '結婚記念日', 'slug' => 'wedding', 'type' => CustomFieldType::YearMonthDay,
                        'display_scope' => DisplayScope::AdminOnly, 'required_flg' => 1],
                ],
            ],
            [
                'name' => 'Mascota', 'slug' => 'mascota', 'columns' => 3,
                'type' => CustomCategoryType::Normal, 'sort' => 4, 'search_flg' => 0,
                'fields' => [
                    ['name' => 'Nombre', 'slug' => 'nombre', 'type' => CustomFieldType::Text],
                    ['name' => 'Tipo', 'slug' => 'tipo', 'type' => CustomFieldType::Select,
                        'options' => ['Perro', 'Gato', 'Conejo', 'Hámster', 'Otros']],
                    ['name' => 'Peso', 'slug' => 'peso', 'type' => CustomFieldType::Numeric, 'unit' => 'Kg'],
                ],
            ],
            [
                'name' => 'Ropa', 'slug' => 'ropa', 'columns' => 1,
                'type' => CustomCategoryType::Normal, 'sort' => 5,
                'search_flg' => 0, 'display_flg' => 0,
                'fields' => [],
            ],
        ];

        foreach ($categorias as $datos) {
            $campos = $datos['fields'];
            unset($datos['fields']);

            $categoria = CustomCategory::firstOrCreate(
                ['shop_id' => $shop->id, 'slug' => $datos['slug']],
                $datos + ['shop_id' => $shop->id],
            );

            foreach ($campos as $i => $campo) {
                $opciones = $campo['options'] ?? [];
                unset($campo['options']);

                $creado = $categoria->fields()->firstOrCreate(
                    ['slug' => $campo['slug']],
                    $campo + ['sort' => $i + 1, 'search_flg' => 1],
                );

                foreach ($opciones as $j => $opcion) {
                    $creado->options()->firstOrCreate(
                        ['value' => Str::slug($opcion)],
                        ['label' => $opcion, 'sort' => $j + 1],
                    );
                }
            }
        }

        $this->command?->info('CustomCategorySeeder: ' . CustomCategory::count() . ' categorías personalizadas.');
    }
}
