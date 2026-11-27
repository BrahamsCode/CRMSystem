<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarjeta FeliCa del personal (`tb_card`). `staff_cd` es texto ('' = libre) y
 * `card_flg` '0' 未割当 / '1' 割当済. Sin `disp_flg`: se borra de verdad. ⏸ 勤怠
 */
class StaffCard extends LegacyModel
{
    use HasHatuban, LegacyTimestamps;

    public const UNASSIGNED = '0';

    public const ASSIGNED = '1';

    protected $table = 'tb_card';

    protected ?string $legacyCreatedColumn = null;

    protected ?string $legacyUpdatedColumn = 'update';

    protected $casts = ['cd' => 'integer'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_cd', 'cd');
    }

    public function scopeUnassigned(Builder $query): void
    {
        $query->where('card_flg', self::UNASSIGNED);
    }

    public function isAssigned(): bool
    {
        return $this->card_flg === self::ASSIGNED;
    }

    public function assignTo(Staff $staff): static
    {
        $this->forceFill(['staff_cd' => (string) $staff->cd, 'card_flg' => self::ASSIGNED])->save();

        return $this;
    }

    /** カード解除: vuelve a quedar libre */
    public function release(): static
    {
        $this->forceFill(['staff_cd' => '', 'card_flg' => self::UNASSIGNED])->save();

        return $this;
    }
}
