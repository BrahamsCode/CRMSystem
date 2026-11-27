<?php

namespace App\Http\Requests\Promotions;

use App\Services\Promotions\DmCustomerList;
use Illuminate\Foundation\Http\FormRequest;

/**
 * この内容で宛名出力する (aterna_a4_12.php): pide una búsqueda de DM発行 guardada en la sesión.
 * Sin ella el legacy imprimía todo `tb_customer` (incluidos los borrados): aquí es 404.
 */
class DmLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_uniqid' => ['required', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        abort_unless(app(DmCustomerList::class)->has($this->input('search_uniqid')), 404);
    }
}
