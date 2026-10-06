<?php

namespace App\Services\Promotions;

use App\Enums\CouponUsage;
use App\Enums\PointExtendMode;
use App\Enums\PointMovement;
use App\Enums\SignupPointTiming;
use App\Enums\StampMovement;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerGroupPointRule;
use App\Models\CustomerPoint;
use App\Models\CustomerStamp;
use App\Models\PointSetting;
use App\Models\Shop;
use App\Models\StampRule;
use App\Models\StampSetting;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Sellos y puntos (スタンプ / ポイント).
 *
 * Los saldos son la suma de los movimientos (customer_stamps, customer_points);
 * customers.stamp_balance y point_balance guardan esa suma para la ficha y los
 * filtros, y se recalculan tras cada movimiento.
 */
class LoyaltyService
{
    public function __construct(private CouponIssuer $coupons) {}

    // ---------------------------------------------------------------------
    // Eventos
    // ---------------------------------------------------------------------

    /** Una visita: sello (respetando el intervalo) y puntos por el importe */
    public function onVisit(Visit $visit): void
    {
        $customer = $visit->customer;
        $shop = $visit->shop;

        if (! $customer || ! $shop) {
            return;
        }

        $this->stampForVisit($customer, $shop, $visit);
        $this->pointsForVisit($customer, $shop, $visit);
    }

    /** Alta de socio: sellos y puntos de regalo, cupones de alta y premio por referido */
    public function onSignup(Customer $customer): void
    {
        $shop = $customer->shop;

        if (! $shop) {
            return;
        }

        $stamps = StampSetting::where('shop_id', $shop->id)->active()->first();
        if ($stamps && $stamps->signup_bonus > 0) {
            $this->addStamps($customer, $shop, $stamps->signup_bonus, StampMovement::SignupBonus, 'Regalo de alta');
        }

        $points = PointSetting::where('shop_id', $shop->id)->active()->first();
        if ($points && $points->signup_points > 0 && $points->signup_timing === SignupPointTiming::OnSignup) {
            $this->addPoints($customer, $shop, $points->signup_points, PointMovement::Signup, $points->signup_comment ?: 'Puntos de alta');
        }

        foreach ($this->coupons->automatic(CouponUsage::Signup, $shop->id) as $coupon) {
            $this->coupons->issue($coupon, $customer);
        }

        if ($customer->referrer_id && ($referrer = Customer::find($customer->referrer_id))) {
            $this->rewardReferral($referrer, $customer, $shop, $points);
        }
    }

    // ---------------------------------------------------------------------
    // Movimientos
    // ---------------------------------------------------------------------

    public function addStamps(
        Customer $customer,
        Shop $shop,
        int $quantity,
        StampMovement $type,
        ?string $note = null,
        ?Visit $visit = null,
        ?CouponCustomer $couponCustomer = null,
    ): CustomerStamp {
        $setting = StampSetting::where('shop_id', $shop->id)->first();

        $movement = CustomerStamp::create([
            'customer_id' => $customer->id,
            'shop_id' => $shop->id,
            'visit_id' => $visit?->id,
            'coupon_customer_id' => $couponCustomer?->id,
            'quantity' => $quantity,
            'type' => $type,
            'expires_at' => $quantity > 0 && $setting?->expiry_flg && $setting->expiry_days
                ? now()->addDays($setting->expiry_days)->setTime($setting->expiry_hour, $setting->expiry_minute)
                : null,
            'note' => $note,
        ]);

        $before = (int) $customer->stamp_balance;
        $this->refreshBalances($customer);

        if ($quantity > 0) {
            $this->applyStampRules($customer, $shop, $before, (int) $customer->stamp_balance);
        }

        return $movement;
    }

