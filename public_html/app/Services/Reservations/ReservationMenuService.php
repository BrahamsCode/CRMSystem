<?php

namespace App\Services\Reservations;

use App\Models\Reservations\ReservationMenu;
use App\Models\Reservations\ReservationMenuCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 予約項目管理 (legacy: category9/yoyaku/yoyaku_koumoku_register.php y ajax_yoyaku_koumoku_new.php).
 *
 * Orden (`rank`): por grupo tienda + tipo + categoría. Igual que el legacy:
 * - alta (y edición que cambia de tipo o categoría): `MAX(rank) + 1` de la tienda, o NULL si no hay rank;
 * - al cargar la lista, si queda algún menú activo sin rank, se renumera TODO por grupo y `cd`;
 * - ▲▼ intercambia con el vecino del mismo grupo; el borrado resta 1 a los de detrás.
 *
 * Lo que el legacy sincronizaba con el POS ($posConnect) no se hace: POS fuera de alcance.
 */
class ReservationMenuService
{
    public function __construct(private ReservationMenuImageStore $images) {}

    /**
     * Guarda el alta o la edición con los campos del formulario.
     *
     * @param  array<string, mixed>  $data  datos validados (StoreReservationMenuRequest)
     */
    public function save(ReservationMenu $menu, array $data): ReservationMenu
    {
        DB::transaction(function () use ($menu, $data) {
            $categoryCd = filled($data['tb_yoyaku_koumoku_category_cd'] ?? null) ? (int) $data['tb_yoyaku_koumoku_category_cd'] : null;
            $typeFlg = (string) ($data['type_flg'] ?? ReservationMenu::TYPE_BASE);
            $shopCd = (int) $data['tb_shop_cd'];

            // Edición: si cambia la categoría o el tipo, el rank se recalcula (el legacy lo pone a null)
            $rank = $menu->exists
                && $menu->tb_yoyaku_koumoku_category_cd === $categoryCd
                && (string) $menu->type_flg === $typeFlg
                ? $menu->rank
                : null;

            if ($rank === null) {
                $maxRank = (int) ReservationMenu::query()->where('tb_shop_cd', $shopCd)->where('disp_flg', ReservationMenu::ACTIVE_FLAG)->max('rank');
                $rank = $maxRank > 0 ? $maxRank + 1 : null;
            }

            $menu->fill([
                'name' => $data['name'],
                'shoyou_time' => (int) $data['shoyou_time'],
                'naiyou' => $data['naiyou'] ?? '',
                'tb_shop_cd' => $shopCd,
                'type_flg' => $typeFlg,
                'tb_yoyaku_koumoku_category_cd' => $categoryCd,
                'rank' => $rank,
                'tb_setsubi_cd' => (int) ($data['tb_setsubi_cd'] ?? 0) > 0 ? (int) $data['tb_setsubi_cd'] : null,
                'disp_flg' => ReservationMenu::ACTIVE_FLAG,
            ]);

            if (! $menu->exists) {
                $menu->hyouji_flg = '1';
            }

            // 最終受付時間: solo en ベースメニュー; la hora se conserva si no se marca "設定する"
            if ($typeFlg === ReservationMenu::TYPE_BASE) {
                $menu->menu_end_time_flg = (string) ($data['menu_end_time_flg'] ?? '');
                if ($menu->menu_end_time_flg === '1') {
                    $menu->menu_end_time = ReservationMenu::endTimeSeconds($data['menu_end_time1'] ?? null, $data['menu_end_time2'] ?? null);
                }
            }

            $menu->save();
        });

        return $menu;
    }

    /** 画像: sube la nueva imagen y, si se marcó 画像を削除, borra las miniaturas (mismo orden que el legacy) */
    public function syncImage(ReservationMenu $menu, ?UploadedFile $file, bool $deleteImage): void
    {
        if ($file) {
            $this->images->store($menu->cd, $file);
        }
        if ($deleteImage) {
            $this->images->delete($menu->cd);
        }
    }

    /** Borrado lógico: disp_flg = '9', rank = null y cierra el hueco del grupo */
    public function delete(ReservationMenu $menu): void
    {
        DB::transaction(function () use ($menu) {
            $oldRank = (int) $menu->rank;
            $menu->rank = null;
            $menu->save();
            $menu->delete();

            $this->sameGroup($menu)
                ->where('rank', '>', $oldRank)
                ->update(['rank' => DB::raw('rank - 1')]);
        });
    }

