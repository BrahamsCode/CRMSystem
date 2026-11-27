<?php

namespace App\Http\Requests\Ventas;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 確定 de 売上データ作成 (karute_new_register.php). El legacy cortaba con «エラー発生しました。…» si
 * kaiin_cd o staff_cd no eran números; el resto (fecha, socio, スタッフ, total ≥ 0) lo comprobaba el JS
 * (submitChk). Aquí se comprueba en el servidor y se responde con el mismo texto.
 * Nombres e importes de menús y descuentos (menu_name[], menu_kingaku[]…) se ignoran: se leen de la BD.
 */
class SaleStoreRequest extends FormRequest
{
    public const ERROR = 'エラー発生しました。もう一度やり直してください。';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kaiin_cd' => ['required', 'regex:/^[0-9]+$/', 'exists:tb_customer,cd'],
            'staff_cd' => ['required', 'regex:/^[0-9]+$/', 'exists:tb_staff,cd'],
            'update_date' => ['required', 'date_format:Y/m/d'],
            'shop' => ['nullable', 'integer'],
            'menu_cd' => ['nullable', 'array'],
            'menu_cd.*' => ['integer'],
            'waribiki_cd' => ['nullable', 'array'],
            'waribiki_cd.*' => ['integer'],
            'otherWaribikiValue' => ['nullable', 'regex:/^[0-9]*$/'],
            'otherWaribikiType' => ['nullable', 'in:1,2'],
            'add_record_verify' => ['nullable', 'string', 'max:40'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response(self::ERROR, 422));
    }

    /** @return array{customer_cd: int, staff_cd: int, business_day: string, menu_cds: list<int>, discount_cds: list<int>, manual_value: int, manual_type: string} */
    public function sale(): array
    {
        return [
            'customer_cd' => (int) $this->input('kaiin_cd'),
            'staff_cd' => (int) $this->input('staff_cd'),
            'business_day' => (string) $this->input('update_date'),
            'menu_cds' => array_map('intval', array_values((array) $this->input('menu_cd', []))),
            'discount_cds' => array_map('intval', array_values((array) $this->input('waribiki_cd', []))),
            'manual_value' => (int) $this->input('otherWaribikiValue'),
            'manual_type' => (string) ($this->input('otherWaribikiType') ?: '1'),
        ];
    }
}
