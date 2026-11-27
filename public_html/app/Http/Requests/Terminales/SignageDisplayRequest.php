<?php

namespace App\Http\Requests\Terminales;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ディスプレイ alta/edición (display_frm.php): 端末名 obligatorio.
 * El legacy también rechazaba terminal_type == "0", pero el formulario no envía ese campo.
 */
class SignageDisplayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'comment' => ['nullable', 'string'],
            'tb_c7_movie_program_cds' => ['nullable', 'integer'],
            'hb_which' => ['nullable', 'in:0,1'],
        ];
    }

    /** Mensaje del legacy (la vista antepone «※») */
    public function messages(): array
    {
        return [
            'name.required' => '必須項目です。',
        ];
    }
}
