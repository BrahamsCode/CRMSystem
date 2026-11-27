<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cupón (マイクーポン).
 * Tabla legacy `tb_mycoupon_master` (dueño: S3 Promoción).
 */
class MycouponMaster extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_mycoupon_master';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'shouhi_point' => 'integer',
    ];

    // coupon_kubun: posición en fnc_make_array('マイクーポン') (system/inc/kihon.php)
    public const KUBUN_MAILMAGA = '';
    public const KUBUN_PRINT_ANYONE = '1';
    public const KUBUN_STAMP_EXCHANGE = '2';
    public const KUBUN_SURVEY = '3';
    public const KUBUN_SIGNUP = '4';
    public const KUBUN_REFERRAL = '5';
    public const KUBUN_VISIT_PRINT = '6';
    public const KUBUN_GAME = '7';
    public const KUBUN_SIGNAGE = '8';
    public const KUBUN_LOTTERY = '9';
    public const KUBUN_POINT_EXCHANGE = '10';

    // limit_flg (有効期限)
    public const LIMIT_WEEK = '1';
    public const LIMIT_MONTH = '2';
    public const LIMIT_THREE_MONTHS = '3';
    public const LIMIT_DAYS = '4';
    public const LIMIT_DATE = '5';

    public function issued(): HasMany
    {
        return $this->hasMany(Mycoupon::class, 'tb_mycoupon_master_cd', 'cd');
    }

    public function used(): HasMany
    {
        return $this->hasMany(MycouponUsed::class, 'tb_mycoupon_master_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }
}
