<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Jornada del terminal 勤怠 (`kintai_tb_hiduke`): `hiduke` = epoch de la entrada,
 * `kintai_flg` = estado en texto (出勤, 欠勤, 有給休暇…), `bikou` = nota. ⏸ 勤怠
 */
class KintaiHiduke extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    /** Estados de kintai_new.php, en su orden */
    public const DAY_TYPES = [
        '出勤', '欠勤', '有給休暇', '忌引休暇', '遅刻', '早退', '出張',
        '半有給', '特別有給', 'シフト休', '直行', '直帰', 'その他勤務',
    ];

    protected $table = 'kintai_tb_hiduke';

    protected $casts = [
        'cd' => 'integer',
        'tb_staff_cd' => 'integer',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(KintaiRecord::class, 'kintai_tb_hiduke_cd', 'cd')->orderBy('kintai_time');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tb_staff_cd', 'cd');
    }
}
