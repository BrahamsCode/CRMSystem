<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de 予約項目 (yoyaku_koumoku_new.php → yoyaku_koumoku_register.php).
 * El legacy solo validaba en el navegador (「店舗」「名称」「所要時間」が未入力です。); aquí lo mismo en el servidor.
 */
class ReservationMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tb_shop_cd' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'shoyou_time' => ['required', 'integer', 'min:0'],
            'naiyou' => ['nullable', 'string'],
            'type_flg' => ['required', 'in:1,2'],
            'tb_yoyaku_koumoku_category_cd' => ['nullable', 'integer'],
            'tb_setsubi_cd' => ['nullable', 'integer'],
            'menu_end_time_flg' => ['nullable', 'in:1'],
            'menu_end_time1' => ['nullable', 'integer', 'between:0,29'],
            'menu_end_time2' => ['nullable', 'in:00,30'],
            'img' => ['nullable', 'file'],
            'img_del_flg' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'tb_shop_cd.required' => '「店舗」が未入力です。',
            'name.required' => '「名称」が未入力です。',
            'shoyou_time.required' => '「所要時間」が未入力です。',
            'shoyou_time.integer' => '「所要時間」※半角数字で入力してください。',
            'shoyou_time.min' => '「所要時間」※半角数字で入力してください。',
        ];
    }
}