    public function addPoints(
        Customer $customer,
        Shop $shop,
        int $points,
        PointMovement $type,
        ?string $note = null,
        ?Visit $visit = null,
        ?CouponCustomer $couponCustomer = null,
    ): CustomerPoint {
        $setting = PointSetting::where('shop_id', $shop->id)->first();
        // Vencen a la hora del proceso de vencimiento configurada (失効処理を行う時間)
        $expiresIn = fn () => now()->addDays($setting->expiry_days)->setTime($setting->expiry_hour, $setting->expiry_minute);
        $expires = $points > 0 && $setting?->expiry_flg && $setting->expiry_days !== null ? $expiresIn() : null;

        $movement = CustomerPoint::create([
            'customer_id' => $customer->id,
            'shop_id' => $shop->id,
            'visit_id' => $visit?->id,
            'coupon_customer_id' => $couponCustomer?->id,
            'points' => $points,
            'type' => $type,
            'expires_at' => $expires,
            'note' => $note,
        ]);

        // Renovar la validez de los puntos vigentes (ポイント有効期間延長動作)
        if ($expires || ($points < 0 && $setting?->expiry_flg && $setting->expiry_days !== null)) {
            $renew = $setting->extend_mode === PointExtendMode::AnyMovement || $points < 0;
            if ($renew) {
                CustomerPoint::where('customer_id', $customer->id)
                    ->where('points', '>', 0)
                    ->where('expires_at', '>', now())
                    ->update(['expires_at' => $expiresIn()]);
            }
        }

        $this->refreshBalances($customer);

        return $movement;
    }

    public function refreshBalances(Customer $customer): void
    {
        $customer->forceFill([
            'stamp_balance' => max(0, (int) CustomerStamp::where('customer_id', $customer->id)->sum('quantity')),
            'point_balance' => max(0, (int) CustomerPoint::where('customer_id', $customer->id)->sum('points')),
        ])->saveQuietly();
    }

    // ---------------------------------------------------------------------
    // Vencimientos
    // ---------------------------------------------------------------------

    /**
     * Vence los sellos y puntos caducados y devuelve cuántos clientes se tocaron.
     *
     * Se consume siempre lo más antiguo primero: lo que vence ahora es lo que
     * caducó menos lo que ya se gastó o venció antes. Así no hace falta llevar un
     * saldo por cada movimiento.
     */
    public function expire(?Carbon $now = null): int
    {
        $now ??= now();
        $touched = 0;

        foreach ([[CustomerStamp::class, 'quantity', StampMovement::Expiry], [CustomerPoint::class, 'points', PointMovement::Expiry]] as [$model, $column, $type]) {
            $customers = $model::query()
                ->where($column, '>', 0)
                ->where('expires_at', '<=', $now)
                ->distinct()
                ->pluck('customer_id');

            foreach ($customers as $customerId) {
                $expired = (int) $model::where('customer_id', $customerId)->where($column, '>', 0)->where('expires_at', '<=', $now)->sum($column);
                $spent = (int) -$model::where('customer_id', $customerId)->where($column, '<', 0)->sum($column);
                $toExpire = $expired - $spent;

                if ($toExpire <= 0) {
                    continue;
                }

                $customer = Customer::find($customerId);
                $shopId = $model::where('customer_id', $customerId)->where($column, '>', 0)->where('expires_at', '<=', $now)->value('shop_id');
                $shop = Shop::find($shopId);

                if (! $customer || ! $shop) {
                    continue;
                }

                $model === CustomerStamp::class
                    ? $this->addStamps($customer, $shop, -$toExpire, $type, 'Vencimiento')
                    : $this->addPoints($customer, $shop, -$toExpire, $type, 'Vencimiento');

                $touched++;
            }
        }

        return $touched;
    }

    // ---------------------------------------------------------------------
    // Internos
    // ---------------------------------------------------------------------

