<?php

namespace App\Services\Promotions;

use App\Models\Promotions\Mycoupon;
use App\Models\Promotions\MycouponMaster;
use App\Models\Promotions\MycouponUsed;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Entrega, uso e impresión de cupones sobre las tablas del legacy.
 *
 * - issue(): como mycoupon_hakkou.php / cMailsoushin (tb_mycoupon con time_limit por limit_flg).
 * - markUsed(): como appview/coupon_control.php y digitalsignage/ajax_kaiin_mycoupon_print_crawl.php:
 *   copia la fila a tb_mycoupon_used con la tienda y la hora del uso, y la borra de tb_mycoupon.
 * - Impresión: tb_mycoupon.print_flg guarda el cd de la tienda donde el cupón está pendiente de imprimir;
 *   el reproductor `digitalsignage` (S4) los pide con printableFor() y, al imprimir, llama a markUsed().
 *
 * Lo usan S4 (señalización, Mi página) y S5 (appview): no escriben estas tablas directamente.
 */
class CouponService
{
    /** Vencimiento (epoch) de un cupón entregado en $issuedAt según tb_mycoupon_master.limit_flg */
    public function timeLimit(MycouponMaster $master, CarbonInterface $issuedAt): ?int
    {
        $from = $issuedAt->getTimestamp();
        $day = 60 * 60 * 24;

        return match ((string) $master->limit_flg) {
            MycouponMaster::LIMIT_WEEK => $from + $day * 7,
            MycouponMaster::LIMIT_MONTH => $from + $day * 30,
            MycouponMaster::LIMIT_THREE_MONTHS => $from + $day * 90,
            MycouponMaster::LIMIT_DAYS => $from + $day * (int) $master->time_limit,
            MycouponMaster::LIMIT_DATE => $master->time_limit === null || $master->time_limit === '' ? null : (int) $master->time_limit,
            default => null,
        };
    }

    /** Entrega el cupón a un cliente (una fila en tb_mycoupon con cd de hatuban) */
    public function issue(MycouponMaster $master, int $customerCd, ?CarbonInterface $at = null): Mycoupon
    {
        $at ??= now();

        return Mycoupon::create([
            'tb_mycoupon_master_cd' => $master->cd,
            'tb_shop_cd' => $master->tb_shop_cd,
            'tb_customer_cd' => $customerCd,
            'coupon_kubun' => $master->coupon_kubun,
            'touroku_date' => (string) $at->getTimestamp(),
            'time_limit' => ($limit = $this->timeLimit($master, $at)) === null ? null : (string) $limit,
        ]);
    }

    /**
     * Marca el cupón como usado en una tienda: copia a tb_mycoupon_used (con tb_shop_cd y used_date)
     * y borra la fila de tb_mycoupon, en una transacción, como el legacy.
     */
    public function markUsed(Mycoupon $coupon, ?int $shopCd = null, ?CarbonInterface $at = null): MycouponUsed
    {
        $at ??= now();

        return DB::transaction(function () use ($coupon, $shopCd, $at) {
            $row = Mycoupon::query()->whereKey($coupon->getKey())->lockForUpdate()->firstOrFail();

            $used = new MycouponUsed($row->getAttributes());
            $used->tb_shop_cd = $shopCd;
            $used->used_date = (string) $at->getTimestamp();
            $used->save();

            Mycoupon::query()->whereKey($row->getKey())->delete();

            return $used;
        });
    }

    /** Deja el cupón pendiente de imprimir en una tienda (print_flg = cd de la tienda) */
    public function markForPrint(Mycoupon $coupon, int $shopCd): Mycoupon
    {
        $coupon->print_flg = (string) $shopCd;
        $coupon->save();

        return $coupon;
    }

    /** Cupones del cliente pendientes de imprimir en la tienda (ajax_kaiin_mycoupon_print_crawl.php) */
    public function printableFor(int $customerCd, int $shopCd): Collection
    {
        return Mycoupon::query()
            ->where('tb_customer_cd', $customerCd)
            ->where('print_flg', (string) $shopCd)
            ->orderBy('cd')
            ->get();
    }
}
