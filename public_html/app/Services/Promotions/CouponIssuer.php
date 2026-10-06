<?php

namespace App\Services\Promotions;

use App\Enums\CouponUsage;
use App\Enums\PointMovement;
use App\Enums\StampMovement;
use App\Models\Coupon;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Entrega, canje y uso de cupones */
class CouponIssuer
{
    /**
     * Entrega el cupón al cliente. Devuelve null si no corresponde: cupón
     * inactivo o vencido, o ya entregado y sin «se puede volver a entregar».
     */
    public function issue(Coupon $coupon, Customer $customer, ?Message $message = null): ?CouponCustomer
    {
        if ($coupon->status !== 1 || $coupon->isExpired()) {
            return null;
        }

        if (! $coupon->reissue_flg && $coupon->issued()->where('customer_id', $customer->id)->exists()) {
            return null;
        }

        $now = now();

        return $coupon->issued()->create([
            'customer_id' => $customer->id,
            'message_id' => $message?->id,
            'issued_at' => $now,
            'expires_at' => $coupon->validity_type->expiresAt($now, $coupon->validity_days, $coupon->valid_until),
        ]);
    }

    /**
     * Canje de un cupón que cuesta sellos o puntos (スタンプ交換 / ポイント交換).
     *
     * @throws RuntimeException si el cliente no tiene saldo o el cupón no se puede entregar
     */
    public function exchange(Coupon $coupon, Customer $customer, Shop $shop): CouponCustomer
    {
        if (! $coupon->usage_type->hasCost()) {
            throw new RuntimeException('Este cupón no se canjea: se entrega directamente.');
        }

        return DB::transaction(function () use ($coupon, $customer, $shop) {
            // Bloquea al cliente para que dos canjes a la vez no gasten el mismo saldo
            $customer = Customer::lockForUpdate()->findOrFail($customer->id);

            $saldo = $coupon->usage_type === CouponUsage::StampExchange ? $customer->stamp_balance : $customer->point_balance;

            if ($saldo < $coupon->cost) {
                throw new RuntimeException("Saldo insuficiente: tiene {$saldo} y el cupón cuesta {$coupon->cost} {$coupon->usage_type->costUnit()}.");
            }

            $issued = $this->issue($coupon, $customer)
                ?? throw new RuntimeException('El cliente ya tiene este cupón o el cupón no está disponible.');

            // Se resuelve aquí y no en el constructor: LoyaltyService también usa esta clase
            $loyalty = app(LoyaltyService::class);

            if ($coupon->usage_type === CouponUsage::StampExchange) {
                $loyalty->addStamps($customer, $shop, -$coupon->cost, StampMovement::Exchange, "Canje: {$coupon->name}", couponCustomer: $issued);
            } else {
                $loyalty->addPoints($customer, $shop, -$coupon->cost, PointMovement::Exchange, "Canje: {$coupon->name}", couponCustomer: $issued);
            }

            return $issued;
        });
    }

    /** Marca un cupón entregado como usado en caja */
    public function markUsed(CouponCustomer $issued, Shop $shop): void
    {
        if (! $issued->isUsable()) {
            throw new RuntimeException('El cupón ya se usó o está vencido.');
        }

        $issued->update(['used_at' => now(), 'used_shop_id' => $shop->id]);
    }

    /** Cupones automáticos de un tipo (alta de socio, referido…) disponibles en la tienda */
    public function automatic(CouponUsage $usage, ?int $shopId)
    {
        return Coupon::active()->where('usage_type', $usage)->forShop($shopId)->get()
            ->reject(fn (Coupon $c) => $c->isExpired());
    }
}
