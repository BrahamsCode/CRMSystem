<?php

namespace App\Http\Requests\Ventas;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta / edición de 割引管理 (karute_waribiki_register.php, act=new|up). Como メニュー管理: solo form2Db
 * (LegacyText::db); el JS exigía 名称 y 割引額.
 */
class DiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => LegacyText::db($this->input('name')),
            'value' => preg_replace('/\D/', '', LegacyText::db($this->input('value'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'act' => ['required', 'in:new,up'],
            'cd' => ['required_if:act,up', 'nullable', 'integer'],
            'tb_shop_cd' => ['nullable', 'integer'],
            'name' => ['required', 'string'],
            'value' => ['required', 'integer'],
            'type' => ['nullable', 'in:1,2'],
            'buai_flg' => ['nullable', 'in:1,2'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => '「名称」が未入力です。', 'value.required' => '「割引額」が未入力です。'];
    }

    /** Columnas de tb_karute_waribiki */
    public function values(): array
    {
        return [
            'name' => (string) $this->input('name'),
            'value' => (int) $this->input('value'),
            'type' => (string) ($this->input('type') ?: '1'),
            'buai_flg' => (string) ($this->input('buai_flg') ?: '1'),
        ];
    }
}
