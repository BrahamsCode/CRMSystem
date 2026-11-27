<?php

namespace App\Http\Requests\Mypage;

use App\Services\Mypage\MypageShop;
use App\Services\Mypage\RecommendationService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * オススメ情報登録 (recommend_register.php). El legacy no valida en el servidor (solo el JS exige
 * 内容); aquí se valida la forma para no guardar basura. Sin マスター権限, la tienda es la de la sesión
 * (el legacy confiaba en el hidden del formulario).
 */
class RecommendationRequest extends FormRequest
{
    /** Alta (act=new) con 1016 新規; modificación (act=up) con 1016 編集. Sin el permiso, 403 */
    public function authorize(MypageShop $shop): bool
    {
        return $shop->can(RecommendationService::KENGEN, $this->input('act') === 'up' ? MypageShop::EDIT : MypageShop::CREATE);
    }

    public function rules(): array
    {
        return [
            'act' => ['required', 'in:new,up'],
            'cd' => ['required_if:act,up', 'nullable', 'integer'],
            'tb_shop_cd' => ['nullable', 'integer'],
            'hyouji_flg' => ['nullable', 'in:9'],
            // create_num_options() manda los menores de 10 con cero delante ('01')
            'news_year' => ['required', 'regex:/^\d{4}$/'],
            'news_month' => ['required', 'regex:/^(0?[1-9]|1[0-2])$/'],
            'news_day' => ['required', 'regex:/^(0?[1-9]|[12]\d|3[01])$/'],
            'title' => ['nullable', 'string'],
            'link_type' => ['nullable', 'in:http://,tel:,mailto:'],
            'link' => ['nullable', 'string'],
            'news_naiyou' => ['nullable', 'string'],
            'pic_name' => ['nullable', 'string'],
            'pic_link' => ['nullable', 'string'],
            'push' => ['nullable', 'in:0,1'],
        ];
    }

    /** Datos para RecommendationService::save */
    public function recommendationData(MypageShop $shop): array
    {
        $data = $this->only([
            'tb_shop_cd', 'hyouji_flg', 'news_year', 'news_month', 'news_day', 'title',
            'link_type', 'link', 'news_naiyou', 'pic_name', 'pic_link',
        ]);
        if (! $shop->isMaster()) {
            $data['tb_shop_cd'] = $shop->current();
        }

        return $data;
    }

    public function wantsPush(): bool
    {
        return (string) $this->input('push') === '1';
    }
}
