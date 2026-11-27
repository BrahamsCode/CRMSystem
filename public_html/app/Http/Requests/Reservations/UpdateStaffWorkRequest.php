<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * スタッフ施術管理 (staff_works.php, act=reg). El legacy no validaba nada: solo se exige el empleado
 * y que los arrays lleguen como arrays.
 */
class UpdateStaffWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop' => ['nullable', 'integer'],
            'staff' => ['required', 'integer'],
            'reserve_flg' => ['nullable', 'in:0,1'],
            'yoyaku_menu_cd' => ['nullable', 'array'],
            'shoyou_time' => ['nullable', 'array'],
            'shoyou_time.*' => ['nullable'],
            'mypage_flg' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return ['staff' => 'スタッフ'];
    }
}
