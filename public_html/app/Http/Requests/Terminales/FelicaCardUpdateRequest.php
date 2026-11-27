<?php

namespace App\Http\Requests\Terminales;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * «更新する» de カード管理 (card_list.php con act): filas numeradas `shop_cd<N>`, `main_table_cd<N>`,
 * `kaiin_cd<N>` hasta `cnt`. El legacy limpiaba $_POST con get_form() (LegacyText::form()).
 * `felica_no<N>` se ignora: el IDm se toma de la tarjeta en la BD, no del formulario.
 */
class FelicaCardUpdateRequest extends FormRequest
{
    /** Tope de filas (el selector 表示件数 llega a 50) */
    private const MAX_ROWS = 50;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach ($this->all() as $key => $value) {
            $clean[$key] = is_array($value) ? $value : LegacyText::form($value);
        }
        $this->replace($clean);
    }

    public function rules(): array
    {
        return [
            'cnt' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer'],
            'kensu' => ['nullable', 'integer'],
        ];
    }

    /**
     * Filas válidas, como el legacy: con tienda y cd numéricos; socio opcional.
     *
     * @return list<array{cd:int, shop_cd:int, kaiin_cd:?int}>
     */
    public function rows(): array
    {
        $rows = [];
        $count = min((int) $this->input('cnt'), self::MAX_ROWS);
        for ($i = 0; $i < $count; $i++) {
            $shopCd = (string) $this->input('shop_cd' . $i);
            $cd = (string) $this->input('main_table_cd' . $i);
            if (! ctype_digit($shopCd) || ! ctype_digit($cd) || (int) $shopCd === 0 || (int) $cd === 0) {
                continue;
            }
            $kaiinCd = (string) $this->input('kaiin_cd' . $i);
            $rows[] = [
                'cd' => (int) $cd,
                'shop_cd' => (int) $shopCd,
                'kaiin_cd' => ctype_digit($kaiinCd) && (int) $kaiinCd > 0 ? (int) $kaiinCd : null,
            ];
        }

        return $rows;
    }
}
