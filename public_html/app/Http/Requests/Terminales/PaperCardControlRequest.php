<?php

namespace App\Http\Requests\Terminales;

use Illuminate\Foundation\Http\FormRequest;

/**
 * control.php de ペーパーサイネージ. Solo tipos y formas: las respuestas de validación del legacy
 * (「入力項目が空白です。」, 「アルファベット・数字…」, 「既に使用されています。」) son texto plano que
 * el JS pinta en #result, así que las devuelve PaperCardService y no una redirección con $errors.
 */
class PaperCardControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:new,update,check,delete,hyouji_switch'],
            'cd' => ['nullable', 'integer'],
            'id' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string'],
            'url' => ['nullable', 'string'],
            'hyouji_flg' => ['nullable', 'string'],
            'returnfunc' => ['nullable'],
        ];
    }
}
