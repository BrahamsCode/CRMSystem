<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** test_mail_address_register.php (act = new | up | del) */
class TestMailAddressRequest extends FormRequest
{
    public function rules(): array
    {
        $saving = in_array($this->input('act'), ['new', 'up'], true);

        return [
            'act' => ['required', Rule::in(['new', 'up', 'del'])],
            'cd' => [Rule::requiredIf(! $saving || $this->input('act') === 'up'), 'nullable', 'integer'],
            'rank' => ['nullable', 'integer'],
            'tb_shop_cd' => ['required', 'integer'],
            'name' => [Rule::requiredIf($saving), 'nullable', 'string'],
            // Misma expresión que submitChk() del legacy
            'email' => [Rule::requiredIf($saving), 'nullable', 'regex:/^[_a-z0-9-\.]+(\.[_a-z0-9-\.]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '名前', 'email' => 'メールアドレス'];
    }
}
