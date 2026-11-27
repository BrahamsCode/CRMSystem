<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de 顧客グループ / 来店動機 / 顧客レベル (`*_register.php`). El legacy solo comprobaba el nombre
 * en el navegador (submitChk: 「…名が空白です。」); aquí también en el servidor. `default_flg` solo lo envía 顧客グループ.
 */
class CustomerMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'default_flg' => ['nullable', 'in:1'],
        ];
    }
}
