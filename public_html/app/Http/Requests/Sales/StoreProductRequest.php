<?php

namespace App\Http\Requests\Sales;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;

/**
 * 商品新規登録 / 編集 (legacy: src/Validator/Validator.php item_edit_validate(), que también responde
 * category2/item/ajax_edit.php). Mismos campos obligatorios y mensajes que Valitron con las etiquetas del legacy.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::ruleSet($this->all());
    }

    public function messages(): array
    {
        return self::messageSet();
    }

    public function attributes(): array
    {
        return self::labels();
    }

    /** Validador para la comprobación AJAX (ajax_edit.php, por GET con el formulario serializado) */
    public static function makeValidator(array $data): Validator
    {
        return ValidatorFactory::make($data, self::ruleSet($data), self::messageSet(), self::labels());
    }

    public static function ruleSet(array $data): array
    {
        $rules = [
            'name' => ['required'],
            'display_name' => ['required'],
            'display_title' => ['required'],
            'discription' => ['required'],
            'discription_detail' => ['required'],
            'is_exchangeable_of_point' => ['required', 'numeric'],
            'available_shop_cds' => ['required', 'array'],
            'available_shop_cds.*' => ['integer'],
            'hyouji_flg' => ['required', 'numeric'],
        ];

        // Valitron: solo si se intercambia por puntos (is_exchangeable_of_point verdadero en PHP)
        if (! empty($data['is_exchangeable_of_point'])) {
            $rules['number_of_points_exchanged'] = ['required', 'regex:/^[0-9]+$/'];
        }

        return $rules;
    }

    private static function messageSet(): array
    {
        return [
            'required' => __('ventas.product.required', [], 'ja'),
            'numeric' => __('ventas.product.select', [], 'ja'),
            'array' => __('ventas.product.select', [], 'ja'),
            'available_shop_cds.*.integer' => __('ventas.product.select', ['attribute' => __('ventas.product.labels.available_shop_cds', [], 'ja')], 'ja'),
            'regex' => __('ventas.product.digits', [], 'ja'),
        ];
    }

    private static function labels(): array
    {
        return __('ventas.product.labels', [], 'ja');
    }
}
