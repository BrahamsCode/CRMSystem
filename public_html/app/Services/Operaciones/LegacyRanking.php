<?php

namespace App\Services\Operaciones;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Orden `rank` de las listas del legacy con ▲▼ (staff_group_frm.php, staff_shift_kind_frm.php…):
 * alta al final (activos + 1) e intercambio con el vecino renumerando todos de 1 a n.
 * Trabaja con modelos que tienen la scope global de borrado lógico (solo activos).
 */
class LegacyRanking
{
    /** @param  class-string<Model>  $model */
    public function nextRank(string $model): int
    {
        return $model::count() + 1;
    }

    /**
     * ▲ / ▼: intercambia rank con el vecino y renumera todos (sin vecino no cambia el orden, pero renumera igual).
     *
     * @param  class-string<Model>  $model
     */
    public function move(string $model, int $cd, string $kind): void
    {
        DB::transaction(function () use ($model, $cd, $kind) {
            $row = $model::find($cd);
            if ($row) {
                $neighbour = $kind === 'up'
                    ? $model::where('rank', '<', (int) $row->rank)->orderByDesc('rank')->first()
                    : $model::where('rank', '>', (int) $row->rank)->orderBy('rank')->first();

                if ($neighbour) {
                    $rank = $row->rank;
                    $model::whereKey($neighbour->getKey())->toBase()->update(['rank' => $rank]);
                    $model::whereKey($row->getKey())->toBase()->update(['rank' => $neighbour->rank]);
                }
            }

            foreach ($model::orderBy('rank')->orderBy('cd')->pluck('cd') as $i => $rowCd) {
                $model::whereKey($rowCd)->toBase()->update(['rank' => $i + 1]);
            }
        });
    }
}
