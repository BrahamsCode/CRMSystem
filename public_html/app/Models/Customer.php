<?php

namespace App\Models;

use App\Enums\AddressType;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\Industry;
use App\Enums\MailMagazine;
use App\Enums\Occupation;
use App\Enums\Sex;
use App\Models\Concerns\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    use HasUid;

    /** El Nº de socio del legacy arranca en esta serie */
    private const CODE_START = 1100001;

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'type' => CustomerType::class,
            'sex' => Sex::class,
            'mail_magazine_flg' => MailMagazine::class,
            'address_type' => AddressType::class,
            'occupation' => Occupation::class,
            'company_industry' => Industry::class,
            'industry' => Industry::class,
            'birth_date' => 'date',
            'wedding_date' => 'date',
            'company_founded_date' => 'date',
            'rep_birth_date' => 'date',
            'last_visit_date' => 'date',
            'next_visit_date' => 'date',
            'last_login_at' => 'datetime',
            'custom_data' => 'array',
            'spouse_flg' => 'integer',
            'reservation_reminder_flg' => 'integer',
            // El legacy la guarda en claro y la ficha tiene un botón «Mostrar».
            // Aquí se cifra: se puede restablecer, pero no recuperar.
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $customer) {
            $customer->code ??= static::nextCode();
        });
    }

    /**
     * Siguiente Nº de socio.
     *
     * Pendiente: si dos altas simultáneas leen el mismo máximo, la segunda choca
     * con el índice único de `code`. Cuando haya concurrencia real conviene
     * pasarlo a una secuencia de PostgreSQL.
     */
    public static function nextCode(): string
    {
        $max = (int) static::withTrashed()->max('code');

        return (string) ($max >= self::CODE_START ? $max + 1 : self::CODE_START);
    }

    /** Nombre completo, en el orden japonés: apellido y luego nombre */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name}");
    }

    public function getFullNameKanaAttribute(): string
    {
        return trim("{$this->last_name_kana} {$this->first_name_kana}");
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** El legacy muestra 端末登録有/無; se deduce de si hay terminal asignado */
    public function hasTerminal(): bool
    {
        return $this->terminal_id !== null;
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function visitMotive(): BelongsTo
    {
        return $this->belongsTo(VisitMotive::class);
    }

    public function amountRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'amount_rank_id');
    }

    public function prevAmountRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'prev_amount_rank_id');
    }

    public function visitRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'visit_rank_id');
    }

    public function prevVisitRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'prev_visit_rank_id');
    }

    /** Quién lo trajo */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referrer_id');
    }

    /** A quiénes ha traído */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referrer_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class)->orderByDesc('visited_at');
    }

    /** Valores de las categorías personalizadas de tipo múltiple */
    public function customValues(): HasMany
    {
        return $this->hasMany(CustomValue::class)->orderBy('row_no');
    }
}
