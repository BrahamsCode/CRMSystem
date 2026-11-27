<?php

namespace App\Services\Promotions;

use Illuminate\Support\Facades\DB;

/**
 * 使用区分 de las クーポン: fnc_make_array('マイクーポン') de inc/kihon.php y los filtros de
 * mycoupon_frm.php / mycoupon_list.php (índice = coupon_kubun; 0 = '' メルマガ配信用).
 */
class CouponKubun
{
    /** @return array<int, string> todas las etiquetas por índice (ガチャリン y ポイント交換 según módulos activos) */
    public static function labels(): array
    {
        $labels = ['メルマガ配信用', '誰でも取得可能なクーポン用（印刷）', 'マイページスタンプ交換用', 'アンケート回答時プレゼント用', '会員登録クーポン',
            'お友達紹介時プレゼント用', '会員の来店時に印刷用', 'ゲーム用', 'デジタルサイネージ用'];
        $labels[] = self::child('ガチャリン管理') ? 'ガチャリン用' : '';
        if (DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント交換クーポン')->where('system_flg', '1')->exists()) {
            $labels[] = 'ポイント交換用';
        }

        return $labels;
    }

    /**
     * Opciones del selector [valor => etiqueta]: sin 印刷 (1), ゲーム (7) ni サイネージ (8); 2, 3 y 6 solo con
     * スタンプ管理, アンケート管理 y 情報端末管理 activos; 9 solo con ガチャリン管理.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::labels() as $i => $label) {
            if ($label === '' || in_array($i, [1, 7, 8], true)
                || ($i === 2 && ! self::child('スタンプ管理'))
                || ($i === 3 && ! self::child('アンケート管理'))
                || ($i === 6 && ! self::child('情報端末管理'))) {
                continue;
            }
            $options[$i === 0 ? '' : (string) $i] = $label;
        }

        return $options;
    }

    /** Etiqueta de un coupon_kubun guardado */
    public static function label(?string $kubun): string
    {
        return self::labels()[(int) ($kubun ?: 0)] ?? '';
    }

    private static function child(string $name): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', $name)->where('system_flg', '1')->exists();
    }
}
