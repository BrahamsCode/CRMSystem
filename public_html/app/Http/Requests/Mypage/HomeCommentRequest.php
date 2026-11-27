<?php

namespace App\Http\Requests\Mypage;

use Illuminate\Foundation\Http\FormRequest;

/**
 * マイコレHOMEに追加するコメント (mobile_mycolle_edit.php, type=home_comment):
 * home_comment[0 | shop_cd] y mypage_config[comment_flg][-1 | shop_cd].
 */
class HomeCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_comment' => ['nullable', 'array'],
            'home_comment.*' => ['nullable', 'string'],
            'mypage_config' => ['nullable', 'array'],
            'mypage_config.comment_flg' => ['nullable', 'array'],
        ];
    }

    /** Comentarios por clave (0 = 全店舗), con '' donde el middleware dejó null */
    public function comments(): array
    {
        return array_map(fn ($text) => (string) $text, (array) $this->input('home_comment', []));
    }

    /** Solo se guarda comment_flg, la única clave de mypage_config que envía este formulario */
    public function configValues(): array
    {
        return array_intersect_key((array) $this->input('mypage_config', []), ['comment_flg' => true]);
    }
}
