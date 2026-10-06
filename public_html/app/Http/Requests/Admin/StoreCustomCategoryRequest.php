<?php

namespace App\Http\Requests\Admin;

use App\Enums\CustomCategoryType;
use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCustomCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        $categoria = $this->route('category');

        // Misma tienda con la que trabaja ModuleController::shop(); el formulario no la envía

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('custom_categories', 'name')
                    ->where('shop_id', Shop::active()->orderBy('id')->value('id'))
                    ->ignore($categoria?->id)
                    ->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string'],
            'columns' => ['required', 'integer', 'between:1,3'],
            'type' => ['required', new Enum(CustomCategoryType::class)],
            'search_flg' => ['required', 'integer', 'in:0,1'],
            'display_flg' => ['required', 'integer', 'in:0,1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre de la categoría',
            'columns' => 'columnas al mostrar',
            'type' => 'tipo de registro',
        ];
    }

    /** El slug es la clave dentro de customers.custom_data, así que se deriva del nombre */
    public function datos(): array
    {
        return $this->validated() + ['slug' => Str::slug($this->input('name')) ?: Str::random(8)];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search_flg' => (int) $this->boolean('search_flg'),
            'display_flg' => (int) $this->boolean('display_flg'),
        ]);
    }
}
