<?php

namespace App\Services\Terminales;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tienda de la sesión para 電子看板 ($_SESSION['shop_cd'] del legacy).
 *
 * El legacy filtra con `tb_shop_cd in (coalesce(tb_shop_cd, 0), -1, <shop_cd>)`, que en la
 * práctica solo deja fuera las filas con tb_shop_cd NULL; se reproduce tal cual.
 * Mientras S1 no entregue el login, la tienda sale de session('shop_cd').
 */
class SignageShop
{
    public function current(): ?int
    {
        $cd = session('shop_cd');

        return filled($cd) ? (int) $cd : null;
    }

    /** Aplica el filtro de tienda del legacy ($add_whereStr) si hay tienda en la sesión */
    public function scope(Builder $query): Builder
    {
        $shopCd = $this->current();
        if ($shopCd !== null) {
            $column = $query->getModel()->qualifyColumn('tb_shop_cd');
            $query->whereRaw("{$column} in (coalesce({$column}, 0), -1, ?)", [$shopCd]);
        }

        return $query;
    }

    /** Comprobación de los *_update.php: la fila debe ser de la tienda de la sesión */
    public function owns(Model $model): bool
    {
        return (string) $model->getAttribute('tb_shop_cd') === (string) $this->current();
    }
}
