<?php

namespace App\Services\Sales;

use App\Support\LegacyText;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 会員検索 del formulario de venta (legacy: system/category1/karute_menu/ajax_search_kaiin.php).
 * Clientes activos (`disp_flg = '1'`, `touroku_kubun = '2'`), cada palabra (separadas por espacio 半角/全角) debe
 * aparecer en nombre, カナ (ひらがな → カタカナ), ID, 電話 / メール (全角 → 半角), 生年月日, 管理番号 o 携帯.
 * Máximo 200, por último 来店 descendente.
 */
class SaleCustomerSearch
{
    public const LIMIT = 200;

    public function search(string $keyword, ?int $shopCd): Collection
    {
        $keyword = LegacyText::db($keyword);

        return DB::table('tb_customer')
            ->where('disp_flg', '1')
            ->where('touroku_kubun', '2')
            ->when($shopCd !== null, fn (Builder $q) => $q->where('tb_shop_cd', $shopCd))
            ->when($keyword !== '', function (Builder $q) use ($keyword) {
                foreach (explode(' ', str_replace('　', ' ', $keyword)) as $word) {
                    $kana = mb_convert_kana($word, 'KVC');
                    $ascii = mb_convert_kana($word, 'a');
                    $q->where(function (Builder $w) use ($word, $kana, $ascii) {
                        $like = fn (string $value) => '%'.addcslashes($value, '%_\\').'%';
                        $w->where('name', 'like', $like($word))
                            ->orWhere('name_kana', 'like', $like($kana))
                            ->orWhere('id', 'like', $like($word))
                            ->orWhere('tel', 'like', $like($ascii))
                            ->orWhere('email1', 'like', $like($ascii))
                            ->orWhere('kojin_birth', 'like', $like($word))
                            ->orWhere('kanri_id', 'like', $like($word))
                            ->orWhere('kojin_mobile', 'like', $like($word));
                    });
                }
            })
            ->orderByRaw("coalesce(last_raiten_date, '') DESC")->orderByDesc('cd')
            ->limit(self::LIMIT)
            ->get(['cd', 'id', 'name']);
    }
}
