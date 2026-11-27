<?php

namespace App\Http\Requests\Ventas;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta / edición de 経費管理 (keihi_register.php, act=new|up). El legacy solo aplicaba form2Db
 * (LegacyText::db); el JS exigía 店舗 y 使用金額 > 0.
 */
class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mokuteki' => LegacyText::db($this->input('mokuteki')),
            'price' => preg_replace('/\D/', '', LegacyText::db($this->input('price'))),
            'date1' => LegacyText::db($this->input('date1')),
        ]);
    }

    public function rules(): array
    {
        return [
            'act' => ['required', 'in:new,up'],
            'cd' => ['required_if:act,up', 'nullable', 'integer'],
            'shop' => ['nullable', 'integer'],
            'date1' => ['required', 'date_format:Y/m/d'],
            'mokuteki' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['price.required' => '・使用金額を入力してください。', 'price.min' => '・使用金額を入力してください。'];
    }

    /** @return array{date: string, purpose: string, price: float} */
    public function expense(): array
    {
        return [
            'date' => (string) $this->input('date1'),
            'purpose' => (string) $this->input('mokuteki'),
            'price' => (float) $this->input('price'),
        ];
    }
}
