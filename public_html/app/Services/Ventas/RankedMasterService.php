<?php

namespace App\Services\Ventas;

use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alta, edición, borrado y orden (▲▼) de メニュー管理 y 割引管理 (karute_menu_register.php,
 * karute_waribiki_register.php, ajax_karute_*_list.php). Las dos tablas funcionan igual:
 * `rank` por tienda (max + 1 al crear), borrado lógico con `rank = null` y corrimiento de los siguientes.
 */
class RankedMasterService
{
    /**
     * Alta (`$model` nuevo) o edición. `$values` ya normalizados por el Form Request.
     *
     * @param  class-string<ServiceMenu|Discount>  $class
     */
    public function save(string $class, ?int $cd, int $shopCd, array $values): ServiceMenu|Discount
    {
        return DB::transaction(function () use ($class, $cd, $shopCd, $values) {
            $item = $cd !== null ? $class::query()->findOrFail($cd) : new $class;

            // Como el legacy: la edición también reactiva (disp_flg/hyouji_flg = 1) y mueve a la tienda del formulario
            $item->fill(['tb_shop_cd' => $shopCd, 'disp_flg' => '1', 'hyouji_flg' => '1'] + $values);

            if (! $item->exists) {
                // MAX(rank) de las vigentes de la tienda + 1
                $item->rank = (int) $class::query()->ofShop($shopCd)->where('disp_flg', '1')->max('rank') + 1;
            }

            $item->save();

            return $item;
        });
    }

    /** act=del: disp_flg 9, rank null y los de rank mayor de la misma tienda suben uno */
    public function delete(ServiceMenu|Discount $item): void
    {
        DB::transaction(function () use ($item) {
            $rank = $item->rank;
            $shopCd = $item->tb_shop_cd;

            $item->rank = null;
            $item->delete();

            if ($rank !== null) {
                $item::query()->ofShop($shopCd)->where('rank', '>', $rank)->decrement('rank');
            }
        });
    }

    /**
     * ▲ (rankup) / ▼ (rankdown): intercambia el rank con la fila vigente anterior / siguiente de la tienda.
     * El legacy tomaba el rank de la petición (send_vl); aquí se usa el guardado.
     *
     * @param  class-string<ServiceMenu|Discount>  $class
     */
    public function move(string $class, int $shopCd, int $cd, string $direction): void
    {
        $item = $class::query()->ofShop($shopCd)->where('disp_flg', '1')->find($cd);
        if (! $item || $item->rank === null) {
            return;
        }
        $rank = $item->rank;

        $neighbour = $class::query()
            ->ofShop($shopCd)
            ->where('disp_flg', '1')
            ->when($direction === 'rankup',
                fn ($q) => $q->where('rank', '<', $rank)->orderByDesc('rank'),
                fn ($q) => $q->where('rank', '>', $rank)->orderBy('rank'))
            ->first();

        if (! $neighbour) {
            return;
        }

        DB::transaction(function () use ($neighbour, $item, $rank) {
            $neighbourRank = $neighbour->rank;
            $neighbour->update(['rank' => $rank]);
            $item->update(['rank' => $neighbourRank]);
        });
    }

    /**
     * Listado vigente de la tienda (ORDER BY rank, cd)
     *
     * @param  class-string<ServiceMenu|Discount>  $class
     */
    public function list(string $class, int $shopCd): Collection
    {
        return $class::query()->ofShop($shopCd)->where('disp_flg', '1')->ordered()->get();
    }
}
