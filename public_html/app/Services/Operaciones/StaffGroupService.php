<?php

namespace App\Services\Operaciones;

use App\Models\Operaciones\StaffGroup;
use Illuminate\Support\Facades\DB;

/** スタッフグループ設定: alta, edición y orden ▲▼ como staff_group_register.php / staff_group_frm.php */
class StaffGroupService
{
    public function __construct(private readonly LegacyRanking $ranking)
    {
    }

    /** Alta: rank = nº de grupos activos + 1 (al final) */
    public function create(string $name): StaffGroup
    {
        return DB::transaction(fn () => StaffGroup::create([
            'name' => $name,
            'rank' => $this->ranking->nextRank(StaffGroup::class),
            'disp_flg' => StaffGroup::ACTIVE_FLAG,
        ]));
    }

    public function rename(StaffGroup $group, string $name): StaffGroup
    {
        $group->update(['name' => $name]);

        return $group;
    }

    /** ▲ / ▼: intercambia rank con el vecino y renumera todos de 1 a n (staff_group_frm.php) */
    public function move(int $cd, string $kind): void
    {
        $this->ranking->move(StaffGroup::class, $cd, $kind);
    }
}
