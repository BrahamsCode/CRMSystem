<?php

namespace App\Services\Promotions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Orden ▲▼ de las listas del legacy (columna `rank`): intercambia el rank con el vecino
 * activo (disp_flg = '1') de arriba o de abajo, como ajax_mail_template.php (corregido el 2012.11.07).
 */
class RankOrder
{
    public const UP = 'rankup';

    public const DOWN = 'rankdown';

    /**
     * @param  Builder  $scope  consulta del grupo donde se ordena (p. ej. las direcciones de una tienda)
     */
    public function move(Builder $scope, int $cd, string $direction): void
    {
        if (! in_array($direction, [self::UP, self::DOWN], true)) {
            return;
        }

        DB::transaction(function () use ($scope, $cd, $direction) {
            /** @var Model|null $item */
            $item = (clone $scope)->whereKey($cd)->lockForUpdate()->first();
            if (! $item || $item->rank === null) {
                return;
            }

            $neighbour = (clone $scope)
                ->where('disp_flg', '1')
                ->where('rank', $direction === self::UP ? '<' : '>', $item->rank)
                ->orderBy('rank', $direction === self::UP ? 'desc' : 'asc')
                ->lockForUpdate()
                ->first();

            if (! $neighbour) {
                return;
            }

            [$item->rank, $neighbour->rank] = [$neighbour->rank, $item->rank];
            $neighbour->save();
            $item->save();
        });
    }
}
