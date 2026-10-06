<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\MessageChannel;
use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Envío: newsletter, email con diseño o push */
class Message extends BaseModel
{
    use HasUid;

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'channel' => MessageChannel::class,
            'delivery_status' => DeliveryStatus::class,
            'filters' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function footerShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'footer_shop_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MessageRule::class, 'message_rule_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    /** Tasa de clics del legacy: clientes que hicieron clic / total enviado */
    public function clickRate(): ?float
    {
        $sent = $this->sent_count ?? $this->recipients()->whereNotNull('sent_at')->count();

        if (! $sent) {
            return null;
        }

        $clicked = $this->clicked_count ?? $this->recipients()->whereNotNull('clicked_at')->count();

        return round($clicked / $sent * 100, 1);
    }
}
