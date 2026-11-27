<?php

namespace App\Services\Operaciones;

use App\Models\Operaciones\StaffGroup;
use Illuminate\Support\Facades\DB;

/** スタッフグループ設定: alta, edición y orden ▲▼ como staff_group_register.php / staff_group_frm.php */
class StaffGroupService
{
    /** Alta: rank = nº de grupos activos + 1 (al final) */
    public function create(string $name): StaffGroup
    {
        return DB::transaction(fn () => StaffGroup::create([
            'name' => $name,
            'rank' => StaffGroup::count() + 1,
            'disp_flg' => StaffGroup::ACTIVE_FLAG,
        ]));
    }

    public function rename(StaffGroup $group, string $name): StaffGroup
    {
        $group->update(['name' => $name]);

        return $group;
    }

    /**
     * ▲ / ▼: intercambia rank con el vecino y renumera todos de 1 a n (staff_group_frm.php).
     * Sin vecino no cambia el orden, pero renumera igual.
     */
    public function move(int $cd, string $kind): void
    {
        DB::transaction(function () use ($cd, $kind) {
            $group = StaffGroup::find($cd);
            if ($group) {
                $neighbour = $kind === 'up'
                    ? StaffGroup::where('rank', '<', (int) $group->rank)->orderByDesc('rank')->first()
                    : StaffGroup::where('rank', '>', (int) $group->rank)->orderBy('rank')->first();

                if ($neighbour) {
                    $rank = $group->rank;
                    StaffGroup::whereKey($neighbour->cd)->update(['rank' => $rank]);
                    StaffGroup::whereKey($group->cd)->update(['rank' => $neighbour->rank]);
                }
            }

            foreach (StaffGroup::orderBy('rank')->orderBy('cd')->pluck('cd') as $i => $groupCd) {
                StaffGroup::whereKey($groupCd)->toBase()->update(['rank' => $i + 1]);
            }
        });
    }
}
