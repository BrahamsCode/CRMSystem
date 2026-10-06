<?php

namespace App\Models;

use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Destinatario de un envío (tabla intermedia customer_message) */
class CustomerMessage extends BaseModel
{
    use HasUid;

    protected $table = 'customer_message';

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
