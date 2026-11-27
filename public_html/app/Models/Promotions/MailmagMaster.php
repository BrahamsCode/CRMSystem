<?php

namespace App\Models\Promotions;

use App\Models\Concerns\HasHatuban;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Envío de お知らせ (メルマガ, デコメール, follow, auto, recordatorio, cupón, encuesta). Columnas en japonés como el legacy.
 * También lo lee el servidor de push externo (g-api). Sin disp_flg de borrado: mail_kaijo lo borra físicamente.
 * Tabla legacy `mailmag_master` (dueño: S3 Promoción).
 */
class MailmagMaster extends LegacyModel
{
    use HasHatuban;

    protected $table = 'mailmag_master';

    /** El legacy numera con hatuban.koumoku = 'mailmag' (mail_send_frm.php) */
    protected string $hatubanKoumoku = 'mailmag';

    protected $casts = [
        'cd' => 'integer',
        'type_data_cd' => 'integer',
        'pos_tb_shop_cd' => 'integer',
        'send_count' => 'integer',
        'last_send_date' => 'integer',
    ];

    // type_flg (mail_type del legacy)
    public const TYPE_MAIL = '';
    public const TYPE_DECO = 'deco';
    public const TYPE_FOLLOW = 'follow';
    public const TYPE_FOLLOW_DECO = 'followdeco';
    public const TYPE_AUTO = 'auto';
    public const TYPE_AUTO_DECO = 'autodeco';
    public const TYPE_RESERVATION = 'yoyaku';
    public const TYPE_RESERVATION_DECO = 'remdeco';
    public const TYPE_COUPON = 'coupon';
    public const TYPE_SURVEY = 'anketo';

    /** Tipos recurrentes: el Scheduler los evalúa cada día y no se marcan como enviados */
    public const RECURRING_TYPES = ['follow', 'followdeco', 'auto', 'autodeco', 'yoyaku', 'remdeco'];

    // 配信状況
    public const STATUS_PENDING = '1';
    public const STATUS_SENT = '2';

    public function sendedLogs(): HasMany
    {
        return $this->hasMany(MailmagSendedLog::class, 'mailmag_master_cd', 'cd');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(MailmagAnalyze::class, 'mailmag_master_cd', 'cd');
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(SendSchedule::class, 'mag_cd', 'cd');
    }

    /** Cupón adjunto (type_flg = coupon) */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(MycouponMaster::class, 'type_data_cd', 'cd');
    }

    /** Encuesta (type_flg = anketo) */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(AnketoCate::class, 'type_data_cd', 'cd');
    }

    public function isRecurring(): bool
    {
        return in_array((string) $this->type_flg, self::RECURRING_TYPES, true);
    }
}
