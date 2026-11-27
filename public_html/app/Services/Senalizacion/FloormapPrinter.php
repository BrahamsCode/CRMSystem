<?php

namespace App\Services\Senalizacion;

use App\Models\Mypage\Recruit;
use App\Models\Promotions\MycouponMaster;
use App\Models\Shop;
use App\Services\Promotions\CouponService;
use Illuminate\Http\Request;

/**
 * digitalsignage/fla/floormap/ajax_print_crawl.php: impresión desde la pantalla インフォメーション
 * (floormapPrintAction(cd, tipo)). print_type 「クーポン」 = cupón de la tienda cd;
 * 「求人情報」 = el 求人 cd. Mismo HTML que el legacy (incluidas las imágenes de ejemplo sample.jpg
 * y fla/floormap/sampleqr.jpg); los textos de la BD van escapados.
 *
 * Diferencia: el 有効期限 sale del cupón impreso (el legacy usaba uno al azar de coupon_kubun 1).
 */
class FloormapPrinter
{
    public const TYPE_COUPON = 'クーポン';

    public const TYPE_RECRUIT = '求人情報';

    public function __construct(private CouponService $coupons, private CouponTicket $ticket) {}

    public function render(Request $request): string
    {
        return match ((string) $request->input('print_type')) {
            self::TYPE_COUPON => $this->coupon((int) $request->input('cd')),
            self::TYPE_RECRUIT => $this->recruit((int) $request->input('cd')),
            default => '',
        };
    }

    private function coupon(int $shopCd): string
    {
        $shop = Shop::withDeleted()->find($shopCd);
        if (! $shop) {
            return '';
        }

        $master = MycouponMaster::query()->where('tb_shop_cd', $shopCd)->orderBy('update_date')->first();
        if (! $master) {
            return '<script>alert("現在クーポンはありません。");</script>';
        }

        $h = fn ($value) => LegacyHtml::h($value);
        $attention = array_pad(explode('<>', (string) $master->attention), 4, '');

        $html = "☆☆☆☆☆☆☆☆☆☆☆<br />\n";
        $html .= '<span style="font-weight:bold">';
        $html .= $h($master->title)."<br />\n";
        $html .= '</span>';
        $html .= "☆☆☆☆☆☆☆☆☆☆☆<br />\n";
        $html .= "<br />\n";
        $html .= $h($master->comment).'<br />';
        $html .= "<br />\n<br />\n";
        $html .= '<span style="font-size:10px">';
        for ($i = 0; $i < 4; $i++) {
            $html .= $h($attention[$i]).'<br />';
        }
        $html .= '</span>';

        $html .= '<span style="font-size:12px">';
        $html .= '有効期限：'.$this->ticket->date($this->coupons->timeLimit($master, now()) ?? 0, 'Y/m/d').'<br />';
        $html .= '</span>';
        $html .= "<br />\n";
        $html .= '<img src="sample.jpg"><br />';

        return $html.$this->footer($shop);
    }

    private function recruit(int $recruitCd): string
    {
        $recruit = Recruit::query()->with('category')->find($recruitCd);
        if (! $recruit) {
            return '<script>alert("現在求人情報はありません。");</script>';
        }
        $shop = Shop::withDeleted()->find($recruit->tb_shop_cd);
        if (! $shop) {
            return '';
        }

        $h = fn ($value) => LegacyHtml::h($value);
        $html = "■■■■■■■■■■■<br />\n";
        $html .= '<span style="font-weight:bold">';
        $html .= $h($recruit->boshu_headline)."<br />\n";
        $html .= '</span>';
        $html .= "■■■■■■■■■■■<br />\n";
        $html .= '<span style="font-size:12px">';
        $html .= "<br />\n";

        $blocks = [
            '募集内容' => $recruit->category?->shokushu,
            '仕事内容' => $recruit->shigoto_naiyou,
            '給与形態' => $recruit->koyou_keitai,
            '応募資格' => $recruit->oubo_shikaku,
            '勤務地' => $recruit->kinmu_chi,
            '勤務時間' => $recruit->kinmu_jikan,
            '給与' => $recruit->kyuyo,
            '休暇' => $recruit->kyuka,
            '待遇' => $recruit->taigu,
        ];
        foreach ($blocks as $label => $value) {
            $html .= "【{$label}】<br />\n".$h($value).'<br /><br />';
        }

        $html .= "<br />\n<br />\n";
        $html .= '</span>';
        $html .= '<img src="sample.jpg"><br />';

        return $html.$this->footer($shop);
    }

    /** フッター común a los dos impresos */
    private function footer(Shop $shop): string
    {
        $h = fn ($value) => LegacyHtml::h($value);

        $html = "━━━━━━━━━━━<br />\n";
        $html .= '<span style="font-size:12px">';
        $html .= $h($shop->kaishamei)."<br />\n";
        $html .= '</span>';
        $html .= '<div style="text-align: center">';
        $html .= '<img src="fla/floormap/sampleqr.jpg"><br />';
        $html .= '<span style="font-size: 12px">お得な会員登録はコチラ';
        $html .= '</div>';
        $html .= "<br />\n";
        $html .= '<span style="font-size:10px">';
        if ($shop->tel) {
            $html .= $h($shop->tel)."<br />\n";
        }
        if ($shop->fax) {
            $html .= $h($shop->fax)."<br />\n";
        }
        $html .= $h($shop->shop_url)."<br />\n";
        $html .= $h($shop->email)."<br />\n";
        $html .= '</span>';
        $html .= "━━━━━━━━━━━<br />\n";
        $html .= "<br />\n<br />\n.\n";

        return $html.'<script>imprime();</script>';
    }
}
