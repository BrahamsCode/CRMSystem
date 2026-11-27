<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use Illuminate\Database\Eloquent\Builder;

/** シフト区分 (`tb_staff_shift_kind`): nombre, color y orden `rank` */
class StaffShiftKind extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_staff_shift_kind';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('rank')->orderBy('cd');
    }
}
