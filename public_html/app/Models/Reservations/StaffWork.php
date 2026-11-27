<?php

namespace App\Models\Reservations;

use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Menús que atiende cada empleado (スタッフ施術管理, `tb_staff_works`). Sin `hatuban`:
 * el legacy numera con `MAX(cd) + 1` y reescribe todas las filas del empleado al guardar.
 * `mypage_flg = '1'` = no se muestra en Mi página.
 */
class StaffWork extends LegacyModel
{
    use LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_staff_works';

    protected $casts = [
        'cd' => 'integer',
        'tb_staff_cd' => 'integer',
        'tb_yoyaku_koumoku_master_cd' => 'integer',
        'shoyou_time' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $work) {
            $work->cd ??= (int) DB::table('tb_staff_works')->max('cd') + 1;
        });
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tb_staff_cd', 'cd');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(ReservationMenu::class, 'tb_yoyaku_koumoku_master_cd', 'cd');
    }
}
