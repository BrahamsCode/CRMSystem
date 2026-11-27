<?php

namespace App\Http\Requests\Mypage;

use App\Services\Mypage\MypageShop;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 職種登録 (recruit_category_register.php). El legacy solo lo comprueba en JS (店舗 y 職種名 obligatorios);
 * aquí también en el servidor. Sin マスター, la tienda es la de la sesión.
 */
class RecruitCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'act' => ['required', 'in:new,up'],
            'cd' => ['required_if:act,up', 'nullable', 'integer'],
            'shop_cd' => ['required', 'integer'],
            'cate_name' => ['required', 'string'],
        ];
    }

    public function shopCd(MypageShop $shop): mixed
    {
        return $shop->isMaster() ? $this->input('shop_cd') : $shop->current();
    }
}
