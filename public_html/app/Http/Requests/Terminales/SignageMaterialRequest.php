<?php

namespace App\Http\Requests\Terminales;

use App\Models\Terminales\SignageMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 放映素材 alta/edición (screen_saver_frm.php): タイトル, コメント y ファイルタイプ obligatorios.
 * Los archivos no se validan aquí: como pic_up.php, un archivo inválido deja un aviso en la
 * pantalla «登録中です。» y el resto se guarda (SignageMaterialUploader).
 */
class SignageMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'comment' => ['required', 'string', 'max:100'],
            'filetype' => ['required', Rule::in([
                SignageMaterial::FILETYPE_IMAGE, SignageMaterial::FILETYPE_SWF, SignageMaterial::FILETYPE_FLV,
            ])],
            'categorize_value_0' => ['nullable', 'string'],
            'categorize_value_0_new' => ['nullable', 'string', 'max:10'],
            'play_frame_sec' => ['nullable', 'string', 'max:3'],
            'tb_display_cd' => ['nullable', 'integer'],
            'hb_which' => ['nullable', 'in:0,1'],
            'pic_flg' => ['nullable', 'array'],
            'pic_flg.*' => ['nullable', 'in:0,1'],
            'pic_extension' => ['nullable', 'array'],
            'pic_extension.*' => ['nullable', 'string', 'max:10'],
            'page' => ['nullable'],
            'kensu' => ['nullable'],
        ];
    }

    /** Mensajes del legacy (la vista antepone «※») */
    public function messages(): array
    {
        return [
            'required' => '必須項目です。',
            'filetype.in' => '必須項目です。',
        ];
    }
}
