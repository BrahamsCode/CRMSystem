<?php

namespace App\Services\Customers;

use App\Models\Customers\CustomCategory;
use App\Models\Customers\CustomerSub;
use App\Models\Customers\CustomField;
use App\Support\LegacyText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Campos de una categoría de 顧客追加情報 (legacy `customer/search_form_*.php`, 「検索フォーム部品設定」) sobre `tb_search_form`.
 * Texto con get_form2() (LegacyText::input) como `search_form_new_check.php`: la vista imprime sin escapar.
 * Opciones: `titles` (表示値) y `data` (送信値) unidas con `<>`; en テーブル, `data` guarda la スタイル de cada
 * columna y `unit` los 単位 unidos con `<>`.
 */
class CustomFieldService
{
    /** Los 15 form_type del legacy, en el orden del <select> */
    public const FORM_TYPES = [
        'テキスト', 'メールアドレス', '半角英数テキスト', '数値テキスト', 'テキストエリア', 'チェックボックス', 'セレクト',
        'ラジオボタン', '年', '年月', '年月日', '月日', '起算日', 'テーブル', 'メニュー項目',
    ];

    /** Tipos con opciones (オプション数) */
    public const OPTION_TYPES = ['チェックボックス', 'セレクト', 'ラジオボタン'];

    /** make_stylesheet_options() de inc/kihon.php: clase CSS => texto */
    public const STYLE_CLASSES = [
        'inputD' => 'デフォルト', 'input3S' => '2文字程度', 'input2S' => '3文字程度', 'inputS' => '6文字程度',
        'inputM' => '9文字程度', 'input2M' => '16文字程度', 'inputL' => '24文字程度', 'writeArea' => '40文字程度',
        'checkBtn' => 'チェック・ラジオ用', 'selectBox' => 'セレクトボックス用',
    ];

    public function __construct(private LegacyRankSorter $sorter) {}

    /** make_stylesheet_options(): <option> en una sola línea (los formularios la insertan también dentro de un string JS) */
    public static function styleOptions(mixed $selected = ''): HtmlString
    {
        $html = '<option value="">▼スタイル選択</option>';
        foreach (self::STYLE_CLASSES as $value => $label) {
            $html .= '<option value="'.$value.'" '.((string) $selected === $value ? 'selected' : '').'>'.$label.'</option>';
        }

        return new HtmlString($html);
    }

    /** Campos vivos de la categoría (ordenación: disp_flg <> '9', como el legacy) */
    public function scope(CustomCategory $category): Builder
    {
        return CustomField::where('tb_search_cate_cd', $category->cd)->where('disp_flg', '<>', '9');
    }

    /** Página de la lista (el legacy lista `disp_flg = '1'`, ORDER BY rank) */
    public function paginate(CustomCategory $category, int $page, int $kensu): array
    {
        $kensu = max(1, $kensu);
        $page = max(1, $page);
        $query = fn () => CustomField::where('tb_search_cate_cd', $category->cd)->where('disp_flg', '1');
        $total = $query()->count();
        $from = ($page - 1) * $kensu;

        return [
            'items' => $query()->orderBy('rank')->offset($from)->limit($kensu)->get(),
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'from' => $from,
            'maxPage' => (int) ceil($total / $kensu),
        ];
    }

    /** チェックした情報を更新する: hissuN, serach_flgN, mail_word_flgN, hyouji_flgN de cada cdN */
    public function updateFlags(CustomCategory $category, array $input): void
    {
        $value = fn (string $key) => LegacyText::input($input[$key] ?? '');
        $max = (int) ($input['update_max'] ?? 0);

        DB::transaction(function () use ($category, $value, $max) {
            for ($row = 0; $row < $max; $row++) {
                $this->scope($category)->where('cd', (int) $value("cd{$row}"))->toBase()->update([
                    'hissu' => $value("hissu{$row}"),
                    'serach_flg' => $value("serach_flg{$row}"),
                    'mail_word_flg' => $value("mail_word_flg{$row}"),
                    'hyouji_flg' => $value("hyouji_flg{$row}"),
                ]);
            }
        });
    }

    public function move(CustomCategory $category, int $thisCd, int $thisRow, string $kind): void
    {
        $this->sorter->move(fn () => $this->scope($category), $thisCd, $thisRow, $kind);
    }

    /** search_form_new_up.php: hatuban, serach_flg = '1' y rank = máximo de la categoría + 1 */
    public function create(CustomCategory $category, array $input): CustomField
    {
        return DB::transaction(fn () => CustomField::create([
            'serach_flg' => '1',
            'rank' => (int) $this->scope($category)->max('rank') + 1,
        ] + $this->attributes($category, $input)));
    }

    /** search_form_update_up.php (sin tocar rank ni las banderas) */
    public function update(CustomField $field, array $input): void
    {
        $field->fill($this->attributes($field->category()->withDeleted()->firstOrFail(), $input))->save();
    }

    /** search_form_delete_up.php: disp_flg = 9 en sus valores de clientes y en el campo (sin renumerar, como el legacy) */
    public function delete(CustomField $field): void
    {
        DB::transaction(function () use ($field) {
            CustomerSub::where('tb_search_form_cd', $field->cd)->toBase()->update(['disp_flg' => '9']);
            $field->delete();
        });
    }

    /** Valores del formulario de edición: columnas del campo + eachformN/eachsendN/eachunitN desde titles/data/unit */
    public function formValues(CustomField $field): array
    {
        $values = $field->only(['name', 'unit', 'comment', 'form_type', 'flds_row', 'flds_col', 'style_class']);
        if ((int) $field->flds_row > 0) {
            $titles = explode('<>', (string) $field->titles);
            $data = explode('<>', (string) $field->data);
            $units = explode('<>', (string) $field->unit);
            for ($i = 0; $i < (int) $field->flds_row; $i++) {
                $values["eachform{$i}"] = $titles[$i] ?? '';
                $values["eachsend{$i}"] = $data[$i] ?? '';
                $values["eachunit{$i}"] = $units[$i] ?? '';
            }
        }

        return $values;
    }

    /** El `$select` de search_form_new_check.php */
    private function attributes(CustomCategory $category, array $input): array
    {
        $value = fn (string $key) => LegacyText::input($input[$key] ?? '');
        $formType = $value('form_type');
        $isTable = $formType === 'テーブル';

        $titles = $data = $units = [];
        for ($i = 0; $i < (int) $value('flds_row'); $i++) {
            $titles[] = $value("eachform{$i}");
            $data[] = $value("eachsend{$i}");
            $units[] = $value("eachunit{$i}");
        }

        $attributes = [
            'tb_search_cate_cd' => $category->cd,
            'category' => CustomCategoryService::CATEGORY,
            'name' => $value('name'),
            'unit' => $isTable ? implode('<>', $units) : $value('unit'),
            'form_type' => $formType,
            'titles' => implode('<>', $titles),
            'data' => implode('<>', $data),
            'comment' => $value('comment'),
            'style_class' => $value('style_class'),
            'disp_flg' => '1',
        ];
        // Como el legacy: flds_col / flds_row solo se escriben si llegan con valor
        if ((int) $value('flds_col')) {
            $attributes['flds_col'] = (int) $value('flds_col');
        }
        if ((int) $value('flds_row')) {
            $attributes['flds_row'] = (int) $value('flds_row');
        }

        return $attributes;
    }
}
