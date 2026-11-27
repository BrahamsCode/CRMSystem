<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** mail_template_register.php (el legacy valida en el navegador con submitChk; aquí también en el servidor) */
class MailTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cd' => ['nullable', 'integer'],
            'tb_mail_template_category_cd' => ['required', 'integer'],
            'mail_type' => ['nullable', Rule::in(['', 'deco'])],
            'title' => ['required', 'string'],
            'naiyou' => [Rule::requiredIf(fn () => $this->input('mail_type', '') === ''), 'nullable', 'string'],
            'naiyou_deco' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['tb_mail_template_category_cd' => 'カテゴリ', 'mail_type' => 'タイプ', 'title' => 'タイトル', 'naiyou' => '内容'];
    }
}
