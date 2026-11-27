<?php

namespace App\Services\Senalizacion;

use App\Models\Promotions\MycouponMaster;
use App\Models\Shop;
use App\Services\Promotions\CouponService;
use Illuminate\Support\Carbon;

/**
 * HTML del cupón impreso por el reproductor (`<div class="kobetsuPrintArea">` de los
 * ajax_*_print_coupon_crawl*.php): mismo orden, mismas clases y mismos saltos que el legacy.
 * Diferencia: los textos de la BD se escapan con h() (el legacy los imprimía tal cual).
 */
class CouponTicket
{
    public const PRINT_SCRIPT = '<script type="text/javascript">imprime();</script>';

    public function __construct(private CouponService $coupons) {}

    /**
     * @param  string  $limit  texto ya formateado de 有効期限
     * @param  bool  $nl2br  el comentario con nl2br (todos menos ajax_print_coupon_crawl.php)
     * @param  bool  $pageBreak  cupones 2.º y siguientes de ajax_kaiin_mycoupon_print_crawl.php
     */
    public function render(MycouponMaster $master, ?Shop $shop, string $limit, bool $nl2br = true, bool $pageBreak = false): string
    {
        $h = fn ($value) => LegacyHtml::h($value);
        $comment = $nl2br ? nl2br($h($master->comment)) : $h($master->comment);

        $html = $pageBreak
            ? "<div class=\"kobetsuPrintArea\" style=\"page-break-before: always;\">\n"
            : "<div class=\"kobetsuPrintArea\">\n";
        $html .= 'NO.'.$master->cd."<br />\n";
        $html .= '<span class="couponTitle">'.$h($master->title).'</span>'."<br />\n";
        $html .= '<span class="couponComment">'.$comment.'</span>'."<br />\n";

        // 注意事項を分割
        foreach (explode('<>', (string) $master->attention) as $attention) {
            $html .= $h($attention)."<br />\n";
        }

        $html .= '<span class="couponLimit">有効期限：'.$limit.'</span>'."<br />\n";
        $html .= '<hr />';

        // 会社情報
        $html .= $h($shop?->kaishamei)."<br />\n";
        $html .= $h($shop?->zip)."<br />\n";
        $html .= $h($shop?->pref).$h($shop?->add).$h($shop?->add_number)."<br />\n";
        $html .= $h($shop?->add_build)."<br />\n";
        $html .= $h($shop?->tel)."<br />\n";
        $html .= $h($shop?->email)."<br />\n";
        $html .= $h($shop?->shop_url)."<br />\n";

        return $html.'</div>';
    }

    /**
     * 有効期限 «Y/m/d» de un cupón impreso hoy según su limit_flg.
     * El legacy lo calculaba con OTRO cupón (ORDER BY RANDOM() de coupon_kubun 1 u 8); aquí, el impreso.
     * Sin vencimiento sale 1970/01/01, como date("Y/m/d", 0) del legacy.
     */
    public function limitFromToday(MycouponMaster $master): string
    {
        return $this->date($this->coupons->timeLimit($master, now()) ?? 0, 'Y/m/d');
    }

    public function date(int|string|null $epoch, string $format): string
    {
        return Carbon::createFromTimestamp((int) $epoch, config('app.timezone'))->format($format);
    }

    public function shop(mixed $shopCd): ?Shop
    {
        return Shop::withDeleted()->find((int) $shopCd);
    }
}
