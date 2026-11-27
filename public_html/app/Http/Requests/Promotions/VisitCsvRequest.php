<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CSV出力する de 来店履歴一覧 (csv_download.php): el formulario manda el rango ya calculado
 * (start_time, end_time), el día elegido, el periodo y la tienda.
 */
class VisitCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required', 'integer'],
            'end_time' => ['required', 'integer'],
            'selected_day' => ['nullable', 'integer'],
            'kikan' => ['nullable', 'in:0,1,2,3'],
            'shop_cd' => ['required', 'integer'],
        ];
    }
}
