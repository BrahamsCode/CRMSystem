<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de 予約項目カテゴリ (yoyaku_koumoku_category.php → yoyaku_koumoku_category_register.php).
 * Mismo aviso que el JS del legacy (submitChk) si falta el nombre.
 */
class ReservationMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '入力がありません。',
        ];
    }
}
