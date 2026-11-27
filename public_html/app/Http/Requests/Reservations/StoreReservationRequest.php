<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de reserva (yoyaku_customer_select.php → yoyaku_register.php).
 * El legacy validaba solo en el navegador (お名前 / 人数 / 必須の項目 / formato de メールアドレス, ver submitChk());
 * aquí se exigen además los datos sin los que el legacy no podía registrar (tienda, empleado, día y hora, menú).
 */
class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'act' => ['nullable', 'in:new'],
            'shop' => ['required', 'integer'],
            'staff' => ['required', 'integer'],
            'yoyaku_date' => ['required', 'regex:/^[0-9]{12}$/'],
            'plus' => ['nullable', 'in:0,1'],
            'menu' => ['required', 'array'],
            'option_menu' => ['nullable', 'array'],
            'kaiin' => ['nullable', 'integer'],
            'people' => ['nullable', 'integer', 'min:0'],
            'free_koumoku' => ['nullable', 'array'],
            'register_verify' => ['nullable', 'string'],
            'oshirase' => ['nullable', 'in:1'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'キーコードがありません。',
            'people.*' => '「人数」が未入力です。',
        ];
    }
}
