<?php

namespace App\Services\Senalizacion;

use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Models\Promotions\MycouponMaster;
use App\Services\Promotions\CouponService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cupones que imprime el reproductor (digitalsignage/ajax_*_print_coupon_crawl*.php). Cada sondeo recibe
 * el «監視時間» de la llamada anterior (<…>_kanshi_time), mira si desde entonces hubo un alta de cliente,
 * una visita o un pedido de impresión en la tienda, y devuelve el HTML del cupón con
 * `<script>imprime();</script>`, más el hidden con el nuevo 監視時間 (el reproductor lo reenvía).
 *
 * Las comparaciones `touroku_date > '<kanshi>'` y `raiten_time > '<kanshi>'` son de texto, como el SQL del legacy
 * (epoch de 10 cifras). Clientes y visitas se cuentan sin filtrar disp_flg, como el legacy.
 *
 * Diferencias deliberadas (ver también CouponTicket):
 * - Un cupón borrado (disp_flg = 9) nunca se imprime (algunos sondeos del legacy no lo miraban).
 * - Si no hay cupón, no se imprime un ticket vacío (ajax_print_coupon_crawl.php lo hacía).
 * - ajax_kaiin_raiten_print_coupon_crawl.php: si llega tb_mycoupon_master_cd (el cupón de フェリカ del programa)
 *   se imprime ese; el legacy lo ignoraba y sacaba uno al azar de coupon_kubun 6 (se mantiene sin él).
 */
class CouponPrintCrawler
{
    public function __construct(private CouponTicket $ticket, private CouponService $coupons) {}

    /** ajax_touch_print_coupon_crawl.php (v1: al azar de kubun 1) y _2.php (v2: el de tb_mycoupon_master_cd, kubun 8) */
    public function touch(Request $request, bool $v2): string
    {
        $master = $v2
            ? $this->shown(MycouponMaster::KUBUN_SIGNAGE)->whereKey((int) $request->input('tb_mycoupon_master_cd'))->first()
            : $this->shown(MycouponMaster::KUBUN_PRINT_ANYONE)->inRandomOrder()->first();

        if (! $master) {
            return '';
        }

        return $this->ticketFor($master, $request).CouponTicket::PRINT_SCRIPT;
    }

    /** ajax_shinkyaku_print_coupon_crawl.php (v1) y _2.php (v2): alta de cliente nuevo en la tienda */
    public function shinkyaku(Request $request, bool $v2): string
    {
        $field = 'ShinkyakuPrintCoupon_kanshi_time';
        $kanshi = (string) $request->input($field);

        $body = null;
        if ($this->newCustomers($request, $kanshi)) {
            $master = $v2
                ? $this->shown(MycouponMaster::KUBUN_SIGNAGE)->whereKey((int) $request->input('tb_mycoupon_master_cd'))->first()
                : $this->shown(MycouponMaster::KUBUN_PRINT_ANYONE)->inRandomOrder()->first();
            $body = $this->printed($master, $request);
        }

        return $this->withKanshi($body, $field);
    }

    /** ajax_Mujyouken_print_coupon_crawl_2.php: alta de cliente o visita (フェリカ 無条件) */
    public function mujyouken(Request $request): string
    {
        $field = 'mujyoukenPrintCoupon_kanshi_time';
        $kanshi = (string) $request->input($field);

        $body = null;
        if ($this->newCustomers($request, $kanshi) || $this->newVisits($request, $kanshi)) {
            $master = MycouponMaster::query()->find((int) $request->input('tb_mycoupon_master_cd'));
            $body = $this->printed($master, $request);
        }

        return $this->withKanshi($body, $field);
    }

    /** ajax_kaiin_raiten_print_coupon_crawl.php: visita de un socio (フェリカ 会員来店時) */
    public function kaiinRaiten(Request $request): string
    {
        $field = 'KaiinRaitenPrintCoupon_kanshi_time';
        $kanshi = (string) $request->input($field);

        $body = null;
        if ($this->newVisits($request, $kanshi)) {
            $master = $request->filled('tb_mycoupon_master_cd')
                ? $this->shown(MycouponMaster::KUBUN_SIGNAGE)->whereKey((int) $request->input('tb_mycoupon_master_cd'))->first()
                : $this->shown(MycouponMaster::KUBUN_VISIT_PRINT)->inRandomOrder()->first();
            $body = $this->printed($master, $request);
        }

        return $this->withKanshi($body, $field);
    }

