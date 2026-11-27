<?php

namespace App\Http\Controllers\Publico\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Senalizacion\CouponPrintCrawler;
use App\Services\Senalizacion\FloormapPrinter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Cupones e impresos del reproductor (ajax_*_print_coupon_crawl*.php y fla/floormap/ajax_print_crawl.php) */
class CouponPrintController extends Controller
{
    public function __construct(private CouponPrintCrawler $crawler, private FloormapPrinter $floormap) {}

    /** ajax_touch_print_coupon_crawl.php */
    public function touch(Request $request): Response
    {
        return $this->html($this->crawler->touch($request, v2: false));
    }

    /** ajax_touch_print_coupon_crawl_2.php */
    public function touch2(Request $request): Response
    {
        return $this->html($this->crawler->touch($request, v2: true));
    }

    /** ajax_shinkyaku_print_coupon_crawl.php */
    public function shinkyaku(Request $request): Response
    {
        return $this->html($this->crawler->shinkyaku($request, v2: false));
    }

    /** ajax_shinkyaku_print_coupon_crawl_2.php */
    public function shinkyaku2(Request $request): Response
    {
        return $this->html($this->crawler->shinkyaku($request, v2: true));
    }

    /** ajax_Mujyouken_print_coupon_crawl_2.php */
    public function mujyouken(Request $request): Response
    {
        return $this->html($this->crawler->mujyouken($request));
    }

    /** ajax_kaiin_raiten_print_coupon_crawl.php */
    public function kaiinRaiten(Request $request): Response
    {
        return $this->html($this->crawler->kaiinRaiten($request));
    }

    /** ajax_kaiin_mycoupon_print_crawl.php */
    public function mycoupon(Request $request): Response
    {
        return $this->html($this->crawler->mycoupon($request));
    }

    /** ajax_print_coupon_crawl.php */
    public function visit(Request $request): Response
    {
        return $this->html($this->crawler->visitCoupon($request, v2: false));
    }

    /** ajax_print_coupon_craw_2l.php (sic) */
    public function visit2(Request $request): Response
    {
        return $this->html($this->crawler->visitCoupon($request, v2: true));
    }

    /** fla/floormap/ajax_print_crawl.php */
    public function floormap(Request $request): Response
    {
        return $this->html($this->floormap->render($request));
    }

    private function html(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
