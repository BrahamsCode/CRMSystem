<?php

namespace App\Models;

use App\Enums\CouponDiscountType;
use App\Enums\CouponUsage;
use App\Enums\CouponValidity;
use App\Enums\LotteryRank;
use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cupón. Tabla del estándar (name, type, value) con las columnas del módulo
 * de promociones.
 */
class Coupon extends BaseModel
{
    use HasUid;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'type' => CouponDiscountType::class,
            'usage_type' => CouponUsage::class,
            'validity_type' => CouponValidity::class,
            'lottery_rank' => LotteryRank::class,
            'valid_until' => 'date',
            'notes' => 'array',
            'reissue_flg' => 'integer',
            'display_flg' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function issued(): HasMany
    {
        return $this->hasMany(CouponCustomer::class);
    }

    /** Cupones que puede usar una tienda: los suyos y los de todas las tiendas */
    public function scopeForShop(Builder $query, ?int $shopId): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('shop_id')->orWhere('shop_id', $shopId));
    }

    /** «10 % de descuento» */
    public function discountLabel(): string
    {
        return $this->type->format($this->value) . ' de descuento';
    }

    public function validityLabel(): string
    {
        return match ($this->validity_type) {
            CouponValidity::Days => "{$this->validity_days} días desde la entrega",
            CouponValidity::UntilDate => 'Hasta el ' . $this->valid_until?->format('d/m/Y'),
            default => $this->validity_type->label(),
        };
    }

    /** Ya no se puede entregar: venció la fecha fija */
    public function isExpired(): bool
    {
        return $this->validity_type === CouponValidity::UntilDate
            && $this->valid_until !== null
            && $this->valid_until->endOfDay()->isPast();
    }
}