    /**
     * ajax_print_coupon_crawl.php (al azar de kubun 1, comentario sin nl2br) y ajax_print_coupon_craw_2l.php
     * (kubun 7 por tb_mycoupon_master_cd): visita en la tienda. El legacy sacaba el 有効期限 de una variable
     * inexistente (siempre 1970年1月1日): aquí, el del cupón.
     */
    public function visitCoupon(Request $request, bool $v2): string
    {
        $field = 'kanshi_time';
        $kanshi = (string) $request->input($field);

        $body = null;
        if ($this->newVisits($request, $kanshi)) {
            $master = $v2
                ? MycouponMaster::query()->where('coupon_kubun', MycouponMaster::KUBUN_GAME)->whereKey((int) $request->input('tb_mycoupon_master_cd'))->first()
                : MycouponMaster::query()->where('coupon_kubun', MycouponMaster::KUBUN_PRINT_ANYONE)->inRandomOrder()->first();
            $body = $master
                ? $this->ticket->render(
                    $master,
                    $this->ticket->shop($request->input('shop_cd')),
                    $this->ticket->date($this->coupons->timeLimit($master, now()) ?? 0, 'Y年n月j日'),
                    nl2br: $v2,
                ).CouponTicket::PRINT_SCRIPT
                : '';
        }

        return $this->withKanshi($body, $field);
    }

    /**
     * ajax_kaiin_mycoupon_print_crawl.php: el socio pidió imprimir sus cupones desde マイページ
     * (fila en tb_mycoupon_print de la tienda). Imprime los cupones del cliente con print_flg = tienda
     * y los marca como usados con CouponService::markUsed(), como el legacy.
     */
    public function mycoupon(Request $request): string
    {
        $field = 'KaiinMycouponPrint_kanshi_time';
        $kanshi = (string) $request->input($field);
        $shopCd = (int) $request->input('shop_cd');

        $html = '';
        // tb_mycoupon_print no tiene modelo (solo se lee aquí): query builder
        $customerCd = DB::table('tb_mycoupon_print')
            ->where('tb_shop_cd', $shopCd)
            ->where('touroku_date', '>', $kanshi)
            ->orderBy('cd')
            ->get(['tb_customer_cd'])
            ->last()?->tb_customer_cd;

        if ($customerCd !== null) {
            $shop = $this->ticket->shop($shopCd);
            foreach ($this->coupons->printableFor((int) $customerCd, $shopCd)->values() as $row => $coupon) {
                $master = MycouponMaster::query()->find($coupon->tb_mycoupon_master_cd);
                if (! $master) {
                    continue;
                }

                $html .= $this->ticket->render($master, $shop, $this->ticket->date($coupon->time_limit, 'Y年n月j日'), pageBreak: $row > 0);
                $html .= CouponTicket::PRINT_SCRIPT;

                // 使用済みにする処理
                $this->coupons->markUsed($coupon, $shopCd);
            }
        }

        return $html.$this->kanshiInput($field);
    }

    /** Cupones visibles de un coupon_kubun (disp_flg = '1' AND hyouji_flg = '1') */
    private function shown(string $kubun): Builder
    {
        return MycouponMaster::query()
            ->where('coupon_kubun', $kubun)
            ->where('disp_flg', '1')
            ->where('hyouji_flg', '1');
    }

    private function newCustomers(Request $request, string $kanshi): bool
    {
        return Customer::query()->withoutGlobalScopes()
            ->where('tb_shop_cd', (int) $request->input('shop_cd'))
            ->where('touroku_date', '>', $kanshi)
            ->exists();
    }

    private function newVisits(Request $request, string $kanshi): bool
    {
        return Visit::query()->withoutGlobalScopes()
            ->where('shop_cd', (int) $request->input('shop_cd'))
            ->where('raiten_time', '>', $kanshi)
            ->exists();
    }

    private function ticketFor(MycouponMaster $master, Request $request): string
    {
        return $this->ticket->render($master, $this->ticket->shop($request->input('shop_cd')), $this->ticket->limitFromToday($master));
    }

    /** Ticket + imprime() si hay cupón; '' si no (dentro del «hubo novedad») */
    private function printed(?MycouponMaster $master, Request $request): string
    {
        return $master ? $this->ticketFor($master, $request).CouponTicket::PRINT_SCRIPT : '';
    }

    /** Cola común: el legacy deja "\r\n" tras el bloque impreso (cierre `?>` del if) y siempre el hidden */
    private function withKanshi(?string $body, string $field): string
    {
        return ($body === null ? '' : $body."\r\n").$this->kanshiInput($field);
    }

    private function kanshiInput(string $field): string
    {
        return '<input type="hidden" name="'.$field.'" id="'.$field.'" value="'.now()->getTimestamp().'" />';
    }
}
