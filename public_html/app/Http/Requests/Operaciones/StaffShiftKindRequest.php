<?php

namespace App\Http\Requests\Operaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * シフト区分: name y color pasan por form2Db() (kana de ancho medio → completo y recorte), sin escapar,
 * como staff_shift_kind_register.php; el JS del legacy exige el nombre.
 */
class StaffShiftKindRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'color'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', mb_convert_kana($value, 'KV'))]);
            }
        }
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string'], 'color' => ['nullable', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return ['name.required' => 'シフト区分管理名が空白です。シフト区分管理名をご記入下さい。'];
    }
}
