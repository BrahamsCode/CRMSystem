<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/** 予約フォーム設定 (yoyaku_form_config.php). El legacy no validaba: solo forma de los datos. */
class UpdateFormSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'free_koumoku_cd' => ['nullable', 'array'],
            'free_koumoku_cd.*' => ['integer'],
        ];
    }
}
