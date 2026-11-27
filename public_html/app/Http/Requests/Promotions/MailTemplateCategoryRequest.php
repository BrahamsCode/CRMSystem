<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

/** mail_template_category_register.php */
class MailTemplateCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cd' => ['nullable', 'integer'],
            'name' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '名称'];
    }
}
