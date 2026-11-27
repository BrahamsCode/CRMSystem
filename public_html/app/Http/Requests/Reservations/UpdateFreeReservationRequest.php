<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * フリー予約設定 (free_yoyaku_register.php). El legacy no validaba: sin `free_shop_cd` no guardaba nada.
 */
class UpdateFreeReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'free_shop_cd' => ['nullable', 'integer'],
            'hyouji_flg' => ['nullable', 'in:1'],
            'name' => ['nullable', 'string'],
            'staff_select_flg' => ['nullable', 'in:1'],
            'timeline_staff_flg' => ['nullable', 'in:1'],
        ];
    }
}
