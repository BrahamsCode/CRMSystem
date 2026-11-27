<?php

namespace App\Services\Customers;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Orden ▲▼ de las listas de configuración del legacy (`search_cate_list.php`, `search_form_list.php`):
 * intercambia `rank` con la fila vecina (la de rank inmediatamente menor o mayor) y después renumera
 * todas las filas del grupo de 1 en adelante, como el bloque 「並び順入れ替え」 del legacy.
 */
class LegacyRankSorter
{
    /**
     * @param  Closure(): Builder  $scope  consulta nueva de las filas del grupo (sin borradas)
     * @param  string  $kind  'up' | 'down'
     */
    public function move(Closure $scope, int $thisCd, int $thisRow, string $kind): void
    {
        // Como el legacy: sin rank válido no se hace nada
        if ($thisRow <= 0 || ! in_array($kind, ['up', 'down'], true)) {
            return;
        }

        DB::transaction(function () use ($scope, $thisCd, $thisRow, $kind) {
            $neighbor = $kind === 'up'
                ? $scope()->where('rank', '<', $thisRow)->orderByDesc('rank')->first(['cd', 'rank'])
                : $scope()->where('rank', '>', $thisRow)->orderBy('rank')->first(['cd', 'rank']);

            if ($neighbor) {
                $scope()->where('cd', $neighbor->cd)->toBase()->update(['rank' => $thisRow]);
                $scope()->where('cd', $thisCd)->toBase()->update(['rank' => $neighbor->rank]);
            }

            $this->renumber($scope);
        });
    }

    /** rank更新: 1, 2, 3… en el orden actual */
    public function renumber(Closure $scope): void
    {
        foreach ($scope()->orderBy('rank')->pluck('cd') as $i => $cd) {
            $scope()->where('cd', $cd)->toBase()->update(['rank' => $i + 1]);
        }
    }
}
