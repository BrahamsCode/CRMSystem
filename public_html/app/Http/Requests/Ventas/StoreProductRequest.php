<?php

namespace App\Http\Requests\Ventas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

/**
 * Validación del 商品登録 (Validator::item_edit_validate() del legacy, con Valitron): mismas reglas,
 * etiquetas y mensajes. La usan el alta/edición y la validación AJAX (ajax_edit.php), que responde
 * `{"status":"error","errorMsg":{campo: [mensajes]}}` como espera el JS de edit.php.
 * Valitron no aplica las demás reglas a un campo vacío: aquí, `bail` tras `required`.
 */
class StoreProductRequest extends FormRequest
{
    /** El legacy no comprueba permisos en category2 (solo la sesión) */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::legacyRules($this->all());
    }

    public function messages(): array
    {
        return self::legacyMessages();
    }

    public function attributes(): array
    {
        return self::legacyAttributes();
    }

    public static function legacyRules(array $data): array
    {
        $rules = [
            'name' => ['required'],
            'display_name' => ['required'],
            'display_title' => ['required'],
            'discription' => ['required'],
            'discription_detail' => ['required'],
            'is_exchangeable_of_point' => ['bail', 'required', 'numeric'],
            'available_shop_cds' => ['bail', 'required', 'array'],
            'hyouji_flg' => ['bail', 'required', 'numeric'],
        ];
        // if ($this->post['is_exchangeable_of_point']): requiredWith + solo dígitos
        if (! empty($data['is_exchangeable_of_point'])) {
            $rules['number_of_points_exchanged'] = ['bail', 'required', 'regex:/^[0-9]+$/'];
        }

        return $rules;
    }

    public static function legacyMessages(): array
    {
        return [
            'required' => ':attributeを入力してください',
            'numeric' => ':attributeを正しく選択してください',
            'array' => ':attributeを正しく選択してください',
            'regex' => ':attributeは数字で入力してください',
        ];
    }

    public static function legacyAttributes(): array
    {
        return [
            'name' => '商品名(正式)',
            'display_name' => '商品名(表示用)',
            'display_title' => '表示見出し',
            'discription' => '一覧用説明',
            'discription_detail' => '詳細用説明',
            'is_exchangeable_of_point' => 'ポイントと交換するかどうか',
            'number_of_points_exchanged' => 'ポイント交換数',
            'available_shop_cds' => '使用可能な店舗',
            'hyouji_flg' => '表示',
        ];
    }

    /** Validador suelto para ajax_edit.php (responde JSON propio, no redirige) */
    public static function validatorFor(array $data): Validator
    {
        return ValidatorFacade::make($data, self::legacyRules($data), self::legacyMessages(), self::legacyAttributes());
    }
}
