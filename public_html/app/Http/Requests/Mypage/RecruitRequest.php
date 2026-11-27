<?php

namespace App\Http\Requests\Mypage;

use App\Services\Mypage\MypageShop;
use App\Services\Mypage\RecruitService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 求人情報 新規登録 / 修正 (recruit_new.php / recruit_update.php con act=up). Comprobaciones del legacy:
 * 募集見出し, 店舗 y 募集職種 obligatorios, con sus mensajes bajo cada campo. Al fallar vuelve al
 * formulario con lo escrito (incluidos pic_name1/pic_name2).
 */
class RecruitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'boshu_headline' => ['required', 'string'],
            'tb_shop_cd' => ['required', 'integer'],
            'boshu_shokushu' => ['required', 'integer'],
            'pic_name1' => ['nullable', 'string'],
            'pic_name2' => ['nullable', 'string'],
        ];
        foreach (RecruitService::TEXT_FIELDS as $field) {
            $rules[$field] ??= ['nullable', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'boshu_headline.required' => __('mypage.recruit.headline_required', [], 'ja'),
            'tb_shop_cd.required' => __('mypage.recruit.shop_required', [], 'ja'),
            'boshu_shokushu.required' => __('mypage.recruit.category_required', [], 'ja'),
        ];
    }

    /**
     * Datos para RecruitService::save. Sin マスター solo vale la tienda de la sesión (el legacy
     * confiaba en el hidden del formulario).
     */
    public function recruitData(MypageShop $shop): array
    {
        $data = $this->only(array_merge(RecruitService::TEXT_FIELDS, ['tb_shop_cd', 'boshu_shokushu', 'pic_name1', 'pic_name2']));
        if (! $shop->isMaster()) {
            $data['tb_shop_cd'] = $shop->current();
        }

        return $data;
    }
}
