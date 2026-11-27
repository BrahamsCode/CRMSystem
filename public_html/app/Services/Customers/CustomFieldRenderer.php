<?php

namespace App\Services\Customers;

use Illuminate\Support\Facades\DB;

/**
 * Valor mostrado de un campo personalizado (`tb_search_form`) del cliente, como lo pintaban
 * `createform_insert_request()` + `createform_confirm()` de `system/inc/function.php` en la ficha
 * (pestañas カルテ/関連情報 y de categoría personalizada).
 *
 * Diferencia con el legacy: los valores y títulos se escapan (sin doble codificar las entidades que ya
 * guardaba get_form2), porque el legacy los imprimía tal cual. El HTML de estructura (tabla, <br />, <li>) es el mismo.
 */
class CustomFieldRenderer
{
    /**
     * @param  object|array  $field  fila de tb_search_form (cd, form_type, titles, data, flds_row, flds_col, unit, style_class)
     * @param  object|array|null  $sub  fila de tb_customer_sub con el valor (text_data, float_data), o null si no hay
     */
    public function render(object|array $field, object|array|null $sub): string
    {
        $field = (array) $field;
        $sub = $sub === null ? [] : (array) $sub;
        $text = (string) ($sub['text_data'] ?? '');

        return match ((string) ($field['form_type'] ?? '')) {
            'テキスト', '半角英数テキスト', 'メールアドレス', 'ラジオボタン', 'セレクト' => $this->esc($text),
            '数値テキスト' => $this->esc((string) ($sub['float_data'] ?? '')),
            'テキストエリア' => nl2br($this->esc($text)),
            'チェックボックス' => $this->checkbox($field, $text),
            '年' => $this->dateParts([$text], ['年']),
            '年月' => $this->dateParts(explode('/', $text), ['年', '月']),
            '年月日', '起算日' => $this->dateParts(explode('/', $text), ['年', '月', '日']),
            '月日' => $this->dateParts(explode('/', $text), ['月', '日']),
            'メニュー項目' => $text !== '' ? $this->menuTable(explode('<>', $text)) : '',
            'テーブル' => $this->table($field, $text),
            // 画像 / 画像とテキスト: el bloque estaba comentado en el legacy (no muestra nada)
            default => '',
        };
    }

    private function esc(string $value): string
    {
        return e($value, false);
    }

    /** Cada opción de `data` marcada en text_data ('a<>b<>') seguida de <br /> */
    private function checkbox(array $field, string $text): string
    {
        $rows = (int) ($field['flds_row'] ?? 0);
        $data = explode('<>', (string) ($field['data'] ?? ''));
        $html = '';
        for ($i = 0; $i < $rows; $i++) {
            $option = $data[$i] ?? '';
            if ($option !== '' && preg_match('/>' . preg_quote($option, '/') . '</', '>' . $text)) {
                $html .= $this->esc($option) . '<br />';
            }
        }

        return $html;
    }

    /** Partes de fecha no vacías con su sufijo (年, 月, 日) */
    private function dateParts(array $parts, array $suffixes): string
    {
        $html = '';
        foreach ($suffixes as $i => $suffix) {
            $part = (string) ($parts[$i] ?? '');
            if ($part !== '') {
                $html .= $this->esc($part) . $suffix;
            }
        }

        return $html;
    }

    /**
     * Tabla: cabecera con `titles` (o el nombre de `select[nombre]:a,b`) y una fila por `flds_col`
     * con los valores 'a<>b||c<>d'. Mismo marcado que el legacy (filas de datos sin <tr> de apertura).
     */
    private function table(array $field, string $text): string
    {
        $rows = (int) ($field['flds_row'] ?? 0);
        $cols = (int) ($field['flds_col'] ?? 0);
        $titles = explode('<>', (string) ($field['titles'] ?? ''));
        $units = explode('<>', (string) ($field['unit'] ?? ''));

        $values = [];
        foreach (explode('||', $text) as $r => $line) {
            foreach (explode('<>', $line) as $c => $value) {
                $values[$r][$c] = $value;
            }
        }

        $html = '<table class="' . $this->esc((string) ($field['style_class'] ?? '')) . "\">\n<tr>";
        for ($row = 0; $row < $rows; $row++) {
            $title = (string) ($titles[$row] ?? '');
            if (preg_match('/^select\[.+\]\:/', $title)) {
                $title = preg_split('/[\[\]]/', $title)[1];
            }
            $html .= '<th>' . $this->esc($title) . '</th>';
        }
        $html .= "\n</tr>";

        for ($col = 0; $col < $cols; $col++) {
            for ($row = 0; $row < $rows; $row++) {
                $value = (string) ($values[$col][$row] ?? '');
                // 値が空のデータは単位を出さない (el legacy lo borraba para las filas siguientes también)
                if ($value === '') {
                    $units[$row] = '';
                }
                $html .= '<td>' . $this->esc($value) . $this->esc((string) ($units[$row] ?? '')) . '</td>';
            }
            $html .= "\n</tr>";
        }

        return $html . '</table>';
    }

    /** makeMenuTable($sub_sql, 'view'): nombres de las メニュー marcadas agrupados por categoría */
    private function menuTable(array $cds): string
    {
        $cds = array_values(array_filter($cds, fn ($cd) => ctype_digit((string) $cd)));
        $menus = $cds === [] ? collect() : DB::table('tb_yoyaku_koumoku_master as tb1')
            ->leftJoin('tb_yoyaku_koumoku_category as tb2', function ($join) {
                $join->on('tb1.tb_yoyaku_koumoku_category_cd', '=', 'tb2.cd')->where('tb2.disp_flg', '1');
            })
            ->where('tb1.disp_flg', '1')
            ->whereIn('tb1.cd', array_map('intval', $cds))
            ->orderBy('tb1.type_flg')->orderBy('tb2.rank')->orderBy('tb1.tb_shop_cd')->orderBy('tb1.rank')
            ->get(['tb1.cd', 'tb1.name', 'tb2.name as category_name']);

        if ($menus->isEmpty()) {
            return '<p>メニューデータはありません</p>' . PHP_EOL;
        }

        $html = '';
        $preCateName = '';
        foreach ($menus as $i => $menu) {
            $html .= '<li';
            if ((string) $menu->category_name !== $preCateName) {
                $html .= ' class="echoKoumokuYoyakuKoMidashi">';
                $html .= (string) $menu->category_name !== '' ? $this->esc($menu->category_name) : 'カテゴリ無し';
                $html .= '</li>' . PHP_EOL . '<li';
            }
            $html .= '>' . PHP_EOL;
            $html .= '<label for="yoyaku_menu_cd_' . (int) $menu->cd . '">' . $this->esc((string) $menu->name) . '</label>' . PHP_EOL;
            $html .= '</li>' . PHP_EOL;
            if ($i === $menus->count() - 1) {
                $html .= '</ul>' . PHP_EOL;
            }
            $preCateName = (string) $menu->category_name;
        }

        return $html;
    }
}
