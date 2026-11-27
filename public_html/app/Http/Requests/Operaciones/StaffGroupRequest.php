<?php

namespace App\Http\Requests\Operaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * スタッフグループ: el legacy pasa los datos por form2Db() (kana de ancho medio → completo y
 * recorte de espacios de medio y ancho completo), sin htmlspecialchars; el JS exige el nombre.
 */
class StaffGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $name = preg_replace('/^[ 　]*(.*?)[ 　]*$/u', '$1', mb_convert_kana($name, 'KV'));
            $this->merge(['name' => $name]);
        }
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }

    public function messages(): array
    {
        return ['name.required' => 'スタッフグループ名が空白です。スタッフグループ名をご記入下さい。'];
    }
}
