<?php

namespace App\Services\Sales;

use App\Models\Sales\Discount;
use App\Models\Sales\ServiceMenu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * メニュー管理 y 割引管理 (legacy: karute_menu_register.php, karute_waribiki_register.php,
 * ajax_karute_menu_list.php, ajax_karute_waribiki_list.php). Las dos tablas funcionan igual:
 * orden `rank` por tienda, borrado `disp_flg = '9'` con `rank = null` y el resto de la tienda sube uno.
 *
 * @template T of ServiceMenu|Discount
 */
class KaruteMasterService
{
    /** Activos de la tienda en el orden del legacy (rank, cd) */
    public function list(string $modelClass, int $shopCd): Collection
    {
        return $modelClass::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')
            ->orderBy('rank')->orderBy('cd')->get();
    }

    /** Registro activo (`disp_flg = '1'`) para el formulario de edición (?cd=) */
    public function findActive(string $modelClass, mixed $cd): ServiceMenu|Discount|null
    {
        return filled($cd) ? $modelClass::query()->where('disp_flg', '1')->find((int) $cd) : null;
    }

    /**
     * Alta (act=new: cd con hatuban, rank = máximo + 1 de la tienda) o edición (act=up). En las dos el legacy
     * reescribe las columnas comunes y deja `disp_flg = '1'`, `hyouji_flg = '1'`.
     */
    public function save(ServiceMenu|Discount $item, array $values, int $shopCd): ServiceMenu|Discount
    {
        return DB::transaction(function () use ($item, $values, $shopCd) {
            if (! $item->exists) {
                $item->rank = (int) $item::query()->where('disp_flg', '1')->where('tb_shop_cd', $shopCd)->max('rank') + 1;
            }
            $item->fill($values + ['tb_shop_cd' => $shopCd, 'disp_flg' => '1', 'hyouji_flg' => '1']);
            $item->save();

            return $item;
        });
    }

    /** act=del: disp_flg 9, rank null y los de rank mayor de la misma tienda suben uno */
    public function delete(ServiceMenu|Discount $item, mixed $rank): void
    {
        DB::transaction(function () use ($item, $rank) {
            $item->forceFill(['rank' => null, 'disp_flg' => '9'])->save();

            $item::withDeleted()->where('tb_shop_cd', $item->tb_shop_cd)->where('rank', '>', (int) $rank)
                ->update(['rank' => DB::raw('rank - 1')]);
        });
    }

    /**
     * ▲▼ (send_type rankup/rankdown): intercambia el rank con el vecino activo de la tienda.
     * `$rank` es el rank que mandó la pantalla (send_vl), como en el legacy.
     */
    public function move(string $modelClass, int $shopCd, int $targetCd, int $rank, string $direction): void
    {
        $neighbour = $modelClass::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', '1')
            ->when($direction === 'rankup',
                fn ($q) => $q->where('rank', '<', $rank)->orderByDesc('rank'),
                fn ($q) => $q->where('rank', '>', $rank)->orderBy('rank'))
            ->first();

        if (! $neighbour) {
            return;
        }

        DB::transaction(function () use ($modelClass, $neighbour, $targetCd, $rank) {
            $neighbourRank = $neighbour->rank;
            $modelClass::withDeleted()->whereKey($neighbour->cd)->update(['rank' => $rank]);
            $modelClass::withDeleted()->whereKey($targetCd)->update(['rank' => $neighbourRank]);
        });
    }
}
