<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** スタッフグループ (`tb_staff_group`): nombre y orden `rank` (▲▼ en staff_group.php) */
class StaffGroup extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    protected $table = 'tb_staff_group';

    protected $casts = [
        'cd' => 'integer',
        'rank' => 'integer',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'tb_staff_group_cd', 'cd');
    }

    /** Orden de pantalla del legacy */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('rank')->orderBy('cd');
    }
}
