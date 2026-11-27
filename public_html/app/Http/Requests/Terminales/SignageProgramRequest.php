<?php

namespace App\Http\Requests\Terminales;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 放映プログラム alta/edición (movie_program_frm.php): solo el nombre es obligatorio.
 * Las casillas de cupón llegan como tb_mycoupon_master_cds<i> / system_tb_mycoupon_master_cds<i>
 * (posicionales, i < tb_mycoupon_master_max) y el servicio las lee de validated() + esos campos.
 */
class SignageProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string'],
            'telop_comment' => ['nullable', 'string'],
            'vertical_setting' => ['nullable', 'in:landscape,portrait'],
            'disp_conf' => ['nullable', 'in:1,2'],
            'act_flg' => ['nullable', 'in:1'],
            'feli_act_flg' => ['nullable', 'in:1,2,3'],
            'mypage_act_flg' => ['nullable', 'in:1'],
            'feli_tb_mycoupon_master_cds' => ['nullable', 'integer'],
            'mypage_tb_mycoupon_master_cds' => ['nullable', 'integer'],
            'tb_dispaly_systems_cds' => ['nullable', 'integer'],
            'non_coupon_flg' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'hb_which' => ['nullable', 'in:0,1'],
            'tb_mycoupon_master_max' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'system_tb_mycoupon_master_max' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'tb_screensaver_cd' => ['nullable', 'array'],
            'tb_screensaver_cd.*' => ['integer'],
        ];

        $max = min(1000, max(0, (int) $this->input('tb_mycoupon_master_max')));
        for ($i = 0; $i < $max; $i++) {
            $rules['tb_mycoupon_master_cds'.$i] = ['nullable', 'integer'];
            $rules['system_tb_mycoupon_master_cds'.$i] = ['nullable', 'integer'];
        }

        return $rules;
    }

    /** Mensaje del legacy (la vista antepone «※») */
    public function messages(): array
    {
        return [
            'name.required' => '必須項目です。',
        ];
    }
}
