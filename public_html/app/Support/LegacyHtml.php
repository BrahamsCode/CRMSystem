<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Fragmentos HTML comunes del legacy, mismo marcado:
 * - tooltip($n): inc/tooltip.php con el texto $tooltip_sentence[$n] de inc/tooltip_define.php.
 * - prefOptions($valor): make_pref_options() de inc/kihon.php.
 * - areaOptions($cd): árbol de エリア como <option> (areaTreeFroSelect de category0/shop/shop_new.php).
 */
class LegacyHtml
{
    /** $tooltip_sentence de inc/tooltip_define.php */
    public const TOOLTIPS = [
        0 => '※ 入力内容は半角数字とし、ハイフン(-)で連結せずに入力してください。',
        1 => '※ 月日は2桁で入力し、入力項目はスラッシュ(/)で連結して入力して下さい。<br />例) 2009/04/18',
        2 => 'ハイフン(-)で連結して入力してください。<br />例) 3-5-3',
        3 => '※ 例) 総合太郎',
        4 => '※ 全角カタカナで入力してください。<br />例) ソウゴウタロウ',
        5 => '※ 例) 株式会社総合業務管理システム',
        6 => '※ 入力内容は法人格を省いて入力してください。<br />例) ソウゴウカンリシステム',
        7 => '※ 例1) 株式会社総合業務管理システム<br />例2) 総合太郎',
        8 => '※ 入力内容は法人格を省いて、全角カナで入力してください。<br />例1) ソウゴウカンリシステム<br />例2) ソウゴウタロウ',
        9 => '例) 千代田区 千代田',
        10 => '例) 総合管理ビルディング3階D号',
        11 => '例) ソウゴウカンリビルディング3カイDゴウ',
        12 => '※ 登記されていない団体の意場合は不要<br />月日は2桁で入力し、入力項目はスラッシュ(/)で連結して入力して下さい。<br />例) 1985/09/21',
        13 => '※ 登録する画像は、jpg,gif,png形式で登録してください。ファイルサイズは2M未満でご登録ください。',
        40 => '例) 千代田区 千代田',
        41 => '例) チヨダク チヨダ',
        50 => '※ セレクトボックスに該当部署がない場合は、何も選択せずにフリースペースにご記入ください。',
        100 => '※ 半角英数のみで入力してください。',
        200 => '<hr /><font color="red" style="font-weight: bold;">送信するメール</font><br /> 山田太郎 様。<br />4月15日 に誕生日を迎えられるそうで、おめでとうございます。<br />誕生日プレゼントを差し上げます。<br /><br />プレゼントの送り先は 大阪府大阪市梅田。<br />あなたの連絡先は 123456789 です。<br />',
    ];

    public static function tooltip(int $n): HtmlString
    {
        return new HtmlString("<dl class=\"toolTip\">\n<dt class=\"toolTipFig\"><img src=\"/system/img/icon_help.gif\" /></dt>\n<dd class=\"toolTipBody\" style=\"background:#FFFFCF;\">"
            . (self::TOOLTIPS[$n] ?? '') . "</dd>\n</dl>");
    }

    public static function prefOptions(?string $value = ''): HtmlString
    {
        $html = "<option value=\"\">▼都道府県選択</option>\n";
        foreach (config('crm.prefectures') as $pref) {
            $selected = (string) $value === $pref ? 'selected' : '';
            $html .= "<option value=\"{$pref}\" {$selected}>{$pref}</option>\n";
        }

        return new HtmlString($html);
    }

    /**
     * Paginador de las listas del legacy (`<ul class="btnBox2">`): 戻る / números / 次へ.
     * $base = 'shop_list.php?' + condiciones de búsqueda (sin page/kensu).
     */
    public static function pager(string $base, int $page, int $kensu, int $maxrows, string $extra = '&se_flg=2'): HtmlString
    {
        $maxpage = $kensu ? (int) ceil($maxrows / $kensu) : 0;
        $html = '';
        if ($page !== 1 && $kensu < $maxrows) {
            $html .= '<li class="btn"><a href="' . e($base . '&page=' . ($page - 1) . '&kensu=' . $kensu . $extra) . '"><img src="/system/img/btn_back_off.gif" width="92" height="32" alt="戻る" /></a></li>';
        }
        $last = 0;
        if ($maxpage > 1) {
            for ($l = 1; $l <= $maxpage; $l++) {
                $last = $l;
                $html .= $l === $page
                    ? '<li class="now"><b>' . $l . '</b></li>'
                    : '<li><a href="' . e($base . '&page=' . $l . '&kensu=' . $kensu . $extra) . '">' . $l . '</a></li>';
            }
        }
        if ($page < $last) {
            $html .= '<li class="btn"><a href="' . e($base . '&page=' . ($page + 1) . '&kensu=' . $kensu . $extra) . '"><img src="/system/img/btn_next_off.gif" width="92" height="32" alt="次へ" /></a></li>';
        }

        return new HtmlString($html);
    }

    /** Opciones del selector de 表示件数 (5 a 30, de 5 en 5, más una vacía) */
    public static function kensuOptions(int $kensu): HtmlString
    {
        $html = "<option value=\"\" ></option>\n";
        for ($i = 1; $i <= 6; $i++) {
            $n = $i * 5;
            $html .= "<option value=\"{$n}\" " . ($kensu === $n ? 'selected' : '') . ">{$n}</option>\n";
        }

        return new HtmlString($html);
    }

    /** Opciones del árbol de áreas activas (raíz: pid NULL o < 1), con └ y sangría por nivel */
    public static function areaOptions(mixed $selected = null, int $pid = 0, int $level = 0): HtmlString
    {
        $level++;
        $areas = DB::table('tb_area')->where('disp_flg', '<>', '9')->where('flg', '1')
            ->when($pid, fn ($q) => $q->where('pid', $pid), fn ($q) => $q->where(fn ($w) => $w->whereNull('pid')->orWhere('pid', '<', 1)))
            ->orderBy('row_lis')->get();

        $html = '';
        foreach ($areas as $area) {
            $sel = (string) $selected !== '' && (string) $selected === (string) $area->cd ? ' selected' : '';
            $html .= '<option value="' . $area->cd . '"' . $sel . '>' . str_repeat('&emsp;', $level - 1) . ($level > 1 ? '└ ' : '') . e($area->name) . '</option>';
            if (DB::table('tb_area')->where('pid', $area->cd)->exists()) {
                $html .= self::areaOptions($selected, (int) $area->cd, $level);
            }
        }

        return new HtmlString($html);
    }
}