    private function stampForVisit(Customer $customer, Shop $shop, Visit $visit): void
    {
        $setting = StampSetting::where('shop_id', $shop->id)->active()->first();

        if (! $setting || ! $setting->visit_stamp_flg) {
            return;
        }

        $last = CustomerStamp::where('customer_id', $customer->id)
            ->where('shop_id', $shop->id)
            ->where('type', StampMovement::Visit)
            ->latest('created_at')
            ->value('created_at');

        // Intervalo mínimo entre sellos de la tienda (スタンプ付与間隔)
        if ($last && Carbon::parse($last)->addSeconds($setting->interval_seconds)->isFuture()) {
            return;
        }

        $this->addStamps($customer, $shop, 1, StampMovement::Visit, null, $visit);
    }

    private function pointsForVisit(Customer $customer, Shop $shop, Visit $visit): void
    {
        $setting = PointSetting::where('shop_id', $shop->id)->active()->first();

        if (! $setting) {
            return;
        }

        $groupRule = $customer->customer_group_id
            ? CustomerGroupPointRule::where('customer_group_id', $customer->customer_group_id)->first()
            : null;

        if ($groupRule && ! $groupRule->use_flg) {
            return;
        }

        // Puntos de alta que se entregan en la primera visita
        if ($setting->signup_points > 0 && $setting->signup_timing === SignupPointTiming::FirstVisit
            && ! CustomerPoint::where('customer_id', $customer->id)->where('type', PointMovement::Signup)->exists()) {
            $this->addPoints($customer, $shop, $setting->signup_points, PointMovement::Signup, $setting->signup_comment ?: 'Puntos de alta', $visit);
        }

        if ($visit->amount <= 0) {
            return;
        }

        // El POS todavía no indica si se pagó en efectivo o con tarjeta: se usa el % de efectivo
        $rate = (float) ($groupRule?->rate ?? $setting->cash_rate);
        $amount = $visit->amount / (10 ** config('crm.currency.decimals'));
        $points = (int) floor($amount * $rate / 100);

        if ($points > 0) {
            $this->addPoints($customer, $shop, $points, PointMovement::Purchase, null, $visit);
        }
    }

    /** Al cruzar el número de sellos de una regla: cupón, aviso y, si toca, tarjeta nueva */
    private function applyStampRules(Customer $customer, Shop $shop, int $before, int $after): void
    {
        $rules = StampRule::where('shop_id', $shop->id)->active()
            ->where('stamp_count', '>', $before)
            ->where('stamp_count', '<=', $after)
            ->orderBy('stamp_count')
            ->get();

        foreach ($rules as $rule) {
            $issued = $rule->coupon ? $this->coupons->issue($rule->coupon, $customer) : null;

            if ($rule->notify_flg) {
                app(MessageDispatcher::class)->notify(
                    $customer,
                    '¡Has completado ' . $rule->stamp_count . ' sellos!',
                    $rule->message ?: "Hola {nombre_completo}, ya tienes {$rule->stamp_count} sellos en tu tarjeta." . ($issued ? "\nTe hemos regalado un cupón: {cupon}\n{enlace_cupon}" : ''),
                    $issued,
                );
            }

            if ($rule->reset_flg) {
                $this->addStamps($customer, $shop, -$rule->stamp_count, StampMovement::CardReset, 'Tarjeta completada');

                return;
            }
        }
    }

    private function rewardReferral(Customer $referrer, Customer $referee, Shop $shop, ?PointSetting $points): void
    {
        if ($points) {
            $previous = CustomerPoint::where('customer_id', $referrer->id)->where('type', PointMovement::Referral)->count();

            if ($points->referral_points > 0 && ($points->referral_limit === null || $previous < $points->referral_limit)) {
                $this->addPoints($referrer, $shop, $points->referral_points, PointMovement::Referral, $points->referral_comment ?: "Por referir a {$referee->greetingName()}");
            }
            if ($points->referee_points > 0) {
                $this->addPoints($referee, $shop, $points->referee_points, PointMovement::Referred, $points->referral_comment ?: 'Por venir referido');
            }
        }

        foreach ($this->coupons->automatic(CouponUsage::Referral, $shop->id) as $coupon) {
            $this->coupons->issue($coupon, $referrer);
        }
    }
}
