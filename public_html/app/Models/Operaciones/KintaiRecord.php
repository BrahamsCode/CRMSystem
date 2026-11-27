<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Marca del terminal 勤怠 (`kintai_tb_kintai`): `kintai_time` epoch entero. ⏸ 勤怠 */
class KintaiRecord extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    public const CLOCK_IN = '1';   // 出勤

    public const CLOCK_OUT = '2';  // 退勤

    public const GO_OUT = '3';     // 外出

    public const RETURN = '4';     // 戻り

    protected $table = 'kintai_tb_kintai';

    protected $casts = [
        'cd' => 'integer',
        'kintai_time' => 'integer',
        'tb_staff_cd' => 'integer',
        'kintai_tb_hiduke_cd' => 'integer',
    ];

    public function hiduke(): BelongsTo
    {
        return $this->belongsTo(KintaiHiduke::class, 'kintai_tb_hiduke_cd', 'cd');
    }
}
