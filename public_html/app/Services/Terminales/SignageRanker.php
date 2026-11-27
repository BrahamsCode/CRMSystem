<?php

namespace App\Services\Terminales;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Orden (`rank`) de los listados de 電子看板: el «rank更新» que el legacy hace al cargar cada
 * listado (renumerar 1..n) y el intercambio ▲▼ con la fila vecina («並び順入れ替え»).
 *
 * Los UPDATE van por el query builder base para no tocar update_date (el reproductor
 * sondea update_date de tb_display: cambiar el orden no debe recargar la pantalla).
 *
 * El grupo se pasa como Closure que devuelve una consulta nueva cada vez.
 */
class SignageRanker
{
    /** Renumera el grupo 1..n en el orden actual (rank, cd) */
    public function renumber(Closure $group): void
    {
        $rows = $group()->orderBy('rank')->orderBy('cd')->get(['cd', 'rank']);

        foreach ($rows->values() as $index => $row) {
            if ((int) $row->rank !== $index + 1) {
                $group()->where('cd', $row->cd)->toBase()->update(['rank' => $index + 1]);
            }
        }
    }

    /** ▲ (`up`) o ▼ (`down`): intercambia el rank de la fila con el de su vecina del grupo */
    public function move(Closure $group, int $cd, string $kind): void
    {
        if (! in_array($kind, ['up', 'down'], true)) {
            return;
        }

        DB::transaction(function () use ($group, $cd, $kind) {
            $this->renumber($group);

            $row = $group()->where('cd', $cd)->first(['cd', 'rank']);
            if (! $row) {
                return;
            }

            /** @var Builder $neighborQuery */
            $neighborQuery = $kind === 'up'
                ? $group()->where('rank', '<', $row->rank)->orderByDesc('rank')
                : $group()->where('rank', '>', $row->rank)->orderBy('rank');
            $neighbor = $neighborQuery->first(['cd', 'rank']);
            if (! $neighbor) {
                return;
            }

            $group()->where('cd', $neighbor->cd)->toBase()->update(['rank' => $row->rank]);
            $group()->where('cd', $row->cd)->toBase()->update(['rank' => $neighbor->rank]);
        });
    }
}
