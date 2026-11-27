<?php

namespace App\Models\Customers;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Reservations\Reservation;
use App\Models\Shop;
use App\Models\Staff;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Cliente / socio (`tb_customer`). Columnas del legacy tal cual:
 * - `id`: Nº de socio (顧客番号, `createID`); `login_id`/`login_pass` en claro como el legacy.
 * - `name`/`name_kana`: un solo campo, apellido y nombre separados por espacio (半角 o 全角).
 * - `kojin_birth`: 'Y/m/d' (vacío '//'); `kojin_sex`: '男性'/'女性'; `job`: texto de `tb_job`.
 * - `touroku_kubun = '2'`: socio con registro completo (本登録).
 * - `stop_yoyaku_reminder_mail_flg = '1'`: no recibe el recordatorio de reservas.
 */
class Customer extends LegacyModel
{
    use HasFactory, HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    public const MEMBER_KUBUN = '2';

    protected $table = 'tb_customer';

    protected $hidden = ['login_pass'];

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'tb_customer_group_cd' => 'integer',
        'tb_customer_level_cd' => 'integer',
        'tb_raiten_douki_master_cd' => 'integer',
        'shoukai_tb_customer_cd' => 'integer',
        'kingaku_rank_now' => 'integer',
        'kingaku_rank_pre' => 'integer',
        'raiten_rank_now' => 'integer',
        'raiten_rank_pre' => 'integer',
        'shuki_heikin' => 'integer',
        'stamp_count' => 'integer',
    ];

    /** Socios con registro completo (el filtro que el legacy aplica casi siempre) */
    public function scopeMembers(Builder $query): void
    {
        $query->where('touroku_kubun', self::MEMBER_KUBUN);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'tb_shop_cd', 'cd');
    }

    /** 指名担当者 */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tb_staff_cd', 'cd');
    }

    public function visitMotive(): BelongsTo
    {
        return $this->belongsTo(VisitMotive::class, 'tb_raiten_douki_master_cd', 'cd');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(CustomerLevel::class, 'tb_customer_level_cd', 'cd');
    }

    /** Quién lo presentó (紹介者) */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'shoukai_tb_customer_cd', 'cd');
    }

    /** A quiénes presentó (紹介実績) */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'shoukai_tb_customer_cd', 'cd');
    }

    public function subs(): HasMany
    {
        return $this->hasMany(CustomerSub::class, 'tb_customer_cd', 'cd');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'user_cd', 'cd');
    }

    public function kartes(): HasMany
    {
        return $this->hasMany(Karte::class, 'tb_customer_cd', 'cd');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CustomerPhoto::class, 'tb_customer_cd', 'cd');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'tb_customer_cd', 'cd');
    }

    /** [apellido, nombre] partiendo `name` por el primer espacio, como `customer_new.php` */
    public function nameParts(string $column = 'name'): array
    {
        $value = (string) $this->getAttribute($column);
        if (preg_match('/^(.*?)[ 　]+(.*)$/u', $value, $m)) {
            return [$m[1], $m[2]];
        }

        return [$value, ''];
    }

    /** Fecha de nacimiento como Carbon, o null si `kojin_birth` no es una fecha válida */
    public function birthDate(): ?Carbon
    {
        $parts = explode('/', (string) $this->kojin_birth);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo');
    }

    /** Edad en una fecha, con el mismo cálculo que `cRaiten::pushRaitenRireki`; null sin fecha válida */
    public function ageAt(?CarbonInterface $at = null): ?int
    {
        $birth = $this->birthDate();

        return $birth ? (int) $birth->diffInYears($at ?? now('Asia/Tokyo')) : null;
    }
}
