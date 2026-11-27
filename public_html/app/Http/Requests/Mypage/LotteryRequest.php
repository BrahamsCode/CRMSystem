<?php

namespace App\Http\Requests\Mypage;

use App\Services\Mypage\LotteryConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * ガチャリン管理 各種設定 (kujibiki_config.php): cada formulario manda su act=. El legacy no valida en el
 * servidor (solo el JS del 抽選設定 exige que los cuatro porcentajes sumen 100 como máximo); aquí se
 * comprueba también para no dejar la ハズレ en negativo.
 */
class LotteryRequest extends FormRequest
{
    public const ACTS = ['change_kujibiki_disp_flg', 'change_kujibiki_max', 'change_kujibiki_limit_flg', 'change_kujibiki_rate'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'act' => ['required', 'in:' . implode(',', self::ACTS)],
            'kujibiki_disp_flg' => ['nullable', 'in:1,9'],
            'kujibiki_max' => ['nullable', 'integer', 'min:0'],
            'kujibiki_limit_flg' => ['nullable', 'in:0,1,2,3,4,5'],
            'time_limit' => ['nullable', 'string'],
        ];
        foreach (LotteryConfig::PRIZES as $prefix) {
            $rules["kujibiki_{$prefix}_rate"] = ['nullable', 'integer', 'between:0,100'];
            $rules["kujibiki_{$prefix}_num"] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('act') === 'change_kujibiki_rate' && array_sum(array_map('intval', $this->rates())) > 100) {
                $validator->errors()->add('kujibiki_dia_rate', 'rate');
            }
        }];
    }

    /** [premio => %] del formulario */
    public function rates(): array
    {
        $rates = [];
        foreach (LotteryConfig::PRIZES as $name => $prefix) {
            $rates[$name] = $this->input("kujibiki_{$prefix}_rate");
        }

        return $rates;
    }

    /** [premio => ['rate' => %, 'num' => 本数]] para LotteryConfig::saveRates */
    public function prizes(): array
    {
        $prizes = [];
        foreach (LotteryConfig::PRIZES as $name => $prefix) {
            $prizes[$name] = ['rate' => $this->input("kujibiki_{$prefix}_rate"), 'num' => $this->input("kujibiki_{$prefix}_num")];
        }

        return $prizes;
    }
}
