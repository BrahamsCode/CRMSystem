<?php

namespace App\Services\Reservations;

use App\Models\Reservations\ReservationMenuCategory;
use Illuminate\Support\Facades\DB;

/**
 * 予約項目カテゴリ管理 (legacy: category9/yoyaku/yoyaku_koumoku_category_register.php y
 * ajax_yoyaku_koumoku_category.php). Categorías globales, orden `rank` consecutivo desde 1.
 */
class ReservationMenuCategoryService
{
    /** Alta (rank = último + 1) o edición del nombre */
    public function save(ReservationMenuCategory $category, string $name): ReservationMenuCategory
    {
        if (! $category->exists) {
            $category->rank = (int) ReservationMenuCategory::withDeleted()->whereNotNull('rank')->max('rank') + 1;
        }

        // Como el legacy: guardar también vuelve a 表示 (hyouji_flg = '1')
        $category->fill([
            // form2Db() de yoyaku_koumoku_category_register.php (半角カナ → 全角 y recorte); texto plano
            'name' => ReservationInput::form2Db($name),
            'disp_flg' => ReservationMenuCategory::ACTIVE_FLAG,
            'hyouji_flg' => ReservationMenuCategory::VISIBLE,
        ])->save();

        return $category;
    }

    /** ▲ (`rankup`) / ▼ (`rankdown`): intercambia con la categoría de rank ±1 */
    public function move(ReservationMenuCategory $category, string $direction): void
    {
        if ($category->rank === null) {
            return;
        }

        $targetRank = $direction === 'rankup' ? $category->rank - 1 : $category->rank + 1;
        $neighbor = ReservationMenuCategory::query()->where('rank', $targetRank)->first();
        if (! $neighbor) {
            return;
        }

        DB::transaction(function () use ($category, $neighbor, $targetRank) {
            $neighbor->rank = $category->rank;
            $neighbor->save();
            $category->rank = $targetRank;
            $category->save();
        });
    }

    /** 表示 ⇔ 非表示 */
    public function toggleVisibility(ReservationMenuCategory $category): void
    {
        $category->hyouji_flg = $category->hyouji_flg === ReservationMenuCategory::VISIBLE
            ? ReservationMenuCategory::HIDDEN
            : ReservationMenuCategory::VISIBLE;
        $category->save();
    }

    /** Borrado lógico: disp_flg = '9', rank = null y los de detrás suben uno */
    public function delete(ReservationMenuCategory $category): void
    {
        DB::transaction(function () use ($category) {
            $oldRank = (int) $category->rank;
            $category->rank = null;
            $category->save();
            $category->delete();

            ReservationMenuCategory::withDeleted()
                ->where('rank', '>', $oldRank)
                ->toBase()
                ->update(['rank' => DB::raw('rank - 1')]);
        });
    }
}
