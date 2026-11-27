<?php

namespace App\Http\Requests\Ventas;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta / edición de メニュー管理 (karute_menu_register.php, act=new|up). El legacy solo aplicaba form2Db
 * (LegacyText::db) a todo `$_REQUEST`, sin get_form() ni get_form2(); el JS exigía 名称 y 金額.
 */
class ServiceMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => LegacyText::db($this->input('name')),
            'kingaku' => preg_replace('/\D/', '', LegacyText::db($this->input('kingaku'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'act' => ['required', 'in:new,up'],
            'cd' => ['required_if:act,up', 'nullable', 'integer'],
            'tb_shop_cd' => ['nullable', 'integer'],
            'name' => ['required', 'string'],
            'kingaku' => ['required', 'integer'],
            'type' => ['nullable', 'in:1,2,3'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => '「名称」が未入力です。', 'kingaku.required' => '「金額」が未入力です。'];
    }

    /** Columnas de tb_karute_menu */
    public function values(): array
    {
        return [
            'name' => (string) $this->input('name'),
            'kingaku' => (int) $this->input('kingaku'),
            'type' => (string) ($this->input('type') ?: '1'),
        ];
    }
}
