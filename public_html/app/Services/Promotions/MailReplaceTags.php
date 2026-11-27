<?php

namespace App\Services\Promotions;

use App\Models\ContentsMaster;
use Illuminate\Support\Facades\DB;

/**
 * Etiquetas {…} que se pueden poner en un correo y que el envío sustituye por datos del cliente.
 * Lista que muestran mail_template_entry.php y mail_frm.php; las palabras añadidas salen de
 * getWordList() (category3/inc/mailWordsReplace.php).
 *
 * @return list<array{word: string, label: string, note?: string}>
 */
class MailReplaceTags
{
    public function all(): array
    {
        $tags = [['word' => '名前', 'label' => '名前']];

        if (ContentsMaster::text('tb_c1_customer', 'name_input_mode_flg') === '1') {
            $tags[] = ['word' => '苗字', 'label' => '苗字'];
        }

        array_push($tags,
            ['word' => 'ID', 'label' => 'ID番号'],
            ['word' => '誕生月日', 'label' => '誕生月日'],
            ['word' => '住所', 'label' => '住所'],
            ['word' => '電話番号', 'label' => '電話番号'],
        );

        if ($this->childActive('スタンプ管理')) {
            $tags[] = ['word' => 'スタンプ数', 'label' => 'スタンプ数'];
        }
        if ($this->grandchildActive('ポイント管理')) {
            $tags[] = ['word' => 'ポイント数', 'label' => 'ポイント数'];
        }
        if ($this->childActive('来店履歴管理')) {
            $tags[] = ['word' => '最終来店日', 'label' => '最終来店', 'note' => '来店履歴が無い場合はそのまま表示されてしまいますので、ご注意ください。(フォローメール推奨)'];
        }
        if ($this->grandchildActive('顧客ランク（使用金額ランク）')) {
            $tags[] = ['word' => '使用金額ランク', 'label' => '使用金額ランク'];
        }
        if ($this->grandchildActive('顧客ランク（来店回数ランク）')) {
            $tags[] = ['word' => '来店回数ランク', 'label' => '来店回数ランク'];
        }

        foreach ($this->wordList() as $name) {
            $tags[] = ['word' => $name, 'label' => $name];
        }

        return $tags;
    }

    /** getWordList(): campos de búsqueda marcados como palabra de correo (mail_word_flg) */
    public function wordList(): array
    {
        return DB::table('tb_search_form as tb1')
            ->join('tb_search_cate as tb2', 'tb1.tb_search_cate_cd', '=', 'tb2.cd')
            ->where('tb1.mail_word_flg', '1')
            ->whereRaw("COALESCE(tb2.record_type, '') = ''")
            ->where('tb1.disp_flg', '1')
            ->whereIn('tb1.hyouji_flg', ['1', '2'])
            ->where('tb2.disp_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->whereNotIn('tb1.form_type', ['テーブル', 'メニュー項目'])
            ->orderBy('tb1.rank')
            ->pluck('tb1.name')
            ->all();
    }

    private function childActive(string $name): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', $name)->where('system_flg', '1')->exists();
    }

    private function grandchildActive(string $name): bool
    {
        return DB::table('tb_base_system_grandchild')->where('system_name', $name)->where('system_flg', '1')->exists();
    }
}
