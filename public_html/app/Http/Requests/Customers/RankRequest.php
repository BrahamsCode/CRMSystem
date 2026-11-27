<?php

namespace App\Http\Requests\Customers;

use App\Services\Reservations\ReservationInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de rango (legacy: category1/rank/customer_rank_frm.php, bloque 「エラーチェック」).
 * Los datos pasan por form2Db() (半角カナ → 全角 y recorte), como el `array_walk_recursive($_REQUEST, 'form2Db')`
 * del legacy. Errores con las claves `name` y `jyouken` y los mismos textos del legacy.
 */
class RankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => ReservationInput::form2Db($this->input('name')),
            'jyouken1' => ReservationInput::form2Db($this->input('jyouken1')),
            'jyouken2' => ReservationInput::form2Db($this->input('jyouken2')),
            'hyouji_flg' => ReservationInput::form2Db($this->input('hyouji_flg')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string'],
            'jyouken1' => ['nullable', 'string'],
            'jyouken2' => ['nullable', 'string'],
            'hyouji_flg' => ['nullable', 'in:1,9'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $data = $this->rankData();

            if ($data['name'] === '') {
                $validator->errors()->add('name', '※必須入力項目です。');
            }

            if ($data['jyouken1'] === '' && $data['jyouken2'] === '') {
                $validator->errors()->add('jyouken', '※必須入力項目です。必ずどちらかに値を入力してください。');
            } elseif (($data['jyouken1'] !== '' && ! ctype_digit($data['jyouken1'])) || ($data['jyouken2'] !== '' && ! ctype_digit($data['jyouken2']))) {
                // El JS del legacy (replace_value) ya quita lo que no es número; aquí se rechaza en vez de romper el SQL
                $validator->errors()->add('jyouken', '※不正な入力です。');
            } elseif ($data['jyouken2'] !== '' && (int) $data['jyouken1'] > (int) $data['jyouken2']) {
                $validator->errors()->add('jyouken', '※不正な入力です。一つ目の値が、二つ目の値を下回っています。');
            }
        }];
    }

    /** @return array{name: string, jyouken1: string, jyouken2: string, hyouji_flg: string} */
    public function rankData(): array
    {
        return [
            'name' => (string) $this->input('name'),
            'jyouken1' => (string) $this->input('jyouken1'),
            'jyouken2' => (string) $this->input('jyouken2'),
            'hyouji_flg' => (string) $this->input('hyouji_flg'),
        ];
    }
}
