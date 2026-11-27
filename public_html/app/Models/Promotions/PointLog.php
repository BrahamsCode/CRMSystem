<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de puntos (sounyu_point suma, shouhi_point resta, shori_mae_point saldo anterior).
 * Tabla legacy `tb_point_log` (dueño: S3 Promoción).
 */
class PointLog extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_point_log';

    protected $casts = [
        'cd' => 'integer',
        'tb_shop_cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'tb_customer_cd' => 'integer',
        'tb_terminal_cd' => 'integer',
        'shori_mae_point' => 'integer',
        'kaikei' => 'integer',
        'sashihiki_kaikei' => 'integer',
        'percentage' => 'float',
        'shouhi_point' => 'integer',
        'jidou_point' => 'integer',
        'sounyu_point' => 'integer',
        'push_master_cd' => 'integer',
    ];

    protected ?string $legacyUpdatedColumn = null;

    // action_flg (ポイント集計)
    public const ACTION_CRM_SIGNUP = 'crm:newRegist';
    public const ACTION_APP_SIGNUP = 'mycolle:newRegist';
    public const ACTION_APP_SIGN_UP = 'mycolle:signUp';
    public const ACTION_REFERRAL = 'mycolle:introduce';
    public const ACTION_PUSH = 'mycolle:push';
    public const ACTION_DAILY_LOGIN = 'mycolle:dailyLogin';
    public const ACTION_CHECK_IN = 'mycolle:checkIn';
    public const ACTION_COUPON_CHANGE = 'mycolle:couponChange';
    public const ACTION_API_USE = 'api:point_use';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customers\Customer::class, 'tb_customer_cd', 'cd');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop::class, 'tb_shop_cd', 'cd');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'tb_staff_cd', 'cd');
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Terminal::class, 'tb_terminal_cd', 'cd');
    }

    public function push(): BelongsTo
    {
        return $this->belongsTo(PushMaster::class, 'push_master_cd', 'cd');
    }
}
