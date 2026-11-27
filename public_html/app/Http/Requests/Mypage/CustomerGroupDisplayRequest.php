<?php

namespace App\Http\Requests\Mypage;

use Illuminate\Foundation\Http\FormRequest;

/** 「マイコレのトップに顧客グループの表記を」 (mode=apli_customer_group_disp_upload): 1 = 表示する, 0 = 表示しない */
class CustomerGroupDisplayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'apli_customer_group_disp' => ['nullable', 'in:0,1'],
        ];
    }

    public function display(): bool
    {
        return (int) $this->input('apli_customer_group_disp') === 1;
    }
}
