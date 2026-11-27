<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de 設備 (setsubi_new.php → setsubi_register.php), con los mismos avisos que el JS del legacy.
 */
class FacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_cd' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'quantity' => ['required', 'regex:/^[1-9][0-9]*$/', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '「名称」が未入力です。',
            'quantity.required' => '「数量」が未入力です。',
            'quantity.regex' => '「数量」半角数字で入力してください。',
            'quantity.max' => '「数量」半角数字で入力してください。',
        ];
    }
}