    /** ▲ (`rankup`) / ▼ (`rankdown`): intercambia el rank con el vecino del mismo grupo */
    public function move(ReservationMenu $menu, string $direction): void
    {
        if ($menu->rank === null) {
            return;
        }

        $up = $direction === 'rankup';
        $neighbor = $this->sameGroup($menu)
            ->where('rank', $up ? '<' : '>', $menu->rank)
            ->orderBy('rank', $up ? 'desc' : 'asc')
            ->first();

        if (! $neighbor) {
            return;
        }

        DB::transaction(function () use ($menu, $neighbor) {
            [$menu->rank, $neighbor->rank] = [$neighbor->rank, $menu->rank];
            $menu->saveQuietly();
            $neighbor->saveQuietly();
        });
    }

    /**
     * Si hay menús activos sin rank, renumera todos los activos desde 1 por tienda, tipo y categoría (orden `cd`).
     * Es lo que hace el legacy cada vez que se carga la lista.
     */
    public function normalizeRanks(): void
    {
        $active = fn () => ReservationMenu::query()->where('disp_flg', ReservationMenu::ACTIVE_FLAG);

        if (! $active()->whereNull('rank')->exists()) {
            return;
        }

        DB::transaction(function () use ($active) {
            $counter = 0;
            $previousGroup = null;
            $menus = $active()->orderBy('tb_shop_cd')->orderBy('type_flg')->orderBy('tb_yoyaku_koumoku_category_cd')->orderBy('cd')->get();

            foreach ($menus as $menu) {
                $group = $menu->tb_shop_cd . '|' . $menu->type_flg . '|' . $menu->tb_yoyaku_koumoku_category_cd;
                $counter = $group === $previousGroup ? $counter + 1 : 1;
                $previousGroup = $group;
                ReservationMenu::query()->whereKey($menu->cd)->toBase()->update(['rank' => $counter]);
            }
        });
    }

    /**
     * Bloques de la lista (ajax_yoyaku_koumoku_new.php) de un tipo: una tabla por categoría con menús
     * (orden de categoría) y al final los sin categoría. Incluye los menús 全店舗 (`tb_shop_cd = -1`).
     *
     * @return list<array{category: ?ReservationMenuCategory, menus: Collection<int, ReservationMenu>}>
     */
    public function listSections(int $shopCd, string $typeFlg): array
    {
        $menus = ReservationMenu::query()
            ->where(fn (Builder $q) => $q->where('tb_shop_cd', $shopCd)->orWhere('tb_shop_cd', -1))
            ->where('disp_flg', ReservationMenu::ACTIVE_FLAG)
            ->where('type_flg', $typeFlg)
            ->orderBy('tb_shop_cd')
            ->orderBy('rank')
            ->get();

        $sections = [];
        foreach (ReservationMenuCategory::query()->where('disp_flg', ReservationMenuCategory::ACTIVE_FLAG)->orderBy('rank')->get() as $category) {
            $inCategory = $menus->where('tb_yoyaku_koumoku_category_cd', $category->cd)->values();
            if ($inCategory->isNotEmpty()) {
                $sections[] = ['category' => $category, 'menus' => $inCategory];
            }
        }

        // Sin categoría (NULL; también 0, que es lo que guardaba el legacy al dejar la categoría vacía)
        $uncategorized = $menus->filter(fn (ReservationMenu $m) => in_array($m->tb_yoyaku_koumoku_category_cd, [null, 0], true))->values();
        if ($uncategorized->isNotEmpty()) {
            $sections[] = ['category' => null, 'menus' => $uncategorized];
        }

        return $sections;
    }

    /** Miniaturas `1_s.jpg` existentes de los menús dados: [cd => url] */
    public function thumbnails(iterable $menus): array
    {
        $urls = [];
        foreach ($menus as $menu) {
            if ($url = $this->images->thumbnailUrl($menu->cd)) {
                $urls[$menu->cd] = $url;
            }
        }

        return $urls;
    }

    /** Mismo grupo de orden: tienda, tipo y categoría (`coalesce(categoría, 0)` como el ▲▼ del legacy) */
    private function sameGroup(ReservationMenu $menu): Builder
    {
        return ReservationMenu::query()
            ->whereKeyNot($menu->cd)
            ->where('tb_shop_cd', $menu->tb_shop_cd)
            ->where('type_flg', $menu->type_flg)
            ->whereRaw('coalesce(tb_yoyaku_koumoku_category_cd, 0) = ?', [(int) $menu->tb_yoyaku_koumoku_category_cd]);
    }
}
