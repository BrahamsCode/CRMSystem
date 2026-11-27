<?php

namespace App\Http\Requests\Customers;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de campo de 顧客追加情報 (legacy `search_form_new_check.php`): solo exige la フォームタイトル,
 * después de recortar espacios 半角/全角 como get_form2(). Con error, vuelve al formulario con el mismo mensaje.
 */
class CustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** get_form2() recorta espacios 半角/全角 antes de comprobar */
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => LegacyText::trim(is_scalar($this->input('name')) ? (string) $this->input('name') : '')]);
    }

    public function rules(): array
    {
        return ['name' => ['required']];
    }

    public function messages(): array
    {
        return ['name.required' => 'フォームタイトルは必ず入力して下さい。'];
    }
}
