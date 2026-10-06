<?php

namespace App\Models;

use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cupón entregado a un cliente (tabla intermedia coupon_customer) */
class CouponCustomer extends BaseModel
{
    use HasUid;

    protected $table = 'coupon_customer';

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function usedShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'used_shop_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /** Entregados y todavía utilizables */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function stateLabel(): string
    {
        return match (true) {
            $this->used_at !== null => 'Usado',
            $this->expires_at !== null && $this->expires_at->isPast() => 'Vencido',
            default => 'Disponible',
        };
    }
}
