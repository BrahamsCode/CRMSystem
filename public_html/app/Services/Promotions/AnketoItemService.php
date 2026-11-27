<?php

namespace App\Services\Promotions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ítems (項目 / 部品) de la encuesta: anketo_form_register.php, el 必須 / 回答一覧に表示 de anketo_form_list.php
 * (act=update), el orden ▲▼ (thisCd/thisRow/kind) y anketo_form_delete.php (DELETE físico, como el legacy).
 */
class AnketoItemService
{
    /** Tipos con opciones (表示値/送信値) */
    public const OPTION_TYPES = ['チェックボックス', 'セレクト', 'ラジオボタン'];

    /** Tipos que ofrece el formulario (el legacy dejó comentados メールアドレス, 起算日, テーブル, 画像…) */
    public const TYPES = ['テキスト', 'テキストエリア', 'チェックボックス', 'セレクト', 'ラジオボタン', '年', '年月', '年月日'];

    /**
     * anketo_form_register.php. $data: el POST limpio (get_form). Alta: hatuban, `serach_flg = '1'` y
     * `rank` = último + 1 entre los ítems no borrados de la encuesta.
     */
    public function save(AnketoFamily $family, int $cateCd, array $data, ?int $cd = null): Model
    {
        $rows = $this->int($data['flds_row'] ?? null);
        $titles = [];
        $values = [];
        for ($i = 0; $i < (int) $rows; $i++) {
            $titles[] = (string) ($data['eachform'.$i] ?? '');
            $values[] = (string) ($data['eachsend'.$i] ?? '');
        }

        $row = [
            'hyouji_flg' => '1',
            'tb_c3_anketo_cate_cd' => $cateCd,
            'name' => (string) ($data['name'] ?? ''),
            'unit' => (string) ($data['unit'] ?? ''),
            'form_type' => (string) ($data['form_type'] ?? ''),
            'titles' => implode('<>', $titles),
            'data' => implode('<>', $values),
            'comment' => (string) ($data['comment'] ?? ''),
            'size_w' => ($data['size_w0'] ?? '').'<>'.($data['size_w1'] ?? '').'<>'.($data['size_w2'] ?? ''),
            'size_h' => (string) ($data['size_h'] ?? ''),
            'style_class' => (string) ($data['style_class'] ?? ''),
            'flds_col' => $this->int($data['flds_col'] ?? null),
            'flds_row' => $rows,
            'disp_flg' => '1',
        ];

        return DB::transaction(function () use ($family, $cateCd, $row, $cd) {
            if ($cd === null) {
                $rank = (int) $family->forms()->withDeleted()->where('tb_c3_anketo_cate_cd', $cateCd)
                    ->whereRaw("COALESCE(disp_flg, '') <> '9'")->max('rank');

                return $family->forms()->create($row + ['serach_flg' => '1', 'rank' => $rank + 1]);
            }
            $form = $family->forms()->withDeleted()->findOrFail($cd);
            $form->fill($row)->save();

            return $form;
        });
    }

    /**
     * «必須情報を更新» (anketo) / «必須設定・回答一覧表示設定を更新» (anketo2): por fila `cd<i>` guarda `hissu<i>`;
     * el legacy también escribe serach_flg y hyouji_flg con lo que llega (anketo no los envía: quedan vacíos;
     * anketo2 envía hyouji_flg = -1 «回答一覧に表示»). Solo filas de la encuesta indicada.
     */
    public function updateFlags(AnketoFamily $family, int $cateCd, array $input): void
    {
        $max = (int) ($input['update_max'] ?? 0);
        for ($i = 0; $i < $max; $i++) {
            $cd = (int) ($input['cd'.$i] ?? 0);
            if ($cd === 0) {
                continue;
            }
            $family->forms()->withDeleted()->whereKey($cd)->where('tb_c3_anketo_cate_cd', $cateCd)->update([
                'hissu' => $this->flag($input['hissu'.$i] ?? ''),
                'serach_flg' => $this->flag($input['serach_flg'.$i] ?? ''),
                'hyouji_flg' => $this->flag($input['hyouji_flg'.$i] ?? ''),
            ]);
        }
    }

    /** ▲▼: intercambia el rank con el vecino (no borrado) y renumera 1..n por rank, como el legacy */
    public function move(AnketoFamily $family, int $cateCd, int $cd, int $thisRank, string $kind): void
    {
        if ($thisRank <= 0 || ! in_array($kind, ['up', 'down'], true)) {
            return;
        }
        $alive = fn () => $family->forms()->withDeleted()->where('tb_c3_anketo_cate_cd', $cateCd)->whereRaw("COALESCE(disp_flg, '') <> '9'");

        DB::transaction(function () use ($alive, $cd, $thisRank, $kind) {
            $neighbor = $kind === 'up'
                ? $alive()->where('rank', '<', $thisRank)->orderByDesc('rank')->first()
                : $alive()->where('rank', '>', $thisRank)->orderBy('rank')->first();
            if ($neighbor) {
                $alive()->whereKey($neighbor->cd)->update(['rank' => $thisRank]);
                $alive()->whereKey($cd)->update(['rank' => $neighbor->rank]);
            }
            foreach ($alive()->orderBy('rank')->pluck('cd')->values() as $i => $formCd) {
                $alive()->whereKey($formCd)->update(['rank' => $i + 1]);
            }
        });
    }

    /** anketo_form_delete.php: DELETE físico (las respuestas guardadas no se tocan) */
    public function delete(AnketoFamily $family, int $cateCd, int $cd): void
    {
        $family->forms()->withDeleted()->whereKey($cd)->where('tb_c3_anketo_cate_cd', $cateCd)->forceDelete();
    }

    /** Ítem para el formulario de edición: titles/data repartidos en eachform<i>/eachsend<i> y size_w en size_w0..2 */
    public function formData(Model $form): array
    {
        $data = $form->getAttributes();
        [$data['size_w0'], $data['size_w1'], $data['size_w2']] = array_pad(explode('<>', (string) $form->size_w), 3, '');
        $titles = explode('<>', (string) $form->titles);
        $values = explode('<>', (string) $form->data);
        for ($i = 0; $i < (int) $form->flds_row; $i++) {
            $data['eachform'.$i] = $titles[$i] ?? '';
            $data['eachsend'.$i] = $values[$i] ?? '';
        }

        return $data;
    }

    private function int(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value !== 0 ? (int) $value : null;
    }

    /** Banderas de casilla: solo los valores que pone el formulario del legacy ('1', '-1') o vacío */
    private function flag(mixed $value): string
    {
        return in_array((string) $value, ['1', '-1'], true) ? (string) $value : '';
    }
}
