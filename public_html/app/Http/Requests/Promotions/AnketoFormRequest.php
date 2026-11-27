<?php

namespace App\Http\Requests\Promotions;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * anketo_form_new.php / anketo_form_update.php con act=up: el POST limpio con get_form (tras form2Db) y las
 * comprobaciones de anketo_form_errchk.php. Como el legacy, el formulario se vuelve a pintar con los mensajes.
 */
class AnketoFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    /** $data_array del legacy: todo el POST escalar */
    public function legacyData(): array
    {
        return collect($this->except(['_token']))
            ->reject(fn ($v) => is_array($v))
            ->map(fn ($v) => LegacyText::form(LegacyText::db($v)))
            ->all();
    }

    /**
     * anketo_form_errchk.php: 項目タイトル obligatorio y tamaños de imagen en números (los mensajes van al campo flds_row).
     *
     * @return array<string, string> campo => HTML del mensaje
     */
    public function legacyErrors(bool $customer): array
    {
        $data = $this->legacyData();
        $errors = [];
        if (($data['name'] ?? '') === '') {
            $errors['name'] = '<p class="m0">'.($customer ? '項目タイトル' : 'フォームタイトル').'は必ず入力して下さい。</p>';
        }

        $sizes = ['size_w0' => '画像サイズ（横）', 'size_h' => '画像サイズ（縦）', 'size_w1' => '画像サイズ（自動縮小１）', 'size_w2' => '画像サイズ（自動縮小２）'];
        foreach ($sizes as $field => $label) {
            if (($data[$field] ?? '') !== '' && ! preg_match('/^[0-9]+$/', $data[$field])) {
                $errors['flds_row'] = ($errors['flds_row'] ?? '').'<p class="m0">'.$label.'は必ず半角数字で入力して下さい。</p>';
            }
        }

        return $errors;
    }
}
