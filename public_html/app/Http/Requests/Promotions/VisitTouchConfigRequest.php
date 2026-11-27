<?php

namespace App\Http\Requests\Promotions;

use App\Services\Promotions\VisitTouchConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * カードリーダ設定 → 更新 (touch_config_register.php): `kankaku[<tb_shop_cd>]` = segundos de una de las
 * opciones del selector (el legacy guardaba cualquier texto).
 */
class VisitTouchConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kankaku' => ['nullable', 'array'],
            'kankaku.*' => ['required', 'integer', Rule::in(array_keys(VisitTouchConfigService::options()))],
        ];
    }

    /** [tb_shop_cd => segundos], solo claves numéricas */
    public function intervals(): array
    {
        return array_filter((array) $this->validated('kankaku', []), fn ($key) => is_numeric($key), ARRAY_FILTER_USE_KEY);
    }
}
