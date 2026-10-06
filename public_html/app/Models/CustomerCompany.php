<?php

namespace App\Models;

use App\Enums\Industry;
use App\Enums\Sex;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lugar de trabajo (cliente persona) o datos de la empresa (cliente empresa).
 * Ver la migración de customer_companies.
 */
class CustomerCompany extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'industry' => Industry::class,
            'founded_on' => 'date',
            'capital' => 'integer',
            'representative_birth_date' => 'date',
            'representative_sex' => Sex::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function representativeName(): string
    {
        return trim("{$this->representative_last_name} {$this->representative_first_name}");
    }

    public function contactName(): string
    {
        return trim("{$this->contact_last_name} {$this->contact_first_name}");
    }
}
